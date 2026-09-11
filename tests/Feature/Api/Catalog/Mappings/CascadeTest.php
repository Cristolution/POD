<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Mappings;

use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\DesignProductMapping;
use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Notifications\PrinterUnavailableForMappingNotification;
use Database\Factories\DesignerProfileFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCase;

class CascadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_deleting_a_design_cascades_its_mappings(): void
    {
        $design = Design::factory()->create();
        $printer = PrinterProviderProfile::factory()->create();
        $t1 = ProductTemplate::factory()->create();
        $t2 = ProductTemplate::factory()->create();

        DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $t1->id,
            'preferred_printer_id' => $printer->id,
        ]);
        DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $t2->id,
            'preferred_printer_id' => $printer->id,
        ]);

        $this->assertSame(2, DesignProductMapping::query()->where('design_id', $design->id)->count());

        $design->forceDelete();

        $this->assertSame(0, DesignProductMapping::query()->withTrashed()->where('design_id', $design->id)->count());
    }

    public function test_soft_deleting_a_printer_nullifies_mapping_fk_and_notifies_designer(): void
    {
        NotificationFacade::fake();

        [$designer] = $this->makeDesignerWithProfile();
        $design = Design::factory()->forDesigner($designer->designerProfile)->create();
        $template = ProductTemplate::factory()->create();
        $printer = PrinterProviderProfile::factory()->create();

        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
        ]);

        $printer->delete();

        $mapping->refresh();
        $this->assertNull($mapping->preferred_printer_id, 'preferred_printer_id should be nulled on printer soft-delete');
        $this->assertNull($mapping->deleted_at, 'mapping itself should be untouched (no soft-delete)');

        NotificationFacade::assertSentTo(
            $designer,
            PrinterUnavailableForMappingNotification::class,
            function ($notification, $channels, $notifiable) use ($mapping) {
                return data_get($notification->toArray($notifiable), 'mapping_id') === $mapping->id;
            }
        );
    }

    public function test_force_deleting_a_printer_nullifies_mapping_fk_and_notifies_designer(): void
    {
        NotificationFacade::fake();

        [$designer] = $this->makeDesignerWithProfile();
        $design = Design::factory()->forDesigner($designer->designerProfile)->create();
        $template = ProductTemplate::factory()->create();
        $printer = PrinterProviderProfile::factory()->create();

        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
        ]);

        $printer->forceDelete();

        $mapping->refresh();
        $this->assertNull($mapping->preferred_printer_id, 'preferred_printer_id should be nulled on printer force-delete');
        $this->assertNull(PrinterProviderProfile::query()->withTrashed()->find($printer->id)?->deleted_at);

        NotificationFacade::assertSentTo(
            $designer,
            PrinterUnavailableForMappingNotification::class,
        );
    }

    public function test_mapping_requires_preferred_printer_on_create(): void
    {
        [$designer] = $this->makeDesignerWithProfile();
        $design = Design::factory()->forDesigner($designer->designerProfile)->create();
        $template = ProductTemplate::factory()->create();

        $response = $this->actingAs($designer, 'sanctum')
            ->postJson('/api/me/mappings', [
                'design_id' => $design->id,
                'product_template_id' => $template->id,
                'final_price' => 19.99,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['preferred_printer_id']);
    }

    public function test_soft_deleted_design_mappings_hidden_from_default_query(): void
    {
        $design = Design::factory()->create();
        $template = ProductTemplate::factory()->create();
        $printer = PrinterProviderProfile::factory()->create();

        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
        ]);

        $design->delete();

        $this->assertSame(0, DesignProductMapping::query()->count(), 'mapping should be hidden when its design is soft-deleted');
        $this->assertSame(1, DesignProductMapping::query()->withTrashedDesigns()->count(), 'withTrashedDesigns scope should reveal it');
        $this->assertNotNull(DesignProductMapping::query()->withTrashedDesigns()->find($mapping->id));
    }

    /**
     * @return array{0: User, 1: DesignerProfile}
     */
    private function makeDesignerWithProfile(): array
    {
        $user = User::factory()->create(['role' => 'designer']);
        $profile = DesignerProfileFactory::new()->for($user, 'user')->create();

        return [$user, $profile];
    }
}
