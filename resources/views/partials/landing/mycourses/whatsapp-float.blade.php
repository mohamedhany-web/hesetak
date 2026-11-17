@php
    $waFloat = trim((string) (($footer['whatsapp_url'] ?? null) ?: (\App\Services\PublicFooterSettings::payload()['whatsapp_url'] ?? '')));
    if ($waFloat === '' || $waFloat === '#') {
        $waFloat = null;
    }
@endphp
@if($waFloat)
<a href="{{ $waFloat }}"
   class="mc-wa-float"
   target="_blank"
   rel="noopener noreferrer"
   aria-label="WhatsApp">
  <i class="fab fa-whatsapp" aria-hidden="true"></i>
</a>
<style>
.mc-wa-float {
  position: fixed;
  z-index: 70;
  inset-inline-end: 1.1rem;
  bottom: 1.1rem;
  width: 3.35rem;
  height: 3.35rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #25D366;
  color: #fff;
  font-size: 1.55rem;
  box-shadow: 0 12px 28px -10px rgba(37, 211, 102, 0.65);
  text-decoration: none;
  transition: transform .2s ease, box-shadow .2s ease;
}
.mc-wa-float:hover { transform: translateY(-2px) scale(1.04); color: #fff; }
@media (max-width: 640px) {
  .mc-wa-float { inset-inline-end: .85rem; bottom: .85rem; width: 3.1rem; height: 3.1rem; }
}
</style>
@endif
