<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Spot;
use App\Models\SpotMenu;
use App\Models\SpotService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SpotMenuController extends Controller
{
    public function index(Spot $spot, SpotService $service): View
    {
        $menus = $service->menus()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.spots.services.menus.index', compact('spot', 'service', 'menus'));
    }

    public function create(Spot $spot, SpotService $service): View
    {
        return view('admin.spots.services.menus.form', [
            'spot' => $spot,
            'service' => $service,
            'menu' => new SpotMenu(),
            'formAction' => route('admin.spots.services.menus.store', [$spot, $service]),
            'formMethod' => 'POST',
        ]);
    }

    public function store(Request $request, Spot $spot, SpotService $service): RedirectResponse
    {
        $data = $this->validated($request);
        $data['spot_id'] = $spot->id;
        $service->menus()->create($data);

        return redirect()->route('admin.spots.services.menus.index', [$spot, $service])
            ->with('status', 'メニューを追加しました。');
    }

    public function edit(Spot $spot, SpotService $service, SpotMenu $menu): View
    {
        return view('admin.spots.services.menus.form', [
            'spot' => $spot,
            'service' => $service,
            'menu' => $menu,
            'formAction' => route('admin.spots.services.menus.update', [$spot, $service, $menu]),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, Spot $spot, SpotService $service, SpotMenu $menu): RedirectResponse
    {
        $menu->update($this->validated($request));

        return redirect()->route('admin.spots.services.menus.index', [$spot, $service])
            ->with('status', 'メニューを更新しました。');
    }

    public function destroy(Spot $spot, SpotService $service, SpotMenu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()->route('admin.spots.services.menus.index', [$spot, $service])
            ->with('status', 'メニューを削除しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_text' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]) + ['sort_order' => (int) $request->input('sort_order', 0)];
    }
}
