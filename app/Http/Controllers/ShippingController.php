<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Customer;
use App\Services\RajaOngkirService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ShippingController extends Controller
{
    public function __construct(private RajaOngkirService $rajaOngkir)
    {
    }

    public function provinces(): JsonResponse
    {
        try {
            return response()->json([
                'data' => $this->rajaOngkir->provinces(),
            ]);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage());
        }
    }

    public function cities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'province_id' => ['required', 'integer'],
        ]);

        try {
            return response()->json([
                'data' => $this->rajaOngkir->cities($validated['province_id']),
            ]);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage());
        }
    }

    public function costs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'integer'],
            'courier' => ['required', 'in:jne,jnt,sicepat,pos'],
        ]);

        $cart = Cart::with('items.product')->firstOrCreate([
            'customer_id' => Customer::firstOrCreate(['user_id' => auth()->id()])->id,
        ]);

        try {
            return response()->json([
                'data' => $this->rajaOngkir->costs(
                    $validated['city_id'],
                    $validated['courier'],
                    $this->cartWeight($cart)
                ),
            ]);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage());
        }
    }

    private function cartWeight(Cart $cart): int
    {
        return max(1, (int) $cart->items->sum(function ($item) {
            $weight = (int) ($item->product->getAttribute('weight') ?: 250);

            return $weight * $item->qty;
        }));
    }

    private function error(string $message): JsonResponse
    {
        return response()->json([
            'message' => RajaOngkirService::UNAVAILABLE_MESSAGE,
        ], 502);
    }
}
