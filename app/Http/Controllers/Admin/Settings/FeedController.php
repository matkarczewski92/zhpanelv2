<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\FeedRequest;
use App\Models\Feed;
use App\Services\Admin\Settings\FeedService;

class FeedController extends Controller
{
    public function __construct(private readonly FeedService $service)
    {
    }

    public function store(FeedRequest $request)
    {
        $this->service->store($request->validated());
        return back()->with('toast', ['type' => 'success', 'message' => 'Karma dodana.']);
    }

    public function update(FeedRequest $request, Feed $feed)
    {
        $this->service->update($feed, $request->validated());
        return back()->with('toast', ['type' => 'success', 'message' => 'Karma zaktualizowana.']);
    }

    public function destroy(Feed $feed)
    {
        $result = $this->service->destroy($feed);
        return back()->with('toast', ['type' => $result['type'], 'message' => $result['message']]);
    }
}
