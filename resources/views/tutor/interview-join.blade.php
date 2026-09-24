<!DOCTYPE html>
<html lang="ar" dir="rtl" class="hstk-live-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>مقابلة توظيف | حصتك</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ route('assets.hesetak-live-meeting.css') }}?v=hstk-live-3">
</head>
<body class="hstk-live-body">
<div class="hstk-live-shell" data-role="student" style="height:100vh">
    <div class="hstk-live-body" style="padding:12px;height:100%">
        <div class="hstk-live-stage" style="height:100%">
            @include('partials.hesetak-live-room', [
                'livekitUrl' => $livekitUrl,
                'livekitToken' => $livekitToken,
                'user' => $user,
                'lkRole' => $lkRole ?? 'participant',
                'lkTheme' => 'student',
                'lkLeaveUrl' => route('tutor.interview.pick'),
                'lkStartAudio' => true,
                'lkStartVideo' => true,
                'lkAllowScreenShare' => true,
            ])
        </div>
    </div>
</div>
</body>
</html>
