<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Outlet;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDivisionAccess;
use App\Models\UserOutletAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Role default SIPASTOK mengikuti users.role sebagai source of truth.
        $roles = [
            'super_admin' => ['Super Admin', 'Akses penuh ke seluruh sistem SIPASTOK.'],
            'admin' => ['Admin', 'Mengelola setup, master bahan, dan verifikasi stok opname.'],
            'manager' => ['Manager', 'Monitoring operasional outlet, audit IRA, dan approval stok opname.'],
            'staff_outlet' => ['Staff Outlet', 'Input operasional harian dan stok opname outlet sesuai akses divisi.'],
        ];

        foreach ($roles as $name => [$displayName, $description]) {
            Role::updateOrCreate(
                ['name' => $name],
                ['display_name' => $displayName, 'description' => $description]
            );
        }

        // Permission menjadi referensi akses modul SIPASTOK.
        $permissions = [
            ['dashboard.view', 'View Dashboard', 'general'],
            ['setup.view', 'View Setup', 'setup'],
            ['setup.manage', 'Manage Setup', 'setup'],
            ['users.manage', 'Manage Users', 'setup'],
            ['roles.manage', 'Manage Roles', 'setup'],
            ['outlets.manage', 'Manage Outlets', 'setup'],
            ['divisions.manage', 'Manage Divisions', 'setup'],
            ['staff.access', 'Access Staff Panel', 'staff'],
            ['stock_opname.input', 'Input Stock Opname', 'inventory'],
            ['waste.input', 'Input Waste', 'inventory'],
            ['purchase_request.create', 'Create Purchase Request', 'purchasing'],
            ['manager.access', 'Access Manager Panel', 'manager'],
            ['reports.view', 'View Reports', 'reports'],
            ['inventory.view', 'View Inventory', 'inventory'],
            ['inventory.manage', 'Manage Inventory', 'inventory'],
            ['stock_movement.view', 'View Stock Movement', 'inventory'],
            ['purchasing.view', 'View Purchasing', 'purchasing'],
            ['purchasing.manage', 'Manage Purchasing', 'purchasing'],
        ];

        foreach ($permissions as [$name, $displayName, $module]) {
            Permission::updateOrCreate(
                ['name' => $name],
                ['display_name' => $displayName, 'module' => $module]
            );
        }

        // Mapping role-permission dibuat idempotent agar seeder aman dijalankan ulang.
        $permissionIds = Permission::pluck('id', 'name');
        $rolePermissionNames = [
            'super_admin' => $permissionIds->keys()->all(),
            'admin' => [
                'dashboard.view',
                'setup.view',
                'setup.manage',
                'users.manage',
                'roles.manage',
                'outlets.manage',
                'divisions.manage',
                'reports.view',
                'inventory.view',
                'inventory.manage',
                'stock_movement.view',
                'purchasing.view',
                'purchasing.manage',
            ],
            'manager' => [
                'dashboard.view',
                'manager.access',
                'reports.view',
                'inventory.view',
                'stock_movement.view',
                'purchasing.view',
            ],
            'staff_outlet' => [
                'staff.access',
                'stock_opname.input',
                'waste.input',
                'purchase_request.create',
                'inventory.view',
            ],
        ];

        foreach ($rolePermissionNames as $roleName => $names) {
            $role = Role::where('name', $roleName)->first();
            $role?->permissions()->sync(
                collect($names)->map(fn ($name) => $permissionIds[$name] ?? null)->filter()->values()->all()
            );
        }

        // Outlet dan divisi Medja Cerita yang dipakai untuk operasional & stok opname.
        foreach ([
            ['name' => 'Mejasem', 'code' => 'MJS'],
            ['name' => 'Slawi', 'code' => 'SLW'],
            ['name' => 'Kagok CK', 'code' => 'KGK'],
        ] as $outlet) {
            Outlet::updateOrCreate(
                ['code' => $outlet['code']],
                ['name' => $outlet['name'], 'is_active' => true]
            );
        }

        foreach ([
            ['name' => 'Kitchen', 'code' => 'KIT'],
            ['name' => 'Bar', 'code' => 'BAR'],
            ['name' => 'Corner', 'code' => 'CRN'],
            ['name' => 'Floor', 'code' => 'FLR'],
            ['name' => 'Pastry', 'code' => 'PST'],
            ['name' => 'Packaging', 'code' => 'PKG'],
            ['name' => 'Central Kitchen', 'code' => 'CK'],
        ] as $division) {
            Division::updateOrCreate(
                ['code' => $division['code']],
                ['name' => $division['name'], 'is_active' => true]
            );
        }

        // Akun internal SIPASTOK untuk demonstrasi & operasional 3 role.
        User::updateOrCreate(['email' => 'admin@admin.com'], [
            'name' => 'Super Administrator SIPASTOK',
            'role' => 'super_admin',
            'status' => 'active',
            'phone' => '0812345678901',
            'password' => Hash::make('P@55word'),
        ]);

        User::updateOrCreate(['email' => 'sopianaji@admin.com'], [
            'name' => 'Admin SIPASTOK Medja Cerita',
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0812345678902',
            'password' => Hash::make('P@55word'),
        ]);

        $managerUser = User::updateOrCreate(['email' => 'manager@medjacerita.com'], [
            'name' => 'Manager Operasional Medja Cerita',
            'role' => 'manager',
            'status' => 'active',
            'phone' => '0812345678904',
            'password' => Hash::make('P@55word'),
        ]);

        foreach (Outlet::where('is_active', true)->get() as $activeOutlet) {
            UserOutletAccess::updateOrCreate(
                ['user_id' => $managerUser->id, 'outlet_id' => $activeOutlet->id],
                [
                    'can_monitor' => true,
                    'can_manage_stock' => true,
                    'can_manage_purchase' => true,
                ]
            );
        }

        $staffUser = User::updateOrCreate(['email' => 'staff@medjacerita.com'], [
            'name' => 'Staff Kitchen Mejasem',
            'role' => 'staff_outlet',
            'status' => 'active',
            'phone' => '0812345678905',
            'password' => Hash::make('P@55word'),
        ]);

        $mejasemOutlet = Outlet::where('code', 'MJS')->first();
        $kitchenDivision = Division::where('code', 'KIT')->first();

        if ($mejasemOutlet && $kitchenDivision) {
            UserDivisionAccess::updateOrCreate(
                [
                    'user_id' => $staffUser->id,
                    'outlet_id' => $mejasemOutlet->id,
                    'division_id' => $kitchenDivision->id,
                ],
                [
                    'can_input_stock' => true,
                    'can_input_waste' => true,
                    'can_request_order' => true,
                ]
            );
        }
    }
}
