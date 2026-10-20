{{-- تفعيل التقاط Figma عبر ?figma=1 فقط --}}
@if(request()->boolean('figma'))
    <script src="https://mcp.figma.com/mcp/html-to-design/capture.js" async></script>
    <style>
        img { opacity: 1 !important; visibility: visible !important; }
        .mc-trial-sheet, .popup-ad, [id*="popup"], .mc-promo-sheet { display: none !important; }
        /*
         * أثناء الكابتشر نُبقي dir=rtl الحقيقي من القالب (مثل الموقع).
         * بعد الرفع لفيجما: طبّق خط عربي (Cairo / Noto Sans Arabic) وليس Inter،
         * وtextAlign = RIGHT — وإلا الحروف تظهر مكسورة أو بوزن واحد.
         */
    </style>
@endif
