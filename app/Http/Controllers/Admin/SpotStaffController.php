<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Spot;
use App\Models\SpotStaff;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SpotStaffController extends Controller
{
    public function index(Spot $spot): View
    {
        $staff = $spot->staff()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.spots.staff.index', compact('spot', 'staff'));
    }

    public function create(Spot $spot): View
    {
        return view('admin.spots.staff.form', [
            'spot' => $spot,
            'member' => new SpotStaff(),
            'formAction' => route('admin.spots.staff.store', $spot),
            'formMethod' => 'POST',
        ]);
    }

    public function store(Request $request, Spot $spot): RedirectResponse
    {
        $data = $this->validated($request);
        $spot->staff()->create($data);

        return redirect()->route('admin.spots.staff.index', $spot)
            ->with('status', 'スタッフを追加しました。');
    }

    public function edit(Spot $spot, SpotStaff $staff): View
    {
        return view('admin.spots.staff.form', [
            'spot' => $spot,
            'member' => $staff,
            'formAction' => route('admin.spots.staff.update', [$spot, $staff]),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, Spot $spot, SpotStaff $staff): RedirectResponse
    {
        $staff->update($this->validated($request));

        return redirect()->route('admin.spots.staff.index', $spot)
            ->with('status', 'スタッフを更新しました。');
    }

    public function destroy(Spot $spot, SpotStaff $staff): RedirectResponse
    {
        $staff->delete();

        return redirect()->route('admin.spots.staff.index', $spot)
            ->with('status', 'スタッフを削除しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'profile' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]) + ['sort_order' => (int) $request->input('sort_order', 0)];
    }
}
