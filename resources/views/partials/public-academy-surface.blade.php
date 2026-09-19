{{-- أسطح اختيارية لصفحات داخلية قديمة — الكروم العام صار Lasles --}}
<style>
    [x-cloak] {
        display: none !important;
    }
    .page-academy {
        overflow-x: hidden;
        background: linear-gradient(180deg, var(--acad-navy) 0%, var(--acad-navy-gradient) 45%, var(--acad-navy) 100%);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        color: #e8eef8;
        font-size: 16px;
        line-height: 1.65;
    }
    .page-academy .font-display {
        font-family: 'Cairo', 'Tajawal', 'IBM Plex Sans Arabic', system-ui, sans-serif;
    }
    .container-acad {
        max-width: 1280px;
        margin-inline: auto;
        padding-inline: clamp(16px, 4vw, 28px);
    }
    .glass-panel {
        background: rgba(var(--acad-navy-mid-rgb), 0.72);
        border: 1px solid rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }
    .pattern-dots {
        background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, 0.06) 1px, transparent 0);
        background-size: 24px 24px;
    }
    .reveal {
        opacity: 0;
        transform: translateY(22px);
        transition: opacity 0.6s ease, transform 0.6s ease;
    }
    .reveal.revealed {
        opacity: 1;
        transform: translateY(0);
    }
    .btn-stream-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 1rem;
        font-weight: 800;
        color: {{ config('academy-theme.blue') }};
        background: {{ config('academy-theme.yellow') }};
        transition: transform 0.2s ease, filter 0.2s ease, box-shadow 0.2s ease;
    }
    .btn-stream-primary:hover {
        transform: translateY(-1px) scale(1.02);
        filter: brightness(1.05);
        box-shadow: 0 18px 40px -18px rgba({{ config('academy-theme.yellow_rgb') }}, 0.55);
    }
    .btn-stream-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 1rem;
        font-weight: 700;
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(255, 255, 255, 0.06);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        transition: background 0.2s ease;
    }
    .btn-stream-secondary:hover {
        background: rgba(255, 255, 255, 0.1);
    }
    .card-stream {
        border-radius: 1rem;
        overflow: hidden;
        background: rgba(var(--acad-navy-mid-rgb), 0.55);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 18px 40px -26px rgba(0, 0, 0, 0.65);
    }
    .card-stream:hover {
        border-color: rgba(245, 184, 0, 0.45);
        box-shadow: 0 0 0 1px rgba(245, 184, 0, 0.25), 0 24px 50px -28px rgba(0, 212, 255, 0.2);
    }
</style>
