<?php

namespace App\Http\Controllers;

use App\Actions\RecordStatusChange;
use App\Enums\ChangeOrigin;
use App\Http\Requests\RevertLinkVerificationRequest;
use App\Http\Requests\VerifyLinkRequest;
use App\Models\Link;
use App\Models\Owner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * The verification dimension of a link (0013, 0018): verified on evidence,
 * reverted by hand with a reason. Both go through RecordStatusChange, the
 * single path that writes the trail (0007).
 */
class LinkVerificationController extends Controller
{
    /**
     * Verify the link.
     */
    public function store(VerifyLinkRequest $request, Link $link, RecordStatusChange $recordStatusChange): RedirectResponse
    {
        Gate::authorize('update', $link);

        $recordStatusChange->verify($link, Owner::findOrFail($request->integer('owner_id')));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Link verified.')]);

        return to_route('links.show', $link);
    }

    /**
     * Revert the verification by hand.
     */
    public function destroy(RevertLinkVerificationRequest $request, Link $link, RecordStatusChange $recordStatusChange): RedirectResponse
    {
        Gate::authorize('update', $link);

        $recordStatusChange->revert(
            $link,
            ChangeOrigin::Manual,
            Owner::findOrFail($request->integer('owner_id')),
            $request->string('trigger_reason')->toString(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Verification reverted.')]);

        return to_route('links.show', $link);
    }
}
