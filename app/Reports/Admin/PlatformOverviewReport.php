<?php

declare(strict_types=1);

namespace App\Reports\Admin;

use App\Actions\Admin\ComputePlatformOverviewAction;
use App\Reports\Contracts\Report;
use Illuminate\Http\Request;

class PlatformOverviewReport implements Report
{
    public function __construct(private readonly ComputePlatformOverviewAction $overview) {}

    public function run(Request $request): array
    {
        $data = $this->overview->execute();

        $rows = [];
        foreach ($data as $metric => $value) {
            $rows[] = ['metric' => (string) $metric, 'value' => $value];
        }

        return $rows;
    }

    /** @return array<int,string> */
    public function csvHeaders(): array
    {
        return ['metric', 'value'];
    }
}
