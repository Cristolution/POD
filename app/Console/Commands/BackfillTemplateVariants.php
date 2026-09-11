<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ProductTemplate;
use App\Models\ProductVariant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * One-shot backfill: generate ProductVariant rows for any ProductTemplate
 * that currently has zero variants. Some templates were created via the
 * admin panel after the original seeder ran and never got variants, which
 * leaves the storefront "Variant" select empty for those products.
 *
 * Pure addition — does not touch existing rows, designs, or mappings.
 */
#[Signature('variants:backfill-empty-templates
    {--min=2 : Minimum variants to generate per template}
    {--max=4 : Maximum variants to generate per template}')]
#[Description('Generate ProductVariant rows for templates that have none.')]
class BackfillTemplateVariants extends Command
{
    public function handle(): int
    {
        $min = max(1, (int) $this->option('min'));
        $max = max($min, (int) $this->option('max'));

        $orphans = ProductTemplate::query()
            ->whereDoesntHave('variants')
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('Every template already has at least one variant. Nothing to do.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d template(s) with zero variants:', $orphans->count()));

        $created = 0;
        foreach ($orphans as $template) {
            $count = random_int($min, $max);
            $this->line("  - {$template->type} (id={$template->id}) → generating {$count} variants");

            for ($i = 0; $i < $count; $i++) {
                ProductVariant::factory()->create([
                    'product_template_id' => $template->id,
                ]);
                $created++;
            }
        }

        $this->newLine();
        $this->info("Created {$created} variant(s) across {$orphans->count()} template(s).");

        return self::SUCCESS;
    }
}
