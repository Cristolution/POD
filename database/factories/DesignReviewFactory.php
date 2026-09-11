<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Design;
use App\Models\DesignReview;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DesignReview>
 */
class DesignReviewFactory extends Factory
{
    protected $model = DesignReview::class;

    public function definition(): array
    {
        return [
            'design_id' => Design::factory(),
            'customer_id' => User::factory()->customer(),
            'order_id' => null,
            'rating' => fake()->numberBetween(DesignReview::MIN_RATING, DesignReview::MAX_RATING),
            'title' => fake()->boolean(60) ? fake()->sentence(4) : null,
            'body' => fake()->boolean(70) ? fake()->paragraph() : null,
            'is_approved' => true,
            'approved_at' => fn () => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'is_approved' => true,
            'approved_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'is_approved' => false,
            'approved_at' => null,
        ]);
    }

    public function forOrder(?Order $order = null): static
    {
        return $this->state(fn () => [
            'order_id' => $order?->id ?? Order::factory(),
        ]);
    }

    public function rating(int $rating): static
    {
        return $this->state(fn () => [
            'rating' => max(DesignReview::MIN_RATING, min(DesignReview::MAX_RATING, $rating)),
        ]);
    }
}
