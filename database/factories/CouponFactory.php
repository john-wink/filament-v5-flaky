<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => 'CPN-'.fake()->unique()->bothify('??##??'),
            'discount_pct' => fake()->numberBetween(5, 50),
            'product_id' => Product::factory(),
            'expires_at' => now()->addDays(30),
        ];
    }
}
