@extends('emails.layouts.brand')

@php
    $emailTitle = $broadcast->subject;
    $emailHeading = $broadcast->subject;
    $emailEyebrow = 'رسالة من حصتك';
    $emailPreheader = \Illuminate\Support\Str::limit(strip_tags((string) $broadcast->body), 90, '…');
@endphp

@section('content')
    <div style="font-size:15px;line-height:1.9;color:#1e293b;white-space:pre-wrap;">{{ $broadcast->body }}</div>
@endsection
