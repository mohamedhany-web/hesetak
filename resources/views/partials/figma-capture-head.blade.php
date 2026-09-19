{{-- تفعيل التقاط Figma عبر ?figma=1 فقط --}}
@if(request()->boolean('figma'))
    <script src="https://mcp.figma.com/mcp/html-to-design/capture.js" async></script>
    <style>
        img { opacity: 1 !important; visibility: visible !important; }
        .mc-trial-sheet, .popup-ad, [id*="popup"], .mc-promo-sheet { display: none !important; }
        /*
         * Figma html-to-design يعكس إحداثيات dir=rtl مرة ثانية فيظهر التصميم LTR.
         * أثناء الكابتشر: html يبقى dir=ltr (يُضبط في القالب) والـ CSS يحافظ على الشكل RTL.
         */
        @if(app()->getLocale() === 'ar')
        html, body { direction: rtl !important; }
        @endif
    </style>
@endif
