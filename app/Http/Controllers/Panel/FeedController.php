<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\StoreFeedRequest;
use App\Models\Feed;
use App\Services\Panel\FeedService;
use Illuminate\View\View;

class FeedController extends Controller
{
    public function __construct(private readonly FeedService $service)
    {
    }

    public function index(): View
    {
        return view('panel.feeds.index', $this->service->getIndexData());
    }

    public function store(StoreFeedRequest $request)
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('panel.feeds.index')
            ->with('toast', ['type' => 'success', 'message' => 'Karma dodana.']);
    }

    public function destroy(Feed $feed)
    {
        $result = $this->service->destroy($feed);

        return redirect()
            ->route('panel.feeds.index')
            ->with('toast', ['type' => $result['type'], 'message' => $result['message']]);
    }
}
