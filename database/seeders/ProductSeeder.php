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

use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendors = User::whereHas('role', function ($query) {
            $query->where('slug', 'vendor');
        })->get();

        $products = [
            ['name' => 'Laptop Computer', 'description' => 'High-performance laptop for professional use', 'price' => 999.99],
            ['name' => 'Wireless Mouse', 'description' => 'Ergonomic wireless mouse with long battery life', 'price' => 29.99],
            ['name' => 'Mechanical Keyboard', 'description' => 'RGB mechanical keyboard with cherry switches', 'price' => 149.99],
            ['name' => 'USB-C Hub', 'description' => 'Multi-port USB-C hub with HDMI and ethernet', 'price' => 79.99],
            ['name' => 'External SSD 1TB', 'description' => 'Fast portable SSD storage drive', 'price' => 129.99],
            ['name' => 'Webcam HD', 'description' => '1080p webcam with built-in microphone', 'price' => 69.99],
            ['name' => 'Monitor 27"', 'description' => '4K UHD monitor with IPS panel', 'price' => 399.99],
            ['name' => 'Desk Lamp LED', 'description' => 'Adjustable LED desk lamp with USB charging', 'price' => 39.99],
            ['name' => 'Office Chair', 'description' => 'Ergonomic office chair with lumbar support', 'price' => 249.99],
            ['name' => 'Smartphone', 'description' => 'Latest smartphone with 5G connectivity', 'price' => 799.99],
            ['name' => 'Tablet 10"', 'description' => 'Portable tablet with stylus support', 'price' => 449.99],
            ['name' => 'Wireless Earbuds', 'description' => 'Noise-cancelling wireless earbuds', 'price' => 179.99],
            ['name' => 'Power Bank 20000mAh', 'description' => 'High-capacity portable charger', 'price' => 49.99],
            ['name' => 'Phone Case', 'description' => 'Protective phone case with card holder', 'price' => 19.99],
            ['name' => 'Screen Protector', 'description' => 'Tempered glass screen protector', 'price' => 9.99],
        ];

        foreach ($products as $index => $productData) {
            $vendor = $vendors[$index % $vendors->count()];

            $product = Product::create([
                'vendor_id' => $vendor->id,
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name']),
                'description' => $productData['description'],
                'price' => $productData['price'],
                'sku' => 'SKU-' . strtoupper(Str::random(8)),
                'is_active' => true,
            ]);

            // Create inventory for each product
            Inventory::create([
                'product_id' => $product->id,
                'quantity' => rand(20, 100),
                'reserved_quantity' => 0,
                'low_stock_threshold' => 10,
                'last_restocked_at' => now(),
            ]);
        }
    }
}
