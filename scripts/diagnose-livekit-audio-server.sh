#!/usr/bin/env bash
# فحص سيرفر LiveKit — الصوت والـ UDP/TURN
# التشغيل على VPS: sudo bash diagnose-livekit-audio-server.sh
set -euo pipefail

echo "==> LiveKit service"
for svc in livekit livekit-server; do
  systemctl is-active "$svc" 2>/dev/null && echo "  $svc: active" || true
done
docker ps --format '  {{.Names}}: {{.Status}}' 2>/dev/null | grep -i livekit || true

echo
echo "==> livekit.yaml locations"
for CFG in /etc/livekit.yaml /opt/livekit/livekit.yaml /root/livekit.yaml /etc/livekit/config.yaml; do
  if [[ -f "$CFG" ]]; then
    echo "--- $CFG ---"
    grep -E '^(port:|rtc:|turn:|keys:|redis:|region:)' "$CFG" 2>/dev/null || true
    grep -A6 '^rtc:' "$CFG" 2>/dev/null || true
    grep -A8 '^turn:' "$CFG" 2>/dev/null || true
  fi
done

echo
echo "==> Listening ports (7880/7881/5349 + UDP range sample)"
ss -tulpn 2>/dev/null | grep -E '7880|7881|5349|3478|livekit' || true

echo
echo "==> Firewall (ufw)"
if command -v ufw >/dev/null 2>&1; then
  ufw status verbose 2>/dev/null | head -40 || true
else
  echo "  ufw not installed"
fi

echo
echo "==> Health"
curl -fsS http://127.0.0.1:7880/ && echo " (7880 OK)" || echo "7880 FAILED"
curl -fsSI https://live.hissatak.online/ 2>/dev/null | head -5 || true

echo
echo "==> Recommendations for choppy audio"
cat <<'EOF'
1. افتح UDP 50000-60000 (أو نطاق rtc.port_range في livekit.yaml) في ufw/iptables.
2. فعّل use_external_ip: true في rtc: مع IP العام 187.127.87.170.
3. فعّل TURN على المنفذ 5349 مع domain: live.hissatak.online.
4. مثال rtc:
   rtc:
     tcp_port: 7881
     port_range_start: 50000
     port_range_end: 60000
     use_external_ip: true
5. بعد التعديل: systemctl restart livekit
EOF
