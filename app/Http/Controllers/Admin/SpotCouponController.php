<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Spot;
use App\Models\SpotCoupon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SpotCouponController extends Controller
{
    public function index(Spot $spot): View
    {
        $coupons = $spot->coupons()->orderByDesc('created_at')->get();

        return view('admin.spots.coupons.index', compact('spot', 'coupons'));
    }

    public function create(Spot $spot): View
    {
        return view('admin.spots.coupons.form', [
            'spot' => $spot,
            'coupon' => new SpotCoupon(),
            'formAction' => route('admin.spots.coupons.store', $spot),
            'formMethod' => 'POST',
        ]);
    }

    public function store(Request $request, Spot $spot): RedirectResponse
    {
        $data = $this->validated($request);
        $spot->coupons()->create($data);

        return redirect()->route('admin.spots.coupons.index', $spot)
            ->with('status', 'クーポンを追加しました。');
    }

    public function edit(Spot $spot, SpotCoupon $coupon): View
    {
        return view('admin.spots.coupons.form', [
            'spot' => $spot,
            'coupon' => $coupon,
            'formAction' => route('admin.spots.coupons.update', [$spot, $coupon]),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, Spot $spot, SpotCoupon $coupon): RedirectResponse
    {
        $coupon->update($this->validated($request));

        return redirect()->route('admin.spots.coupons.index', $spot)
            ->with('status', 'クーポンを更新しました。');
    }

    public function destroy(Spot $spot, SpotCoupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()->route('admin.spots.coupons.index', $spot)
            ->with('status', 'クーポンを削除しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'conditions' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
