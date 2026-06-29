<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\CashierCashMovement;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Inventory\StockMovementService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CashierOrderController extends Controller
{
    private const CART_KEY = 'cashier_pos_cart';

    public function index(Request $request)
    {
        $openShift = $this->openShift();
        $cart = collect(session(self::CART_KEY, []));
        $cartSubtotal = $cart->sum('subtotal');

        $products = collect();
        $categories = Category::where('status', 'active')->orderBy('name')->get();

        if ($openShift) {
            $products = Product::with('category')
                ->where('status', 'published')
                ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%' . $request->search . '%'))
                ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->category))
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString();
        }

        return view('cashier.orders.index', [
            'title' => 'POS Orders',
            'openShift' => $openShift,
            'products' => $products,
            'categories' => $categories,
            'cart' => $cart,
            'cartSubtotal' => $cartSubtotal,
            'cartTotal' => $cartSubtotal,
        ]);
    }

    public function history(Request $request)
    {
        $orders = Order::with(['outlet', 'cashier', 'payment'])
            ->where('order_channel', 'cashier_pos')
            ->when(! auth()->user()?->hasAnyRole(['admin', 'super_admin']), fn ($query) => $query->where('cashier_id', auth()->id()))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('created_at', $request->date))
            ->when($request->filled('service_type'), fn ($query) => $query->where('service_type', $request->service_type))
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->payment_method))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('cashier.orders.history', [
            'title' => 'Riwayat POS',
            'orders' => $orders,
        ]);
    }

    public function addToCart(Request $request)
    {
        if (!$this->openShift()) {
            return back()->with('error', 'Buka shift terlebih dahulu sebelum membuat transaksi POS.');
        }

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'qty' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $qty = (int) ($validated['qty'] ?? 1);
        $cart = session(self::CART_KEY, []);
        $currentQty = (int) ($cart[$product->id]['qty'] ?? 0);
        $newQty = $currentQty + $qty;

        $cart[$product->id] = $this->cartItem($product, $newQty);
        session([self::CART_KEY => $cart]);

        return back()->with('success', 'Produk ditambahkan ke POS cart.');
    }

    public function updateCart(Request $request)
    {
        if (!$this->openShift()) {
            return back()->with('error', 'Buka shift terlebih dahulu sebelum membuat transaksi POS.');
        }

        $validated = $request->validate([
            'product_id' => ['required'],
            'qty' => ['required', 'integer', 'min:1'],
        ]);

        $cart = session(self::CART_KEY, []);
        $productId = (string) $validated['product_id'];

        if (!isset($cart[$productId])) {
            return back()->with('error', 'Produk tidak ada di POS cart.');
        }

        $product = Product::findOrFail($productId);
        $cart[$productId] = $this->cartItem($product, (int) $validated['qty']);
        session([self::CART_KEY => $cart]);

        return back()->with('success', 'Quantity POS cart diperbarui.');
    }

    public function removeFromCart(Request $request)
    {
        if (!$this->openShift()) {
            return back()->with('error', 'Buka shift terlebih dahulu sebelum membuat transaksi POS.');
        }

        $validated = $request->validate([
            'product_id' => ['required'],
        ]);

        $cart = session(self::CART_KEY, []);
        unset($cart[(string) $validated['product_id']]);
        session([self::CART_KEY => $cart]);

        return back()->with('success', 'Produk dihapus dari POS cart.');
    }

    public function clearCart()
    {
        if (!$this->openShift()) {
            return back()->with('error', 'Buka shift terlebih dahulu sebelum membuat transaksi POS.');
        }

        session()->forget(self::CART_KEY);

        return back()->with('success', 'POS cart dikosongkan.');
    }

    public function finalize(Request $request)
    {
        $openShift = $this->openShift();

        if (! $openShift) {
            return back()->with('error', 'Buka shift terlebih dahulu sebelum membuat transaksi POS.');
        }

        $cart = collect(session(self::CART_KEY, []));

        if ($cart->isEmpty()) {
            return back()->with('error', 'POS cart masih kosong.');
        }

        $validated = $request->validate([
            'service_type' => ['required', 'in:dine_in,pickup,local_delivery,shipping'],
            'payment_method' => ['required', 'in:cash,bank_transfer,qris_manual'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'table_number' => ['nullable', 'required_if:service_type,dine_in', 'string', 'max:50'],
            'pickup_time' => ['nullable', 'date'],
            'delivery_address' => ['nullable', 'required_if:service_type,local_delivery', 'required_if:service_type,shipping', 'string', 'max:1000'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $total = (float) $cart->sum('subtotal');
        $paidAmount = (float) ($validated['paid_amount'] ?? 0);

        if ($validated['payment_method'] === 'cash' && $paidAmount < $total) {
            return back()
                ->withInput()
                ->with('error', 'Nominal bayar cash harus minimal sama dengan total.');
        }

        if ($validated['payment_method'] !== 'cash') {
            $paidAmount = $paidAmount > 0 ? $paidAmount : $total;
        }

        $products = Product::with([
            'recipes' => fn ($query) => $query
                ->where('is_active', true)
                ->with('items.item')
                ->latest(),
        ])->whereIn('id', $cart->pluck('product_id'))->get()->keyBy('id');

        if ($products->count() !== $cart->count()) {
            return back()->with('error', 'Ada produk di POS cart yang tidak ditemukan.');
        }

        if ($stockError = $this->stockDeductionError($cart, $products, (int) $openShift->outlet_id)) {
            return back()
                ->withInput()
                ->with('error', $stockError);
        }

        $totalWeight = (int) $cart->sum(function (array $item) use ($products) {
            return (int) ($products[$item['product_id']]->weight ?? 0) * (int) $item['qty'];
        });

        try {
            $order = DB::transaction(function () use ($cart, $products, $validated, $openShift, $total, $paidAmount, $totalWeight) {
                $order = Order::create([
                    'customer_id' => null,
                    'outlet_id' => $openShift->outlet_id,
                    'cashier_shift_id' => $openShift->id,
                    'cashier_id' => auth()->id(),
                    'order_number' => $this->orderNumber($openShift),
                    'order_channel' => 'cashier_pos',
                    'status' => 'paid',
                    'transaction_method' => $validated['service_type'],
                    'service_type' => $validated['service_type'],
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_phone' => $validated['customer_phone'] ?? null,
                    'table_number' => $validated['table_number'] ?? null,
                    'pickup_time' => $validated['pickup_time'] ?? null,
                    'order_source' => 'cashier',
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => 'paid',
                    'subtotal' => $total,
                    'shipping_cost' => 0,
                    'total_weight' => $totalWeight,
                    'total_price' => $total,
                    'address' => $validated['delivery_address'] ?? null,
                    'postal_code' => null,
                    'notes' => null,
                ]);

                foreach ($cart as $item) {
                    $product = $products[$item['product_id']];

                    $orderItem = $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => (int) $item['qty'],
                        'price' => (float) $item['price'],
                        'subtotal' => (float) $item['subtotal'],
                    ]);

                    $this->deductRecipeStock($orderItem, $product, (int) $item['qty'], $openShift, $order->order_number);
                }

                $order->payment()->create([
                    'cashier_shift_id' => $openShift->id,
                    'payment_method' => $validated['payment_method'],
                    'payment_channel' => 'cashier_pos',
                    'payment_status' => 'paid',
                    'paid_amount' => $paidAmount,
                    'change_amount' => $validated['payment_method'] === 'cash' ? $paidAmount - $total : 0,
                    'paid_at' => now(),
                ]);

                if ($validated['payment_method'] === 'cash') {
                    CashierCashMovement::create([
                        'cashier_shift_id' => $openShift->id,
                        'outlet_id' => $openShift->outlet_id,
                        'cashier_id' => $openShift->cashier_id,
                        'type' => 'cash_in',
                        'category' => 'sales_cash',
                        'amount' => $total,
                        'description' => 'POS order payment: ' . $order->order_number,
                    ]);

                    $openShift->refreshExpectedCash();
                }

                return $order;
            });
        } catch (DomainException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Order POS gagal dibuat. Coba ulangi atau hubungi admin.');
        }

        session()->forget(self::CART_KEY);

        return redirect()
            ->route('cashier.receipts.show', $order)
            ->with('success', 'Order POS ' . $order->order_number . ' berhasil dibuat.');
    }

    private function openShift(): ?CashierShift
    {
        return CashierShift::with('outlet')
            ->where('cashier_id', auth()->id())
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    private function cartItem(Product $product, int $qty): array
    {
        $price = (float) $product->price;

        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => $price,
            'qty' => $qty,
            'subtotal' => $price * $qty,
            'image_url' => $product->image_url,
        ];
    }

    private function activeRecipe(Product $product)
    {
        return $product->recipes->first();
    }

    private function stockDeductionError($cart, $products, int $outletId): ?string
    {
        $required = [];
        $missingRecipe = [];

        foreach ($cart as $cartItem) {
            $product = $products[$cartItem['product_id']];
            $recipe = $this->activeRecipe($product);

            if (! $recipe) {
                $missingRecipe[] = $product->name;
                continue;
            }

            foreach ($recipe->items as $recipeItem) {
                $item = $recipeItem->item;

                if (! $item) {
                    continue;
                }

                $itemId = (int) $item->id;
                $required[$itemId]['item'] = $item;
                $required[$itemId]['qty'] = ($required[$itemId]['qty'] ?? 0) + ((float) $recipeItem->effective_qty * (int) $cartItem['qty']);
            }
        }

        if ($missingRecipe) {
            logger()->warning('POS product recipe missing; stock not deducted for those products.', [
                'products' => $missingRecipe,
                'cashier_id' => auth()->id(),
            ]);
        }

        if (! $required) {
            return null;
        }

        if ($outletId <= 0) {
            return 'Shift POS belum memiliki outlet, stok recipe tidak bisa dikurangi.';
        }

        $stocks = InventoryStock::where('outlet_id', $outletId)
            ->whereIn('inventory_item_id', array_keys($required))
            ->get()
            ->keyBy('inventory_item_id');

        foreach ($required as $itemId => $need) {
            $current = (float) ($stocks[$itemId]->current_stock ?? 0);

            if ($current + 0.00001 < $need['qty']) {
                return sprintf(
                    'Stok %s tidak cukup. Dibutuhkan %s %s, tersedia %s %s.',
                    $need['item']->name,
                    rtrim(rtrim(number_format($need['qty'], 4, '.', ''), '0'), '.'),
                    $need['item']->base_unit,
                    rtrim(rtrim(number_format($current, 4, '.', ''), '0'), '.'),
                    $need['item']->base_unit
                );
            }
        }

        return null;
    }

    private function deductRecipeStock(OrderItem $orderItem, Product $product, int $qty, CashierShift $shift, string $orderNumber): void
    {
        $recipe = $this->activeRecipe($product);

        if (! $recipe) {
            return;
        }

        foreach ($recipe->items as $recipeItem) {
            if (! $recipeItem->item) {
                continue;
            }

            app(StockMovementService::class)->decreaseStock([
                'outlet_id' => $shift->outlet_id,
                'inventory_item_id' => $recipeItem->inventory_item_id,
                'quantity' => (float) $recipeItem->effective_qty * $qty,
                'movement_type' => 'pos_sale_out',
                'reference_type' => OrderItem::class,
                'reference_id' => $orderItem->id,
                'user_id' => auth()->id(),
                'notes' => 'POS stock deduction: ' . $orderNumber,
            ]);
        }
    }

    private function orderNumber(CashierShift $shift): string
    {
        $outletCode = $shift->outlet?->code ?: 'POS';

        do {
            $number = 'BL-' . $outletCode . '-POS-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
