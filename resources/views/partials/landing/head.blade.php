{{-- رأس صفحات اللاندنج العامة — MyCourses / حصتك --}}
@php
    $landingCss = $landingCss ?? [];
    $sheets = array_values(array_unique(array_merge(['mycourses'], $landingCss)));
    $themeColor = '#00C2A8';
@endphp
<script>document.documentElement.classList.add('js');</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="theme-color" content="{{ $themeColor }}">
@foreach($sheets as $sheet)
  @php
      $landingCssFile = public_path('css/landing/'.$sheet.'.css');
      if (! is_file($landingCssFile)) {
          $landingCssFile = resource_path('css/landing/'.$sheet.'.css');
      }
      $landingCssVer = is_file($landingCssFile) ? (string) filemtime($landingCssFile) : (string) time();
  @endphp
  <link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => $sheet]) }}?v={{ $landingCssVer }}">
@endforeach
<style>
  :root {
    --p: #00C2A8;
    --p-dark: #007A68;
    --p-deep: #18243C;
    --gold: #FCC430;
  }
  body.sana-home {
    font-family: "Inter", "Rubik", system-ui, sans-serif;
    background: #fff;
    color: #18243C;
  }
</style>
