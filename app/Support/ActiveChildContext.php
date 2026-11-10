<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * ولي الأمر يشاهد بيانات ابنه (كورسات / تقويم / إشعارات / تقدم) بنفس منطق المكتب الدراسي.
 */
final class ActiveChildContext
{
    public static function resolve(?User $actor, ?Request $request = null): ?User
    {
        if (! $actor) {
            return null;
        }

        if (! $actor->isParent()) {
            return $actor;
        }

        $request ??= request();
        $childId = (int) ($request->query('child')
            ?: $request->session()->get('active_child_id')
            ?: 0);

        $children = $actor->children()->orderBy('name')->get();
        if ($children->isEmpty()) {
            return null;
        }

        $child = $childId > 0
            ? $children->firstWhere('id', $childId)
            : $children->first();

        if ($child) {
            $request->session()->put('active_child_id', $child->id);
        }

        return $child;
    }
}
