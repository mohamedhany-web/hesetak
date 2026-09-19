<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServicePackage;
use App\Models\ServicePackagePricingRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServicePackagePricingRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (! $user || (! $user->isAdmin() && ! $user->hasPermission('manage.packages') && ! $user->hasPermission('manage.tutoring-groups'))) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $rules = ServicePackagePricingRule::query()->ordered()->get();
        $editRule = $request->integer('edit')
            ? ServicePackagePricingRule::query()->findOrFail($request->integer('edit'))
            : new ServicePackagePricingRule([
                'scope' => ServicePackage::SCOPE_TUTORING_INDIVIDUAL,
                'price_per_session' => 25,
                'min_sessions' => 2,
                'max_sessions' => 24,
                'session_step' => 1,
                'session_minutes' => 60,
                'duration_days' => 90,
                'is_active' => true,
                'sort_order' => (int) ServicePackagePricingRule::query()->max('sort_order') + 1,
            ]);

        return view('admin.service-package-pricing-rules.index', compact('rules', 'editRule'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['discount_min_sessions'], $data['discount_percent']);
        $data['discount_tiers'] = $this->discountTiers($request);
        $data['is_active'] = $request->boolean('is_active');

        ServicePackagePricingRule::create($data);

        return redirect()->route('admin.service-package-pricing-rules.index')
            ->with('success', 'تمت إضافة قاعدة التسعير بالريال السعودي.');
    }

    public function update(Request $request, ServicePackagePricingRule $servicePackagePricingRule): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['discount_min_sessions'], $data['discount_percent']);
        $data['discount_tiers'] = $this->discountTiers($request);
        $data['is_active'] = $request->boolean('is_active');

        $servicePackagePricingRule->update($data);

        return redirect()->route('admin.service-package-pricing-rules.index')
            ->with('success', 'تم تحديث قاعدة التسعير.');
    }

    public function destroy(ServicePackagePricingRule $servicePackagePricingRule): RedirectResponse
    {
        $servicePackagePricingRule->delete();

        return back()->with('success', 'تم حذف قاعدة التسعير.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scope' => ['required', 'in:global,tutoring_individual,tutoring_collective,private_lessons'],
            'price_per_session' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'min_sessions' => ['required', 'integer', 'min:1', 'max:500'],
            'max_sessions' => ['required', 'integer', 'gte:min_sessions', 'max:500'],
            'session_step' => ['required', 'integer', 'min:1', 'max:100'],
            'session_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:730'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'discount_min_sessions' => ['nullable', 'array', 'max:5'],
            'discount_min_sessions.*' => ['nullable', 'integer', 'min:1', 'max:500'],
            'discount_percent' => ['nullable', 'array', 'max:5'],
            'discount_percent.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }

    private function discountTiers(Request $request): array
    {
        $minimums = $request->input('discount_min_sessions', []);
        $percentages = $request->input('discount_percent', []);
        $tiers = [];

        foreach ($minimums as $index => $minimum) {
            $percent = $percentages[$index] ?? null;
            if ($minimum === null || $minimum === '' || $percent === null || $percent === '') {
                continue;
            }
            $tiers[] = [
                'min_sessions' => (int) $minimum,
                'discount_percent' => (float) $percent,
            ];
        }

        usort($tiers, fn (array $a, array $b) => $a['min_sessions'] <=> $b['min_sessions']);

        return $tiers;
    }
}
