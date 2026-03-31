<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Spot;
use App\Models\SpotStation;
use App\Models\Station;
use App\Services\NearestStationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SpotStationController extends Controller
{
    public function index(Spot $spot): View
    {
        $spotStations = $spot->spotStations()
            ->with('station')
            ->orderBy('sort_order')
            ->get();

        return view('admin.spots.stations.index', compact('spot', 'spotStations'));
    }

    public function store(Request $request, Spot $spot): RedirectResponse
    {
        $data = $request->validate([
            'station_name' => ['required', 'string', 'max:100'],
            'walking_minutes' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $station = Station::where('station_name', 'like', '%' . $data['station_name'] . '%')
            ->first();

        if (! $station) {
            return back()->withErrors(['station_name' => '「' . $data['station_name'] . '」に一致する駅が見つかりませんでした。'])->withInput();
        }

        if ($spot->spotStations()->where('station_id', $station->id)->exists()) {
            return back()->withErrors(['station_name' => 'その駅はすでに登録されています。'])->withInput();
        }

        $maxOrder = $spot->spotStations()->max('sort_order') ?? 0;

        $spot->spotStations()->create([
            'station_id' => $station->id,
            'walking_minutes' => $data['walking_minutes'] ?? null,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('admin.spots.stations.index', $spot)
            ->with('status', '「' . $station->station_name . '」を追加しました。');
    }

    public function destroy(Spot $spot, SpotStation $station): RedirectResponse
    {
        $station->delete();

        return redirect()->route('admin.spots.stations.index', $spot)
            ->with('status', '最寄り駅を削除しました。');
    }

    public function recalculate(Spot $spot): RedirectResponse
    {
        if (! $spot->latitude || ! $spot->longitude) {
            return back()->withErrors(['recalculate' => '緯度・経度が未設定です。先にスポット基本情報で位置情報を入力してください。']);
        }

        NearestStationService::syncForSpot($spot);

        return redirect()->route('admin.spots.stations.index', $spot)
            ->with('status', '最寄り駅を再算出しました。');
    }
}
