<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Design>
 */
class DesignFactory extends Factory
{
    protected $model = Design::class;

    public function definition(): array
    {
        return [
            'designer_id' => DesignerProfile::factory(),
            'category_id' => Category::factory(),
            'title' => fake()->words(3, true),
            'status' => 'draft',
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => 'published']);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => 'archived']);
    }

    public function forDesigner(DesignerProfile $designer): static
    {
        return $this->state(fn () => ['designer_id' => $designer->id]);
    }
}
