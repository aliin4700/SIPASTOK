<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Division;
use App\Models\Outlet;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = [
            'super_admin' => ['Super Admin', 'Akses penuh ke seluruh sistem.'],
            'admin' => ['Admin', 'Mengelola setup dan konfigurasi operasional.'],
            'manager' => ['Manager', 'Monitoring dan laporan read-only.'],
            'staff_outlet' => ['Staff Outlet', 'Input operasional outlet sesuai akses outlet dan divisi.'],
            'cashier' => ['Cashier', 'Akses panel POS dan transaksi kasir.'],
            'customer' => ['Customer', 'Akses website customer.'],
        ];

        foreach ($roles as $name => [$displayName, $description]) {
            Role::updateOrCreate(
                ['name' => $name],
                ['display_name' => $displayName, 'description' => $description]
            );
        }

        $permissions = [
            ['dashboard.view', 'View Dashboard', 'general'],
            ['setup.view', 'View Setup', 'setup'],
            ['setup.manage', 'Manage Setup', 'setup'],
            ['users.manage', 'Manage Users', 'setup'],
            ['roles.manage', 'Manage Roles', 'setup'],
            ['outlets.manage', 'Manage Outlets', 'setup'],
            ['divisions.manage', 'Manage Divisions', 'setup'],
            ['cashier.access', 'Access Cashier', 'cashier'],
            ['cashier.open_shift', 'Open Cashier Shift', 'cashier'],
            ['cashier.close_shift', 'Close Cashier Shift', 'cashier'],
            ['cashier.create_order', 'Create POS Order', 'cashier'],
            ['cashier.print_receipt', 'Print Receipt', 'cashier'],
            ['cashier.cash_movement', 'Cash Movement', 'cashier'],
            ['staff.access', 'Access Staff Panel', 'staff'],
            ['stock_opname.input', 'Input Stock Opname', 'inventory'],
            ['waste.input', 'Input Waste', 'inventory'],
            ['purchase_request.create', 'Create Purchase Request', 'purchasing'],
            ['manager.access', 'Access Manager Panel', 'manager'],
            ['reports.view', 'View Reports', 'reports'],
            ['finance.view', 'View Finance', 'finance'],
            ['finance.view_profit', 'View Profit', 'finance'],
            ['finance.manage_target', 'Manage Target', 'finance'],
            ['finance.manage_expense', 'Manage Expense', 'finance'],
            ['finance.export_report', 'Export Finance Report', 'finance'],
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
                'finance.view',
                'finance.view_profit',
                'finance.manage_target',
                'finance.manage_expense',
                'finance.export_report',
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
                'finance.view',
                'finance.view_profit',
                'inventory.view',
                'stock_movement.view',
                'purchasing.view',
            ],
            'cashier' => [
                'cashier.access',
                'cashier.open_shift',
                'cashier.close_shift',
                'cashier.create_order',
                'cashier.print_receipt',
                'cashier.cash_movement',
            ],
            'staff_outlet' => [
                'staff.access',
                'stock_opname.input',
                'waste.input',
                'purchase_request.create',
                'inventory.view',
            ],
            'customer' => [],
        ];

        foreach ($rolePermissionNames as $roleName => $names) {
            $role = Role::where('name', $roleName)->first();
            $role?->permissions()->sync(
                collect($names)->map(fn ($name) => $permissionIds[$name] ?? null)->filter()->values()->all()
            );
        }

        foreach ([
            ['name' => 'Mejasem', 'code' => 'MJS'],
            ['name' => 'Slawi', 'code' => 'SLW'],
        ] as $outlet) {
            Outlet::updateOrCreate(
                ['code' => $outlet['code']],
                ['name' => $outlet['name'], 'is_active' => true]
            );
        }

        foreach ([
            ['name' => 'Kitchen', 'code' => 'KIT'],
            ['name' => 'Pastry', 'code' => 'PST'],
            ['name' => 'Bakery', 'code' => 'BKR'],
            ['name' => 'Bar', 'code' => 'BAR'],
            ['name' => 'Floor', 'code' => 'FLR'],
            ['name' => 'Packaging', 'code' => 'PCK'],
        ] as $division) {
            Division::updateOrCreate(
                ['code' => $division['code']],
                ['name' => $division['name'], 'is_active' => true]
            );
        }

        User::updateOrCreate(['email' => 'admin@admin.com'], [
            'name' => 'Super Administrator',
            'role' => 'super_admin',
            'status' => 'active',
            'phone' => '0812345678901',
            'password' => Hash::make('P@55word'),
        ]);

        User::updateOrCreate(['email' => 'sopianaji@admin.com'], [
            'name' => 'Admin BreadLab',
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0812345678902',
            'password' => Hash::make('P@55word'),
        ]);

        $customerUser = User::updateOrCreate(['email' => 'customer@breadlab.test'], [
            'name' => 'Customer BreadLab',
            'role' => 'customer',
            'status' => 'active',
            'phone' => '0812345678903',
            'password' => Hash::make('P@55word'),
        ]);

        Customer::updateOrCreate(['user_id' => $customerUser->id], [
            'address' => 'Jl. BreadLab No. 1',
            'postal_code' => '40123',
        ]);

        collect(['Brownies', 'Combro', 'Dawet', 'Mochi', 'Wingko'])->each(function (string $name) {
            Category::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'status' => 'active',
            ]);
        });

        $brownies = Category::where('slug', 'brownies')->first();
        $mochi = Category::where('slug', 'mochi')->first();

        Product::updateOrCreate(['slug' => 'brownies-cokelat'], [
            'category_id' => $brownies?->id,
            'name' => 'Brownies Cokelat',
            'description' => 'Brownies lembut dengan rasa cokelat pekat.',
            'price' => 45000,
            'stock' => 20,
            'weight' => 500,
            'status' => 'published',
        ]);

        Product::updateOrCreate(['slug' => 'mochi-kacang'], [
            'category_id' => $mochi?->id,
            'name' => 'Mochi Kacang',
            'description' => 'Mochi isi kacang dengan tekstur lembut.',
            'price' => 30000,
            'stock' => 30,
            'weight' => 250,
            'status' => 'published',
        ]);
    }
}
