<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            'minimal', 'vintage', 'retro', 'geometric', 'abstract',
            'nature', 'floral', 'animal', 'typography', 'quote',
            'monochrome', 'colorful', 'pastel', 'neon', 'gradient',
            'motivational', 'funny', 'cute', 'dark', 'boho',
            'kawaii', 'skull', 'space', 'sketch', 'watercolor',
        ];

        foreach ($tags as $name) {
            Tag::factory()->create(['name' => $name]);
        }
    }
}
