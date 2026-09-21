# تشغيل LiveKit لـ حصتك على VPS 187.124.36.228

## الهدف
- `live.glottical.com` هو نطاق LiveKit لمنصة حصتك.
- كل غرف البث وClassroom تعمل عبر LiveKit فقط.

## 1) DNS (Hostinger — dns.hostinger.com)
في لوحة DNS لنطاق `glottical.com` أضف:

| Type | Name | Value | TTL |
|------|------|-------|-----|
| A | live | 187.124.36.228 | 300 |

تحقق:
```bash
nslookup live.glottical.com
```
يجب أن يظهر `187.124.36.228`.

## 2) VPS (SSH إلى 187.124.36.228)
ارفع/انسخ ثم نفّذ:
```bash
sudo bash scripts/setup-live-hesetak-livekit.sh
```
السكربت:
- يضيف nginx لـ `live.glottical.com` → `127.0.0.1:7880`
- يصدر شهادة Let's Encrypt
- يضبط مفاتيح LiveKit في `livekit.yaml`

## 3) منصة حصتك
في `.env` (محلياً وعلى الإنتاج):
```
LIVEKIT_URL=wss://live.glottical.com
LIVEKIT_PUBLIC_HOST=live.glottical.com
LIVEKIT_HTTP_URL=http://187.124.36.228:7880
LIVEKIT_API_KEY=your_livekit_api_key
LIVEKIT_API_SECRET=your_livekit_api_secret
```

ثم:
```bash
php artisan config:clear
php artisan livekit:provision-hesetak --set-default
```

## 4) تحقق
- `curl -I https://live.glottical.com/` → 200
- `curl http://187.124.36.228:7880/` → `OK`
- غرفة بث معلم/طالب وClassroom تحمّل عميل LiveKit من jsDelivr

## 5) جدار ناري Hostinger (مهم جداً للصوت)
في لوحة VPS → Firewall افتح:

| البروتوكول | المنفذ | الغرض |
|------------|--------|--------|
| TCP | 443, 7880, 7881 | WebSocket + ICE-TCP |
| UDP | 50000–60000 | وسائط WebRTC (صوت/فيديو) |
| UDP | 34789 | TURN LiveKit |
| TCP | 5351 | TURN TLS LiveKit |
| UDP | 30000–40000 | TURN relay (احتياطي عند ضعف UDP المباشر) |

بدون UDP 50000–60000 يبدأ الصوت جيداً ثم يتباطأ ويقطع بعد 10–15 دقيقة.

## 6) TURN
LiveKit يوزّع بيانات TURN تلقائياً على العملاء عند `turn.enabled: true` في `livekit.yaml`.
شهادات `live.glottical.com` تُنسخ إلى `/opt/livekit/certs/` وتُربط في docker-compose.

## ملاحظات
- LiveKit يعمل حالياً على المنفذ `7880` على الـ VPS.
- بدون سجل DNS + شهادة SSL لن يعمل `wss://live.glottical.com` من المتصفح على HTTPS.
- من لوحة الإدارة → سيرفرات البث: أضف سيرفر LiveKit واضغط «استخدام كنطاق افتراضي».
