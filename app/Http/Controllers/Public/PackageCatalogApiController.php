<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\PackageCatalogFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageCatalogApiController extends Controller
{
    public function __invoke(Request $request, PackageCatalogFilterService $catalog): JsonResponse
    {
        $data = $catalog->catalog(
            yearId: $request->filled('year') ? $request->integer('year') : null,
            subjectId: $request->filled('subject') ? $request->integer('subject') : null,
            curriculumType: $request->query('curriculum_type'),
            limit: $request->filled('limit') ? min(24, max(1, $request->integer('limit'))) : null,
        );

        return response()->json([
            'selected_year_id' => $data['selected_year_id'],
            'selected_subject_id' => $data['selected_subject_id'],
            'selected_curriculum_type' => $data['selected_curriculum_type'],
            'years' => $data['years']->map(fn ($y) => [
                'id' => $y->id,
                'name' => $y->name,
            ])->values(),
            'subjects' => $data['subjects']->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
            ])->values(),
            'tracks' => collect($data['tracks'])->map(fn ($t, $key) => [
                'key' => $key,
                'label' => $t['label'],
            ])->values(),
            'packages' => $data['packages']->map(function (array $row) {
                unset($row['model']);

                return $row;
            })->values(),
        ]);
    }
}
