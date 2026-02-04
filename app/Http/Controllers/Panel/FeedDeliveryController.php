<?php

namespace App\Http\Controllers\Panel;

use App\Application\Feeds\Commands\AddFeedDeliveryItemCommand;
use App\Application\Feeds\Commands\CommitFeedDeliveryCommand;
use App\Application\Feeds\Commands\RemoveFeedDeliveryItemCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\CommitFeedDeliveryRequest;
use App\Http\Requests\Panel\StoreFeedDeliveryItemRequest;
use App\Models\Feed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class FeedDeliveryController extends Controller
{
    public function store(StoreFeedDeliveryItemRequest $request, AddFeedDeliveryItemCommand $command): RedirectResponse
    {
        $command->handle($request->validated());

        return redirect()->route('panel.feeds.index');
    }

    public function destroy(Feed $feed, RemoveFeedDeliveryItemCommand $command): RedirectResponse
    {
        $command->handle($feed->id);

        return redirect()->route('panel.feeds.index');
    }

    public function commit(CommitFeedDeliveryRequest $request, CommitFeedDeliveryCommand $command): RedirectResponse
    {
        try {
            $result = $command->handle();
        } catch (ValidationException $exception) {
            return redirect()
                ->route('panel.feeds.index')
                ->withErrors($exception->errors(), 'feedDeliveryCommit');
        }

        $total = number_format($result->totalValue, 2, ',', ' ') . ' zl';

        return redirect()
            ->route('panel.feeds.index')
            ->with('toast', [
                'type' => 'success',
                'message' => "Dostawe zapisano. Lacznie {$total}.",
            ]);
    }
}
