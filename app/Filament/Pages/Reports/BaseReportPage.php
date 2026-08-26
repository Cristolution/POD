<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Reports\Contracts\Report;
use App\Reports\CsvExporter;
use Filament\Pages\Page;
use Filament\Widgets\ChartWidget;
use Illuminate\Http\Request;
use UnitEnum;

/**
 * Base Filament Page for rendering a Phase 2 Report contract as a brutalist table
 * with CSV export and date-range filtering.
 *
 * Subclasses must implement {@see reportClass()} to point at a concrete Report class.
 *
 * Filter values are bound directly via raw HTML inputs with `wire:model="data.X"` in
 * the shared Blade view — that sidesteps the v3 → v4 Forms facade, which no longer
 * ships `renderHeader()`.
 *
 * NOTE: PHP 8.5 strictly requires overriding static properties with the EXACT same
 * type declared in the parent. {@see Page}'s typed properties
 * (`$navigationGroup`, `$navigationIcon`) use union types — so subclasses must
 * redeclare them with the matching union, not with a narrower `?string` form.
 */
abstract class BaseReportPage extends Page
{
    protected string $view = 'filament.pages.reports.base';

    /** All report pages share the "Reports" navigation group. */
    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    /**
     * Livewire-bound filter values: 'from' (Y-m-d), 'to' (Y-m-d).
     *
     * @var array<string, string|null>
     */
    public array $data = [];

    /** Class-string of the Phase 2 Report contract implementation. */
    abstract public function reportClass(): string;

    public function mount(): void
    {
        $this->data = [
            'from' => now()->subDays(30)->toDateString(),
            'to' => now()->toDateString(),
        ];
    }

    public function exportCsv()
    {
        /** @var Report $report */
        $report = app($this->reportClass());

        return app(CsvExporter::class)->stream(
            $report->csvHeaders(),
            $report->run($this->buildRequest()),
            $this->getSlug().'-'.now()->format('Ymd-His').'.csv',
        );
    }

    /**
     * Chart widgets to render above the table on this Report page.
     *
     * Subclasses return an array of widget class-strings (each extends
     * {@see ChartWidget}). The base Blade renders them via
     * `<livewire>` and passes the page's `$data` filter values through to
     * any widget that accepts a `from` / `to` mount parameter.
     *
     * Override this in subclasses to opt into charting. Default: no charts.
     *
     * @return array<int, string>
     */
    protected function chartWidgets(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        /** @var Report $report */
        $report = app($this->reportClass());
        $rows = $report->run($this->buildRequest());

        // Normalise to a list of associative arrays so the Blade can iterate uniformly.
        $normalised = collect($rows)
            ->map(fn ($row): array => (array) $row)
            ->values()
            ->all();

        return [
            'rows' => $normalised,
            'chartWidgets' => $this->chartWidgets(),
        ];
    }

    protected function buildRequest(): Request
    {
        return new Request([
            'from' => $this->data['from'] ?? null,
            'to' => $this->data['to'] ?? null,
        ]);
    }
}
