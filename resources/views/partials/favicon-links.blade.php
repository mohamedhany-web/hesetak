{{-- أيقونة التبويب: شعار لوحة التحكم إن وُجد، وإلا شعار حصتك --}}
@php
    $brandIcon = \App\Services\AdminPanelBranding::logoPublicUrl();
    $defaultIcon = public_img_url('brand/hesetak-favicon-32.png');
    $appleIcon = public_img_url('brand/hesetak-apple-touch.png');
@endphp
@if($brandIcon && ! str_starts_with((string) $brandIcon, 'data:'))
    <link rel="icon" href="{{ $brandIcon }}" sizes="any">
    <link rel="shortcut icon" href="{{ $brandIcon }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $brandIcon }}">
    <link rel="icon" href="{{ $brandIcon }}" sizes="32x32">
    <link rel="icon" href="{{ $brandIcon }}" sizes="16x16">
@else
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $defaultIcon }}">
    <link rel="icon" type="image/png" sizes="180x180" href="{{ $appleIcon }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $appleIcon }}">
    <link rel="shortcut icon" href="{{ $defaultIcon }}">
@endif
