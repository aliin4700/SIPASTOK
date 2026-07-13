<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD address book customer untuk checkout shipping.
 */
class CustomerAddressController extends Controller
{
    public function index(Request $request)
    {
        $customer = $this->customer($request);

        return view('v_customer.address.index', [
            'judul' => 'Daftar Alamat',
            'addresses' => $customer->addresses()->latest('is_default')->latest()->get(),
        ]);
    }

    public function create()
    {
        return view('v_customer.address.create', [
            'judul' => 'Tambah Alamat',
            'address' => new CustomerAddress(),
        ]);
    }

    public function store(Request $request)
    {
        $customer = $this->customer($request);
        $validated = $this->validateAddress($request);

        // Alamat pertama otomatis menjadi default agar checkout langsung punya alamat utama.
        DB::transaction(function () use ($customer, $validated) {
            $isFirstAddress = ! $customer->addresses()->exists();
            $isDefault = (bool) ($validated['is_default'] ?? false) || $isFirstAddress;

            if ($isDefault) {
                $customer->addresses()->update(['is_default' => false]);
            }

            $customer->addresses()->create(array_merge($validated, [
                'is_default' => $isDefault,
            ]));
        });

        return redirect()->route('customer.address.index')->with('success', 'Alamat berhasil ditambahkan.');
    }

    public function edit(Request $request, CustomerAddress $address)
    {
        $this->authorizeAddress($request, $address);

        return view('v_customer.address.edit', [
            'judul' => 'Edit Alamat',
            'address' => $address,
        ]);
    }

    public function update(Request $request, CustomerAddress $address)
    {
        $customer = $this->customer($request);
        $this->authorizeAddress($request, $address);
        $validated = $this->validateAddress($request);

        // Bila alamat ini dijadikan default, alamat lain milik customer dinonaktifkan default-nya.
        DB::transaction(function () use ($customer, $address, $validated) {
            $isDefault = (bool) ($validated['is_default'] ?? false);

            if ($isDefault) {
                $customer->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }

            $address->update(array_merge($validated, [
                'is_default' => $isDefault || $address->is_default,
            ]));
        });

        return redirect()->route('customer.address.index')->with('success', 'Alamat berhasil diperbarui.');
    }

    public function destroy(Request $request, CustomerAddress $address)
    {
        $customer = $this->customer($request);
        $this->authorizeAddress($request, $address);
        $wasDefault = $address->is_default;

        // Jika alamat default dihapus, pilih alamat terbaru sebagai pengganti.
        DB::transaction(function () use ($customer, $address, $wasDefault) {
            $address->delete();

            if ($wasDefault) {
                $replacement = $customer->addresses()->latest()->first();
                $replacement?->update(['is_default' => true]);
            }
        });

        return redirect()->route('customer.address.index')->with('success', 'Alamat berhasil dihapus.');
    }

    public function setDefault(Request $request, CustomerAddress $address)
    {
        $customer = $this->customer($request);
        $this->authorizeAddress($request, $address);

        DB::transaction(function () use ($customer, $address) {
            $customer->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return redirect()->route('customer.address.index')->with('success', 'Alamat utama berhasil diperbarui.');
    }

    private function validateAddress(Request $request): array
    {
        // Province/city/district name tetap divalidasi bersama id untuk snapshot alamat checkout.
        return $request->validate([
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_phone' => ['required', 'string', 'max:30'],
            'province_id' => ['required', 'string', 'max:50'],
            'province_name' => ['required', 'string', 'max:255'],
            'city_id' => ['required', 'string', 'max:50'],
            'city_name' => ['required', 'string', 'max:255'],
            'district_id' => ['required', 'string', 'max:50'],
            'district_name' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'full_address' => ['required', 'string', 'max:1000'],
            'detail_address' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'label' => ['nullable', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }

    private function customer(Request $request): Customer
    {
        return Customer::firstOrCreate([
            'user_id' => $request->user()->id,
        ]);
    }

    private function authorizeAddress(Request $request, CustomerAddress $address): void
    {
        // Customer tidak boleh mengubah alamat milik customer lain.
        abort_unless((int) $address->customer_id === (int) $this->customer($request)->id, 403);
    }
}
