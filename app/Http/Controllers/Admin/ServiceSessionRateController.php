<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ServiceSessionRate;
use App\Support\HesetakMatchCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ServiceSessionRateController extends Controller
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

    public function index(): View
    {
        $years = Schema::hasTable('academic_years')
            ? AcademicYear::query()->active()->ordered()->get(['id', 'name', 'level_number'])
            : collect();

        $tracks = HesetakMatchCatalog::curriculumTypes('ar');
        $currency = strtoupper((string) config('currency.code', 'SAR'));

        $rates = ServiceSessionRate::query()
            ->where('currency', $currency)
            ->get()
            ->groupBy(fn (ServiceSessionRate $r) => ($r->academic_year_id ?? 'global').'|'.$r->curriculum_type);

        $matrix = [];
        foreach ([null, ...$years->all()] as $year) {
            $yearId = $year?->id;
            $rowKey = $yearId ?? 'global';
            $matrix[$rowKey] = [
                'year' => $year,
                'label' => $year?->name ?? 'كل المراحل (معدل عام)',
                'cells' => [],
            ];
            foreach ($tracks as $key => $meta) {
                $lookup = ($yearId ?? 'global').'|'.$key;
                $matrix[$rowKey]['cells'][$key] = $rates->get($lookup)?->first();
            }
        }

        return view('admin.service-session-rates.index', [
            'matrix' => $matrix,
            'tracks' => $tracks,
            'currency' => $currency,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $tracks = array_keys(HesetakMatchCatalog::curriculumTypes());
        $currency = strtoupper((string) config('currency.code', 'SAR'));

        $data = $request->validate([
            'rates' => ['required', 'array'],
            'rates.*.academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'rates.*.curriculum_type' => ['required', 'string', 'in:'.implode(',', $tracks)],
            'rates.*.price_per_session' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'rates.*.is_active' => ['nullable', 'boolean'],
        ]);

        foreach ($data['rates'] as $row) {
            $yearId = isset($row['academic_year_id']) && $row['academic_year_id'] !== ''
                ? (int) $row['academic_year_id']
                : null;
            $type = (string) $row['curriculum_type'];
            $price = $row['price_per_session'] ?? null;

            $existing = ServiceSessionRate::query()
                ->where('curriculum_type', $type)
                ->where('currency', $currency)
                ->when(
                    $yearId === null,
                    fn ($q) => $q->whereNull('academic_year_id'),
                    fn ($q) => $q->where('academic_year_id', $yearId)
                )
                ->first();

            if ($price === null || $price === '') {
                $existing?->delete();
                continue;
            }

            ServiceSessionRate::query()->updateOrCreate(
                [
                    'academic_year_id' => $yearId,
                    'curriculum_type' => $type,
                    'currency' => $currency,
                ],
                [
                    'price_per_session' => round((float) $price, 2),
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ]
            );
        }

        return redirect()->route('admin.service-session-rates.index')
            ->with('success', 'تم حفظ مصفوفة أسعار الحصص (مرحلة × مسار).');
    }
}
