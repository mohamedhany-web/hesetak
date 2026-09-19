{{-- بطاقة رأي — شريط رئيسية (عرض ثابت) أو شبكة صفحة الآراء ($fluid) — ألوان حصتك --}}
@php
    /** @var \App\Models\SiteTestimonial $t */
    $fluid = $fluid ?? false;
    $featured = $t->is_featured;
    $widthClass = $fluid
        ? 'w-full'
        : ($featured
            ? 'min-w-[min(92vw,380px)] max-w-[min(92vw,380px)] sm:min-w-[360px] sm:max-w-[360px]'
            : 'min-w-[min(88vw,300px)] max-w-[min(88vw,300px)] sm:min-w-[280px] sm:max-w-[280px]');
    $initial = mb_substr($t->author_name ?: 'ر', 0, 1, 'UTF-8');
@endphp
<article class="{{ $widthClass }} {{ $fluid ? '' : 'flex-shrink-0' }} overflow-hidden rounded-2xl flex flex-col border shadow-[0_14px_40px_-22px_rgba(11,61,145,.35)] {{ $featured ? 'border-[#0B3D91] bg-[#0B3D91] text-white' : 'border-[#D7DDE6] bg-white' }}">
    @if($t->isImageType() && $t->publicImageUrl())
        <div class="flex aspect-[4/3] min-h-[10.5rem] max-h-[15rem] w-full items-center justify-center overflow-hidden sm:max-h-[17rem] {{ $featured ? 'bg-white/10' : 'bg-[#F4F7FC]' }}">
            <img src="{{ $t->publicImageUrl() }}" alt="" class="h-auto max-h-full w-auto max-w-full object-contain object-center" loading="lazy" decoding="async">
        </div>
    @endif
    <div class="flex flex-1 flex-col p-5">
        @if($t->body)
            <p class="flex-1 text-sm leading-8 {{ $featured ? 'text-white/95' : 'text-[#5B6577]' }}">
                @if($t->isImageType())
                    {{ Str::limit(strip_tags($t->body), 160) }}
                @else
                    «{{ Str::limit(strip_tags($t->body), 260) }}»
                @endif
            </p>
        @endif
        @if($t->author_name || $t->role_label)
            <div class="mt-4 flex items-center gap-3 border-t pt-4 {{ $featured ? 'border-white/20' : 'border-[#E8EEF8]' }}">
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl text-sm font-black {{ $featured ? 'bg-[#F5B800]/20 text-[#F5B800]' : 'bg-[#E8EEF8] text-[#0B3D91]' }}">{{ $initial }}</span>
                <div class="min-w-0">
                    @if($t->author_name)
                        <p class="truncate text-sm font-bold {{ $featured ? 'text-[#F5B800]' : 'text-[#0B1220]' }}">{{ $t->author_name }}</p>
                    @endif
                    @if($t->role_label)
                        <p class="mt-0.5 truncate text-xs {{ $featured ? 'text-white/75' : 'text-[#5B6577]' }}">{{ $t->role_label }}</p>
                    @endif
                </div>
            </div>
        @endif
    </div>
</article>
