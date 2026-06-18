<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
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
        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@admin.com',
            'role' => 'super_admin',
            'status' => 'active',
            'phone' => '0812345678901',
            'password' => Hash::make('P@55word'),
        ]);

        User::create([
            'name' => 'Admin BreadLab',
            'email' => 'sopianaji@admin.com',
            'role' => 'admin',
            'status' => 'active',
            'phone' => '0812345678902',
            'password' => Hash::make('P@55word'),
        ]);

        $customerUser = User::create([
            'name' => 'Customer BreadLab',
            'email' => 'customer@breadlab.test',
            'role' => 'customer',
            'status' => 'active',
            'phone' => '0812345678903',
            'password' => Hash::make('P@55word'),
        ]);

        Customer::create([
            'user_id' => $customerUser->id,
            'address' => 'Jl. BreadLab No. 1',
            'postal_code' => '40123',
        ]);

        collect(['Brownies', 'Combro', 'Dawet', 'Mochi', 'Wingko'])->each(function (string $name) {
            Category::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'status' => 'active',
            ]);
        });

        $brownies = Category::where('slug', 'brownies')->first();
        $mochi = Category::where('slug', 'mochi')->first();

        Product::create([
            'category_id' => $brownies?->id,
            'name' => 'Brownies Cokelat',
            'slug' => 'brownies-cokelat',
            'description' => 'Brownies lembut dengan rasa cokelat pekat.',
            'price' => 45000,
            'stock' => 20,
            'weight' => 500,
            'status' => 'published',
        ]);

        Product::create([
            'category_id' => $mochi?->id,
            'name' => 'Mochi Kacang',
            'slug' => 'mochi-kacang',
            'description' => 'Mochi isi kacang dengan tekstur lembut.',
            'price' => 30000,
            'stock' => 30,
            'weight' => 250,
            'status' => 'published',
        ]);
    }
}
