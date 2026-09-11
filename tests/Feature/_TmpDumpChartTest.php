<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports\RevenueByDayReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class _TmpDumpChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_chart_widget_survives_in_html(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $tester = Livewire::test(RevenueByDayReport::class);

        $html1 = $tester->html();
        fwrite(STDERR, 'INITIAL has chart wire:id: '.(preg_match('/wire:id="[^"]+".*fi-wi-chart/s', $html1) ? 'YES' : 'NO')."\n");
        fwrite(STDERR, 'INITIAL has canvas: '.(str_contains($html1, '<canvas') ? 'YES' : 'NO')."\n");
        fwrite(STDERR, 'INITIAL has chart widget name: '.(str_contains($html1, 'app.filament.widgets.reports.revenue-by-day-chart') ? 'YES' : 'NO')."\n");

        $tester->set('data.from', now()->subDays(7)->toDateString());
        $html2 = $tester->html();
        fwrite(STDERR, "\nAFTER FILTER has chart wire:id: ".(preg_match('/wire:id="[^"]+".*fi-wi-chart/s', $html2) ? 'YES' : 'NO')."\n");
        fwrite(STDERR, 'AFTER FILTER has canvas: '.(str_contains($html2, '<canvas') ? 'YES' : 'NO')."\n");
        fwrite(STDERR, 'AFTER FILTER has chart widget name: '.(str_contains($html2, 'app.filament.widgets.reports.revenue-by-day-chart') ? 'YES' : 'NO')."\n");

        // Check if the chart data updated
        preg_match('/cachedData: JSON.parse\(\'(.+?)\'\),/s', $html2, $m);
        fwrite(STDERR, 'AFTER FILTER cachedData raw len: '.strlen($m[1] ?? 'none')."\n");
        fwrite(STDERR, 'AFTER FILTER cachedData: '.substr($m[1] ?? 'none', 0, 200)."\n");
        $this->assertTrue(true);
    }
}
