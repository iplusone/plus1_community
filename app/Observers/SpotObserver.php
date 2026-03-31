<?php

namespace App\Observers;

use App\Models\Spot;
use App\Models\SpotSearchDocument;
use App\Services\NearestStationService;

class SpotObserver
{
    public function saved(Spot $spot): void
    {
        SpotSearchDocument::syncForSpot($spot);

        if ($spot->wasChanged(['latitude', 'longitude']) || $spot->wasRecentlyCreated) {
            NearestStationService::syncForSpot($spot);
        }
    }

    public function deleted(Spot $spot): void
    {
        SpotSearchDocument::query()->where('spot_id', $spot->id)->delete();
    }
}
