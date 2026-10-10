<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

namespace Database\Factories;

use App\Models\User;
use App\Models\Inventory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'vendor_id' => User::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 1000),
            'sku' => 'SKU-' . strtoupper(Str::random(8)),
            'is_active' => true,
        ];
    }
    
    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function ($product) {
            Inventory::create([
                'product_id' => $product->id,
                'quantity' => 100,
                'reserved_quantity' => 0,
                'low_stock_threshold' => 10,
            ]);
        });
    }
}
