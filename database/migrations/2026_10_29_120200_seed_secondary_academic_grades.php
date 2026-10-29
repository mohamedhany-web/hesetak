<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_grades') || ! Schema::hasTable('academic_years')) {
            return;
        }

        $years = DB::table('academic_years')
            ->where(function ($q) {
                $q->where('name', 'like', '%ثانوي%')
                    ->orWhere('slug', 'like', '%secondary%');
            })
            ->get(['id', 'name']);

        $grades = [
            ['name' => 'أول ثانوي', 'order' => 1],
            ['name' => 'ثاني ثانوي', 'order' => 2],
            ['name' => 'ثالث ثانوي', 'order' => 3],
        ];

        $now = now();

        foreach ($years as $year) {
            $exists = DB::table('academic_grades')
                ->where('academic_year_id', $year->id)
                ->exists();

            if ($exists) {
                continue;
            }

            foreach ($grades as $grade) {
                DB::table('academic_grades')->insert([
                    'academic_year_id' => $year->id,
                    'name' => $grade['name'],
                    'order' => $grade['order'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('academic_grades') || ! Schema::hasTable('academic_years')) {
            return;
        }

        $yearIds = DB::table('academic_years')
            ->where(function ($q) {
                $q->where('name', 'like', '%ثانوي%')
                    ->orWhere('slug', 'like', '%secondary%');
            })
            ->pluck('id');

        if ($yearIds->isEmpty()) {
            return;
        }

        DB::table('academic_grades')
            ->whereIn('academic_year_id', $yearIds)
            ->whereIn('name', ['أول ثانوي', 'ثاني ثانوي', 'ثالث ثانوي'])
            ->delete();
    }
};
