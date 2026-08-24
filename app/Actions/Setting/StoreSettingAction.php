<?php

declare(strict_types=1);

namespace App\Actions\Setting;

use App\Models\Setting;

class StoreSettingAction
{
    /** @param array<string,mixed> $data */
    public function execute(array $data): Setting
    {
        return Setting::create($data);
    }
}
