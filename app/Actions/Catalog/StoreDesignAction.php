<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\User;

class StoreDesignAction
{
    /** @param array<string,mixed> $data */
    public function execute(User $user, array $data): Design
    {
        $profile = $this->resolveProfile($user, $data);

        $designData = collect($data)
            ->except(['tag_ids'])
            ->all();

        if ($user->isAdmin() && isset($designData['designer_id'])) {
            $profile = DesignerProfile::query()->findOrFail($designData['designer_id']);
        } else {
            $designData['designer_id'] = $profile->id;
        }

        $design = $profile->designs()->create($designData);

        if (! empty($data['tag_ids'])) {
            $design->tags()->sync($data['tag_ids']);
        }

        return $design->refresh()->load(['designer', 'category', 'tags']);
    }

    /**
     * Find or create the DesignerProfile for a given user. Designers must
     * already own a profile before they can publish a design; we lazily
     * create one if missing so the API stays ergonomic.
     *
     * @param  array<string,mixed>  $data
     */
    private function resolveProfile(User $user, array $data): DesignerProfile
    {
        if ($user->isAdmin() && isset($data['designer_id'])) {
            return DesignerProfile::query()->findOrFail($data['designer_id']);
        }

        return $user->designerProfile()->firstOrCreate(['user_id' => $user->id], []);
    }
}
