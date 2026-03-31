<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Spot;
use App\Models\SpotService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SpotServiceController extends Controller
{
    public function index(Spot $spot): View
    {
        $services = $spot->services()->with('menus')->orderBy('sort_order')->orderBy('title')->get();

        return view('admin.spots.services.index', compact('spot', 'services'));
    }

    public function create(Spot $spot): View
    {
        return view('admin.spots.services.form', [
            'spot' => $spot,
            'service' => new SpotService(),
            'formAction' => route('admin.spots.services.store', $spot),
            'formMethod' => 'POST',
        ]);
    }

    public function store(Request $request, Spot $spot): RedirectResponse
    {
        $data = $this->validated($request);
        $spot->services()->create($data);

        return redirect()->route('admin.spots.services.index', $spot)
            ->with('status', 'サービスを追加しました。');
    }

    public function edit(Spot $spot, SpotService $service): View
    {
        return view('admin.spots.services.form', [
            'spot' => $spot,
            'service' => $service,
            'formAction' => route('admin.spots.services.update', [$spot, $service]),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, Spot $spot, SpotService $service): RedirectResponse
    {
        $service->update($this->validated($request));

        return redirect()->route('admin.spots.services.index', $spot)
            ->with('status', 'サービスを更新しました。');
    }

    public function destroy(Spot $spot, SpotService $service): RedirectResponse
    {
        $service->delete();

        return redirect()->route('admin.spots.services.index', $spot)
            ->with('status', 'サービスを削除しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]) + ['sort_order' => (int) $request->input('sort_order', 0)];
    }
}
