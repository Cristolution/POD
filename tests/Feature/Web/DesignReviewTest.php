<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Design;
use App\Models\DesignReview;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_review_on_published_design(): void
    {
        $design = Design::factory()->published()->create();
        $customer = User::factory()->customer()->create();

        $response = $this
            ->actingAs($customer)
            ->post(route('design.reviews.store', $design), [
                'rating' => 5,
                'title' => 'Love it',
                'body' => 'Arrived fast, looks great.',
            ]);

        $response->assertRedirect(route('design.show', $design));

        $this->assertDatabaseHas('design_reviews', [
            'design_id' => $design->id,
            'customer_id' => $customer->id,
            'rating' => 5,
            'title' => 'Love it',
            'is_approved' => true,
        ]);
    }

    public function test_review_form_rejects_invalid_rating(): void
    {
        $design = Design::factory()->published()->create();
        $customer = User::factory()->customer()->create();

        $this
            ->actingAs($customer)
            ->from(route('design.show', $design))
            ->post(route('design.reviews.store', $design), [
                'rating' => 7, // out of range
            ])
            ->assertSessionHasErrors('rating');
    }

    public function test_customer_cannot_review_twice(): void
    {
        $design = Design::factory()->published()->create();
        $customer = User::factory()->customer()->create();

        DesignReview::factory()->approved()->create([
            'design_id' => $design->id,
            'customer_id' => $customer->id,
        ]);

        $this
            ->actingAs($customer)
            ->post(route('design.reviews.store', $design), [
                'rating' => 4,
            ])
            ->assertRedirect(route('design.show', $design))
            ->assertSessionHas('status');

        $this->assertCount(1, DesignReview::where('design_id', $design->id)->get());
    }

    public function test_designer_cannot_submit_review(): void
    {
        $design = Design::factory()->published()->create();
        $designer = User::factory()->designer()->create();

        $this
            ->actingAs($designer)
            ->post(route('design.reviews.store', $design), [
                'rating' => 5,
            ])
            ->assertForbidden();
    }

    public function test_anonymous_cannot_submit_review(): void
    {
        $design = Design::factory()->published()->create();

        $this
            ->post(route('design.reviews.store', $design), [
                'rating' => 5,
            ])
            ->assertRedirect(route('login'));
    }

    public function test_customer_can_delete_own_review(): void
    {
        $design = Design::factory()->published()->create();
        $customer = User::factory()->customer()->create();
        $review = DesignReview::factory()->approved()->create([
            'design_id' => $design->id,
            'customer_id' => $customer->id,
        ]);

        $this
            ->actingAs($customer)
            ->delete(route('design.reviews.destroy', [$design, $review]))
            ->assertRedirect(route('design.show', $design));

        $this->assertDatabaseMissing('design_reviews', ['id' => $review->id]);
    }

    public function test_other_customer_cannot_delete_someone_elses_review(): void
    {
        $design = Design::factory()->published()->create();
        $owner = User::factory()->customer()->create();
        $stranger = User::factory()->customer()->create();
        $review = DesignReview::factory()->approved()->create([
            'design_id' => $design->id,
            'customer_id' => $owner->id,
        ]);

        $this
            ->actingAs($stranger)
            ->delete(route('design.reviews.destroy', [$design, $review]))
            ->assertForbidden();

        $this->assertDatabaseHas('design_reviews', ['id' => $review->id]);
    }

    public function test_admin_can_delete_any_review(): void
    {
        $design = Design::factory()->published()->create();
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $review = DesignReview::factory()->approved()->create([
            'design_id' => $design->id,
            'customer_id' => $customer->id,
        ]);

        $this
            ->actingAs($admin)
            ->delete(route('design.reviews.destroy', [$design, $review]))
            ->assertRedirect(route('design.show', $design));

        $this->assertDatabaseMissing('design_reviews', ['id' => $review->id]);
    }

    public function test_average_rating_and_count_are_computed_from_approved_only(): void
    {
        $design = Design::factory()->published()->create();
        $customers = User::factory()->customer()->count(3)->create();

        DesignReview::factory()->approved()->create([
            'design_id' => $design->id, 'customer_id' => $customers[0]->id, 'rating' => 5,
        ]);
        DesignReview::factory()->approved()->create([
            'design_id' => $design->id, 'customer_id' => $customers[1]->id, 'rating' => 3,
        ]);
        DesignReview::factory()->pending()->create([
            'design_id' => $design->id, 'customer_id' => $customers[2]->id, 'rating' => 1,
        ]);

        // 2 approved reviews (5 + 3) → avg 4.0
        $this->assertSame(4.0, $design->averageRating());
        $this->assertSame(2, $design->approvedReviews()->count());
    }

    public function test_duplicate_review_per_design_is_blocked_at_db_level(): void
    {
        $design = Design::factory()->published()->create();
        $customer = User::factory()->customer()->create();

        DesignReview::factory()->approved()->create([
            'design_id' => $design->id,
            'customer_id' => $customer->id,
        ]);

        $this->expectException(QueryException::class);

        DesignReview::factory()->approved()->create([
            'design_id' => $design->id,
            'customer_id' => $customer->id,
        ]);
    }
}
