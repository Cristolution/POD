<?php

namespace Database\Factories;

use App\Models\Design;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'model_type' => Design::class,
            'model_id' => Design::factory(),
            'collection_name' => 'mockup',
            'file_path' => 'media/'.fake()->uuid().'.jpg',
        ];
    }

    public function mockup(): static
    {
        return $this->state(fn () => [
            'collection_name' => 'mockup',
            'product_template_id' => null,
        ]);
    }

    public function productMockup(int|string|null $productTemplateId = null): static
    {
        return $this->state(fn () => [
            'collection_name' => 'mockup',
            'product_template_id' => $productTemplateId,
        ]);
    }

    public function printFile(): static
    {
        return $this->state(fn () => ['collection_name' => 'print_file']);
    }

    public function paymentProof(): static
    {
        return $this->state(fn () => ['collection_name' => 'payment_proof']);
    }

    public function attachment(): static
    {
        return $this->state(fn () => ['collection_name' => 'attachment']);
    }
}
