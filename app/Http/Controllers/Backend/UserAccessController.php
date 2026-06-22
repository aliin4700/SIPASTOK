<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Outlet;
use App\Models\User;
use App\Models\UserDivisionAccess;
use App\Models\UserOutletAccess;
use Illuminate\Http\Request;

class UserAccessController extends Controller
{
    public function edit(User $user)
    {
        $this->ensureCanManage($user);

        $user->load(['outletAccesses', 'divisionAccesses']);

        return view('backend.user.access', [
            'judul' => 'Kelola Akses User',
            'user' => $user,
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'outletAccessMap' => $user->outletAccesses->keyBy('outlet_id'),
            'divisionAccessMap' => $user->divisionAccesses->keyBy(fn ($access) => $access->outlet_id . '-' . $access->division_id),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureCanManage($user);

        $request->validate([
            'outlet_access' => ['nullable', 'array'],
            'outlet_access.*' => ['array'],
            'division_access' => ['nullable', 'array'],
            'division_access.*' => ['array'],
        ]);

        $outlets = Outlet::where('is_active', true)->get();
        $divisions = Division::where('is_active', true)->get();

        foreach ($outlets as $outlet) {
            $values = $request->input("outlet_access.{$outlet->id}", []);
            $access = [
                'can_monitor' => isset($values['can_monitor']),
                'can_cashier' => isset($values['can_cashier']),
                'can_manage_stock' => isset($values['can_manage_stock']),
                'can_manage_purchase' => isset($values['can_manage_purchase']),
                'can_manage_finance' => isset($values['can_manage_finance']),
            ];

            if (in_array(true, $access, true)) {
                UserOutletAccess::updateOrCreate(
                    ['user_id' => $user->id, 'outlet_id' => $outlet->id],
                    $access
                );
            } else {
                UserOutletAccess::where('user_id', $user->id)
                    ->where('outlet_id', $outlet->id)
                    ->delete();
            }
        }

        foreach ($outlets as $outlet) {
            foreach ($divisions as $division) {
                $values = $request->input("division_access.{$outlet->id}.{$division->id}", []);
                $access = [
                    'can_input_stock' => isset($values['can_input_stock']),
                    'can_input_waste' => isset($values['can_input_waste']),
                    'can_request_order' => isset($values['can_request_order']),
                ];

                if (in_array(true, $access, true)) {
                    UserDivisionAccess::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'outlet_id' => $outlet->id,
                            'division_id' => $division->id,
                        ],
                        $access
                    );
                } else {
                    UserDivisionAccess::where('user_id', $user->id)
                        ->where('outlet_id', $outlet->id)
                        ->where('division_id', $division->id)
                        ->delete();
                }
            }
        }

        return redirect()
            ->route('backend.user.access.edit', $user)
            ->with('success', 'Akses user berhasil disimpan');
    }

    private function ensureCanManage(User $user): void
    {
        $authUser = auth()->user();

        abort_unless($authUser?->hasAnyRole(['super_admin', 'admin']), 403);

        if ($user->hasRole('super_admin') && ! $authUser->hasRole('super_admin')) {
            abort(403, 'Hanya super admin yang dapat mengelola akses super admin.');
        }
    }
}
