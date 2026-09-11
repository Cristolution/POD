<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreDesignReviewRequest;
use App\Models\Design;
use App\Models\DesignReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DesignReviewController extends Controller
{
    public function store(StoreDesignReviewRequest $request, Design $design): RedirectResponse
    {
        // Check the "already reviewed" case FIRST so we can show a friendly
        // status instead of a 403 from the policy (which is for unauthorized
        // access, not duplicate submissions).
        if ($design->reviewBy($request->user()) !== null) {
            return redirect()
                ->route('design.show', $design)
                ->with('status', 'You already reviewed this design.');
        }

        Gate::authorize('create', [DesignReview::class, $design]);

        DesignReview::create([
            'design_id' => $design->id,
            'customer_id' => $request->user()->id,
            'rating' => $request->integer('rating'),
            'title' => $request->input('title'),
            'body' => $request->input('body'),
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        return redirect()
            ->route('design.show', $design)
            ->with('status', 'Thanks — your review is live.');
    }

    public function destroy(Design $design, DesignReview $review): RedirectResponse
    {
        Gate::authorize('delete', $review);

        $review->delete();

        return redirect()
            ->route('design.show', $design)
            ->with('status', 'Review removed.');
    }
}
