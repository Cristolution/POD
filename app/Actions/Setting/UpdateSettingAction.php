<?php

declare(strict_types=1);

namespace App\Actions\Setting;

use App\Models\Setting;

class UpdateSettingAction
{
    /** @param array<string,mixed> $data */
    public function execute(Setting $setting, array $data): Setting
    {
        $setting->update($data);

        return $setting->refresh();
    }
}
