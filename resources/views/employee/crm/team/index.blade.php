@extends('layouts.employee')

@section('title', 'فريقي وأداء الأعضاء')
@section('header', 'CRM — فريقي')

@section('content')
<div class="space-y-6">
  @include('partials.crm-employee-nav', ['role' => $role])

  @if(session('success'))<div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm">{{ $errors->first() }}</div>@endif

  @if($memberStats->isNotEmpty())
  <div class="rounded-2xl border bg-white overflow-hidden">
    <div class="px-5 py-3 border-b font-bold">أداء كل عضو في الفريق</div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-xs uppercase">
          <tr>
            <th class="px-4 py-3 text-right">العضو</th>
            <th class="px-4 py-3 text-right">الدور</th>
            <th class="px-4 py-3 text-right">المجموعة</th>
            <th class="px-4 py-3 text-right">إجمالي</th>
            <th class="px-4 py-3 text-right">مفتوحة</th>
            <th class="px-4 py-3 text-right">ناجحة</th>
            <th class="px-4 py-3 text-right">بانتظار الدفع</th>
            <th class="px-4 py-3 text-right">إيراد</th>
            <th class="px-4 py-3 text-right">عمولات</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @foreach($memberStats as $row)
            <tr>
              <td class="px-4 py-3 font-semibold">{{ $row['user_name'] }}</td>
              <td class="px-4 py-3">{{ $row['role_label'] }}</td>
              <td class="px-4 py-3 text-gray-600">{{ $row['group_name'] }}</td>
              <td class="px-4 py-3">{{ $row['total_leads'] }}</td>
              <td class="px-4 py-3">{{ $row['open_leads'] ?? 0 }}</td>
              <td class="px-4 py-3 text-emerald-700 font-bold">{{ $row['closed_won'] }}</td>
              <td class="px-4 py-3 text-amber-700">{{ $row['payment_pending'] ?? 0 }}</td>
              <td class="px-4 py-3">{{ number_format($row['revenue'], 2) }} {{ currency_symbol() }}</td>
              <td class="px-4 py-3">{{ number_format($row['commissions'], 2) }} {{ currency_symbol() }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  @foreach($groups as $group)
  <div class="rounded-2xl border bg-white p-6 space-y-4">
    <div class="flex justify-between items-center gap-3">
      <h2 class="text-lg font-black">{{ $group->name }}</h2>
      <span class="text-xs bg-sky-100 text-sky-800 px-2 py-1 rounded">{{ $group->leads_count }} عميل</span>
    </div>

    <div class="space-y-2">
      @forelse($group->activeMembers as $member)
        <div class="flex justify-between items-center border rounded-lg px-4 py-2 text-sm">
          <div>
            <span class="font-semibold">{{ $member->user?->name }}</span>
            <span class="text-gray-500 mr-2">— {{ $member->role === 'marketing' ? 'تسويق' : 'مبيعات' }}</span>
          </div>
          @if($canManage)
          <form method="POST" action="{{ route('employee.crm.team.members.destroy', [$group, $member]) }}" onsubmit="return confirm('إزالة العضو من الفريق؟')">
            @csrf @method('DELETE')
            <button class="text-rose-600 text-xs font-bold">إزالة</button>
          </form>
          @endif
        </div>
      @empty
        <p class="text-sm text-gray-500">لا يوجد أعضاء نشطون.</p>
      @endforelse
    </div>

    @if($canManage)
    <form method="POST" action="{{ route('employee.crm.team.members.store', $group) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-3 border-t">
      @csrf
      <select name="user_id" class="rounded-lg border px-3 py-2 text-sm" required>
        <option value="">اختر موظفاً</option>
        <optgroup label="تسويق">
          @foreach($marketingUsers as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
        </optgroup>
        <optgroup label="مبيعات">
          @foreach($salesUsers as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
        </optgroup>
      </select>
      <select name="role" class="rounded-lg border px-3 py-2 text-sm" required>
        <option value="marketing">تسويق</option>
        <option value="sales">مبيعات</option>
      </select>
      <button class="px-4 py-2 rounded-xl bg-sky-600 text-white font-bold text-sm">إضافة عضو</button>
    </form>
    @endif
  </div>
  @endforeach

  @if($groups->isEmpty())
    <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm">لم يُعيَّن لك فريق بعد. تواصل مع الإدارة لربطك كقائد فريق.</div>
  @endif
</div>
@endsection
