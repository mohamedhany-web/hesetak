<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TutoringGroup;
use App\Models\TutoringGroupPackage;
use App\Services\TutoringGroupPackagePricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TutoringGroupPackageController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (! $user || (! $user->isAdmin() && ! $user->hasPermission('manage.tutoring-groups'))) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(TutoringGroup $tutoringGroup): View
    {
        abort_unless($tutoringGroup->isIndividual(), 404);

        $packages = $tutoringGroup->packages()->orderBy('sort_order')->orderBy('duration_months')->paginate(20);

        return view('admin.tutoring-groups.packages.index', [
            'group' => $tutoringGroup,
            'packages' => $packages,
            'type' => $tutoringGroup->type,
        ]);
    }

    public function create(TutoringGroup $tutoringGroup): View
    {
        abort_unless($tutoringGroup->isIndividual(), 404);

        $hourly = (float) ($tutoringGroup->hourly_rate ?? 10);
        $spm = (int) ($tutoringGroup->sessions_per_month ?: 8);

        return view('admin.tutoring-groups.packages.form', [
            'group' => $tutoringGroup,
            'package' => new TutoringGroupPackage([
                'tutoring_group_id' => $tutoringGroup->id,
                'name' => 'باقة شهر واحد',
                'duration_months' => 1,
                'sessions_per_month' => $spm,
                'hourly_rate' => $hourly,
                'currency' => $tutoringGroup->currency ?: platform_currency(),
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'suggestedPrice' => TutoringGroupPackagePricingService::suggestedPrice($hourly, $spm, 1),
            'mode' => 'create',
            'type' => $tutoringGroup->type,
        ]);
    }

    public function store(Request $request, TutoringGroup $tutoringGroup): RedirectResponse
    {
        abort_unless($tutoringGroup->isIndividual(), 404);
        $data = $this->validated($request);
        $data['tutoring_group_id'] = $tutoringGroup->id;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured');

        $package = new TutoringGroupPackage($data);
        TutoringGroupPackagePricingService::applyToPackage(
            $package,
            $request->filled('price') ? (float) $request->input('price') : TutoringGroupPackagePricingService::suggestedPrice(
                (float) $package->hourly_rate,
                (int) $package->sessions_per_month,
                (int) $package->duration_months
            )
        );
        $package->save();

        return redirect()
            ->route('admin.tutoring-groups.packages.index', $tutoringGroup)
            ->with('success', 'تم إنشاء الباقة.');
    }

    public function edit(TutoringGroup $tutoringGroup, TutoringGroupPackage $package): View
    {
        abort_unless($tutoringGroup->isIndividual() && (int) $package->tutoring_group_id === (int) $tutoringGroup->id, 404);

        return view('admin.tutoring-groups.packages.form', [
            'group' => $tutoringGroup,
            'package' => $package,
            'suggestedPrice' => TutoringGroupPackagePricingService::suggestedPrice(
                (float) $package->hourly_rate,
                (int) $package->sessions_per_month,
                (int) $package->duration_months
            ),
            'mode' => 'edit',
            'type' => $tutoringGroup->type,
        ]);
    }

    public function update(Request $request, TutoringGroup $tutoringGroup, TutoringGroupPackage $package): RedirectResponse
    {
        abort_unless($tutoringGroup->isIndividual() && (int) $package->tutoring_group_id === (int) $tutoringGroup->id, 404);
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured');

        $package->fill($data);
        TutoringGroupPackagePricingService::applyToPackage(
            $package,
            $request->filled('price') ? (float) $request->input('price') : null
        );
        $package->save();

        return redirect()
            ->route('admin.tutoring-groups.packages.index', $tutoringGroup)
            ->with('success', 'تم تحديث الباقة.');
    }

    public function destroy(TutoringGroup $tutoringGroup, TutoringGroupPackage $package): RedirectResponse
    {
        abort_unless((int) $package->tutoring_group_id === (int) $tutoringGroup->id, 404);
        $package->delete();

        return redirect()
            ->route('admin.tutoring-groups.packages.index', $tutoringGroup)
            ->with('success', 'تم حذف الباقة.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:24'],
            'sessions_per_month' => ['required', 'integer', 'min:1', 'max:60'],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
