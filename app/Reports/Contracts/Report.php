<?php

declare(strict_types=1);

namespace App\Reports\Contracts;

use Illuminate\Http\Request;

interface Report
{
    /**
     * Run the report and return the result.
     *
     * Implementations may return either a list of rows (array or iterable) or,
     * for aggregate reports, an associative array / object that will be JSON-encoded
     * by the controller verbatim.
     *
     * @return iterable<array<string,mixed>>|array<int,array<string,mixed>>|object
     */
    public function run(Request $request): mixed;

    /**
     * CSV header row emitted before the report rows.
     *
     * @return array<int,string>
     */
    public function csvHeaders(): array;
}
