<?php

namespace Tests\Feature;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SimulationAdminRoleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_purchase_order_and_goods_receipt_simulation_on_mejasem(): void
    {
        // 1. Login sebagai Admin / Backend & identifikasi outlet Mejasem + item sampel
        $admin = User::whereIn('role', ['super_admin', 'admin'])->orderBy('id')->firstOrFail();
        $mejasem = Outlet::where('name', 'like', '%Mejasem%')->firstOrFail();
        $supplier = Supplier::where('is_active', true)->first();

        $strawberry = InventoryItem::whereRaw('UPPER(name) = ?', ['FROZEN STRAWBERRY'])
            ->orWhereRaw('UPPER(esb_item_name) = ?', ['FROZEN STRAWBERRY'])
            ->firstOrFail();

        $gulaPasir = InventoryItem::whereRaw('UPPER(name) = ?', ['GULA PASIR'])
            ->orWhereRaw('UPPER(esb_item_name) = ?', ['GULA PASIR'])
            ->firstOrFail();

        $qtyOrder = 5000.0;

        $beforeStrawberry = (float) (InventoryStock::where('outlet_id', $mejasem->id)
            ->where('inventory_item_id', $strawberry->id)
            ->value('current_stock') ?? 0);

        $beforeGula = (float) (InventoryStock::where('outlet_id', $mejasem->id)
            ->where('inventory_item_id', $gulaPasir->id)
            ->value('current_stock') ?? 0);

        $strawberryCost = (float) ($strawberry->unit_cost > 0 ? $strawberry->unit_cost : ($strawberry->last_purchase_price ?? 0));
        $gulaCost = (float) ($gulaPasir->unit_cost > 0 ? $gulaPasir->unit_cost : ($gulaPasir->last_purchase_price ?? 0));

        // 2. Buat & Submit/Approve Purchase Order (PO) melalui rute Backend
        $poResponse = $this->actingAs($admin)->post(route('backend.purchase-orders.store'), [
            'supplier_id' => $supplier?->id,
            'outlet_id' => $mejasem->id,
            'order_date' => now()->toDateString(),
            'expected_date' => now()->addDay()->toDateString(),
            'notes' => 'Simulasi PO Admin - Outlet Mejasem',
            'items' => [
                [
                    'inventory_item_id' => $strawberry->id,
                    'ordered_qty' => $qtyOrder,
                    'unit' => 'GR',
                    'unit_cost' => $strawberryCost,
                ],
                [
                    'inventory_item_id' => $gulaPasir->id,
                    'ordered_qty' => $qtyOrder,
                    'unit' => 'GR',
                    'unit_cost' => $gulaCost,
                ],
            ],
        ]);

        $poResponse->assertRedirect();
        $poResponse->assertSessionHas('success');

        $po = PurchaseOrder::with('items')->latest('id')->firstOrFail();
        $this->assertSame('draft', $po->status);
        $this->assertSame($mejasem->id, (int) $po->outlet_id);
        $this->assertCount(2, $po->items);

        // Submit / Approve PO agar siap diterima di Goods Receipt
        $poSubmitResponse = $this->actingAs($admin)->post(route('backend.purchase-orders.submit', $po));
        $poSubmitResponse->assertRedirect();
        $poSubmitResponse->assertSessionHas('success');

        $po->refresh();
        $this->assertSame('submitted', $po->status);

        $poItemStrawberry = $po->items->firstWhere('inventory_item_id', $strawberry->id);
        $poItemGula = $po->items->firstWhere('inventory_item_id', $gulaPasir->id);
        $this->assertNotNull($poItemStrawberry);
        $this->assertNotNull($poItemGula);

        // 3. Buat Goods Receipt dari PO, Submit, dan Apply Stock ke Outlet Mejasem
        $grResponse = $this->actingAs($admin)->post(route('backend.goods-receipts.store'), [
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier?->id,
            'outlet_id' => $mejasem->id,
            'receipt_date' => now()->toDateString(),
            'notes' => 'Simulasi Goods Receipt dari ' . $po->po_number,
            'items' => [
                [
                    'purchase_order_item_id' => $poItemStrawberry->id,
                    'inventory_item_id' => $strawberry->id,
                    'received_qty' => $qtyOrder,
                    'unit' => 'GR',
                    'unit_cost' => $strawberryCost,
                ],
                [
                    'purchase_order_item_id' => $poItemGula->id,
                    'inventory_item_id' => $gulaPasir->id,
                    'received_qty' => $qtyOrder,
                    'unit' => 'GR',
                    'unit_cost' => $gulaCost,
                ],
            ],
        ]);

        $grResponse->assertRedirect();
        $grResponse->assertSessionHas('success');

        $gr = GoodsReceipt::with('items')->latest('id')->firstOrFail();
        $this->assertSame('draft', $gr->status);
        $this->assertSame($po->id, (int) $gr->purchase_order_id);

        $this->actingAs($admin)
            ->post(route('backend.goods-receipts.submit', $gr))
            ->assertRedirect()
            ->assertSessionHas('success');

        $gr->refresh();
        $this->assertSame('submitted', $gr->status);

        $this->actingAs($admin)
            ->post(route('backend.goods-receipts.apply-stock', $gr))
            ->assertRedirect()
            ->assertSessionHas('success');

        // 4. Verifikasi Status Akhir, Mutasi Stok, dan Saldo Bertambah +5.000 GR
        $gr->refresh();
        $po->refresh();

        $this->assertSame('applied', $gr->status);
        $this->assertSame('received', $po->status);

        $afterStrawberry = (float) InventoryStock::where('outlet_id', $mejasem->id)
            ->where('inventory_item_id', $strawberry->id)
            ->value('current_stock');

        $afterGula = (float) InventoryStock::where('outlet_id', $mejasem->id)
            ->where('inventory_item_id', $gulaPasir->id)
            ->value('current_stock');

        $this->assertEquals(round($beforeStrawberry + $qtyOrder, 2), round($afterStrawberry, 2));
        $this->assertEquals(round($beforeGula + $qtyOrder, 2), round($afterGula, 2));

        $movementCount = StockMovement::where('reference_type', GoodsReceiptItem::class)
            ->whereIn('reference_id', $gr->items->pluck('id'))
            ->where('movement_type', 'purchase_in')
            ->count();
        $this->assertSame(2, $movementCount);

        // Cetak log tabel angka sebelum vs sesudah ke console
        $log = "\n"
            . "====================================================================================\n"
            . "  SIMULASI ROLE ADMIN / BACKEND — PO & GOODS RECEIPT (OUTLET: {$mejasem->name})\n"
            . "====================================================================================\n"
            . sprintf("  Admin   : %s (%s)\n", $admin->name, $admin->email)
            . sprintf("  PO      : %s  [Status Akhir: %s]\n", $po->po_number, strtoupper($po->status))
            . sprintf("  GR      : %s  [Status Akhir: %s]\n", $gr->receipt_number, strtoupper($gr->status))
            . "------------------------------------------------------------------------------------\n"
            . sprintf("  %-22s | %12s | %12s | %10s | %12s\n", 'Item', 'Unit Cost', 'Stok Awal', 'Masuk (+)', 'Stok Akhir')
            . "------------------------------------------------------------------------------------\n"
            . sprintf(
                "  %-22s | Rp %9s | %9s GR | +%6s GR | %9s GR\n",
                $strawberry->name,
                number_format($strawberryCost, 2, ',', '.'),
                number_format($beforeStrawberry, 2, ',', '.'),
                number_format($qtyOrder, 0, ',', '.'),
                number_format($afterStrawberry, 2, ',', '.')
            )
            . sprintf(
                "  %-22s | Rp %9s | %9s GR | +%6s GR | %9s GR\n",
                $gulaPasir->name,
                number_format($gulaCost, 2, ',', '.'),
                number_format($beforeGula, 2, ',', '.'),
                number_format($qtyOrder, 0, ',', '.'),
                number_format($afterGula, 2, ',', '.')
            )
            . "====================================================================================\n";

        fwrite(STDOUT, $log);
    }
}
