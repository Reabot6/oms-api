<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $vendorRole = Role::where('slug', 'vendor')->first();
        $customerRole = Role::where('slug', 'customer')->first();

        // Create admin user
        User::create([
            'role_id' => $adminRole->id,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'phone' => '+1234567890',
            'address' => '123 Admin St, City, Country',
            'is_active' => true,
        ]);

        // Create vendor users
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'role_id' => $vendorRole->id,
                'name' => "Vendor User $i",
                'email' => "vendor$i@example.com",
                'password' => Hash::make('password'),
                'phone' => "+123456789$i",
                'address' => "$i Vendor St, City, Country",
                'is_active' => true,
            ]);
        }

        // Create customer users
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'role_id' => $customerRole->id,
                'name' => "Customer User $i",
                'email' => "customer$i@example.com",
                'password' => Hash::make('password'),
                'phone' => "+123456780$i",
                'address' => "$i Customer St, City, Country",
                'is_active' => true,
            ]);
        }
    }
}
