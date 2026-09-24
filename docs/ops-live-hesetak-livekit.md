# تشغيل LiveKit لـ حصتك على VPS 187.127.87.170

## النطاق
- `live.hissatak.online` — نطاق LiveKit لمنصة حصتك.

## 1) DNS عند Hostinger (`hissatak.online`)

| Type | Name | Value | TTL |
|------|------|-------|-----|
| A | live | 187.127.87.170 | 300 |

```bash
dig +short live.hissatak.online A
# يجب أن يظهر: 187.127.87.170
```

## 2) VPS (SSH إلى 187.127.87.170)

```bash
export LIVEKIT_API_KEY='...'
export LIVEKIT_API_SECRET='...'
sudo -E bash scripts/setup-live-hesetak-livekit.sh
```

- يضيف nginx لـ `live.hissatak.online` → `127.0.0.1:7880`
- يصدر شهادة Let's Encrypt
- ينسخ الشهادات إلى `/opt/livekit/certs/` لـ TURN

## 3) Laravel على Hostinger

```env
LIVEKIT_URL=wss://live.hissatak.online
LIVEKIT_PUBLIC_HOST=live.hissatak.online
LIVEKIT_HTTP_URL=http://187.127.87.170:7880
LIVEKIT_API_KEY=your_livekit_api_key
LIVEKIT_API_SECRET=your_livekit_api_secret
LIVEKIT_TOKEN_TTL=21600
```

```bash
php artisan config:clear
php artisan livekit:provision-hesetak --set-default
```

## 4) تحقق

- `curl -I https://live.hissatak.online/` → 200
- `curl http://187.127.87.170:7880/` → `OK`
- Container `mx-livekit` = healthy
- حصة تجريبية: معلم ينشر + طالب يشاهد

## TURN

LiveKit يوزّع بيانات TURN تلقائياً عند `turn.enabled: true`.
شهادات `live.hissatak.online` في `/opt/livekit/certs/`.

## ملاحظات

- بدون سجل DNS + شهادة SSL لن يعمل `wss://live.hissatak.online` من المتصفح على HTTPS.
- افتح نفس المنافذ في Firewall لوحة الـ VPS (UDP media + TURN).
