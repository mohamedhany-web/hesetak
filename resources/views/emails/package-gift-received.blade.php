<x-mail::message>
# وصلك هدية باقة من حصتك

مرحباً {{ $gift->recipient_name ?: 'عزيزي الطالب' }}،

أهدى لك **{{ $gift->buyer?->name ?? 'صديق' }}** باقة **{{ $gift->servicePackage?->name ?? 'حصص تعليمية' }}**.

@if($gift->message)
> {{ $gift->message }}
@endif

عدد الحصص تقريباً: **{{ $gift->servicePackage?->units_count ?? '—' }}**

@if($recipientIsNew && $resetUrl)
حسابك جاهز على البريد {{ $gift->recipient_email }}. عيّن كلمة المرور من الرابط:

<x-mail::button :url="$resetUrl">
تعيين كلمة المرور والدخول
</x-mail::button>
@else
<x-mail::button :url="route('login')">
تسجيل الدخول لمنصتك
</x-mail::button>
@endif

يمكنك أيضاً فتح صفحة الهدية:

<x-mail::button :url="$gift->claimUrl()">
عرض الهدية
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
