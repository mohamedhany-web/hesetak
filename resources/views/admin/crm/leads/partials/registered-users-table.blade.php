@php
    $title = $title ?? 'مسجّلون بالمنصة';
    $paginated = $paginated ?? true;
    $registeredUsers = $registeredUsers ?? collect();
    $leadLinksByUserId = $leadLinksByUserId ?? collect();
    $recentRegistrationCutoff = $recentRegistrationCutoff ?? now()->subDays(14);
@endphp
<article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
        <div class="min-w-0">
            <h3 class="text-base font-semibold text-ink">{{ $title }}</h3>
            @if($paginated && method_exists($registeredUsers, 'total'))
                <p class="mt-0.5 text-xs text-muted">{{ number_format($registeredUsers->total()) }} نتيجة</p>
            @else
                <p class="mt-0.5 text-xs text-muted">{{ $registeredUsers->count() }} مستخدم</p>
            @endif
        </div>
    </div>
    <div class="admin-table-wrap overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                <tr>
                    <th class="px-4 py-3 text-start font-medium">#</th>
                    <th class="px-4 py-3 text-start font-medium">النوع</th>
                    <th class="px-4 py-3 text-start font-medium">الاسم</th>
                    <th class="px-4 py-3 text-start font-medium">الدور</th>
                    <th class="px-4 py-3 text-start font-medium">عميل CRM</th>
                    <th class="px-4 py-3 text-start font-medium">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse($registeredUsers as $user)
                    @php
                        $isRecent = $user->created_at && $user->created_at->gte($recentRegistrationCutoff);
                        $linkedLeadId = $leadLinksByUserId[(string) $user->id] ?? $leadLinksByUserId[$user->id] ?? null;
                    @endphp
                    <tr class="hover:bg-canvas/40">
                        <td class="px-4 py-3 tabular-nums text-muted">{{ $user->id }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <span class="inline-flex rounded-lg bg-metal/15 px-2.5 py-1 text-xs font-medium text-metal">مسجّل بالمنصة</span>
                                @if($isRecent)
                                    <span class="inline-flex rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">مسجّل حديثاً</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-ink">{{ $user->name }}</p>
                            <p class="mt-0.5 text-[11px] text-muted">{{ $user->email ?: $user->phone }}</p>
                        </td>
                        <td class="px-4 py-3 text-ink-soft">{{ $user->role === 'parent' ? 'ولي أمر' : 'طالب' }}</td>
                        <td class="px-4 py-3">
                            @if($linkedLeadId)
                                <a href="{{ route('admin.crm.leads.show', $linkedLeadId) }}" class="text-xs font-medium text-accent hover:underline">عميل #{{ $linkedLeadId }}</a>
                            @else
                                <span class="text-xs text-muted">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent" title="ملف المستخدم"><i class="fas fa-user"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-muted">لا يوجد مستخدمون مطابقون.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($paginated && method_exists($registeredUsers, 'hasPages') && $registeredUsers->hasPages())
        <div class="border-t border-line px-4 py-4 sm:px-5">{{ $registeredUsers->links() }}</div>
    @endif
</article>
