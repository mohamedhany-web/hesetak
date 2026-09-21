#!/usr/bin/env bash
# إعداد نطاق live.glottical.com → LiveKit على نفس VPS مع الإبقاء على live.muallimx.com (Jitsi)
# التشغيل على السيرفر: sudo bash setup-live-hesetak-livekit.sh
set -euo pipefail

DOMAIN="live.glottical.com"
VPS_IP="187.124.36.228"
LIVEKIT_PORT="${LIVEKIT_PORT:-7880}"
API_KEY="${LIVEKIT_API_KEY:-}"
API_SECRET="${LIVEKIT_API_SECRET:-}"
EMAIL="${LETSENCRYPT_EMAIL:-info@glottical.com}"

if [[ -z "${API_KEY}" || -z "${API_SECRET}" ]]; then
  echo "Set LIVEKIT_API_KEY and LIVEKIT_API_SECRET before running this script."
  exit 1
fi

echo "==> التحقق من أن هذا المضيف هو ${VPS_IP}"
HOST_IP="$(hostname -I 2>/dev/null | awk '{print $1}' || true)"
echo "    hostname -I: ${HOST_IP:-unknown}"

echo "==> التأكد أن LiveKit يستجيب على 127.0.0.1:${LIVEKIT_PORT}"
curl -fsS "http://127.0.0.1:${LIVEKIT_PORT}/" | head -c 20 || {
  echo "LiveKit غير متاح على المنفذ ${LIVEKIT_PORT}. أوقف السكربت."
  exit 1
}
echo

NGINX_SITE="/etc/nginx/sites-available/${DOMAIN}.conf"
cat >"${NGINX_SITE}" <<EOF
# Glottical LiveKit — لا تعدّل live.muallimx.com من هنا
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};

    location /.well-known/acme-challenge/ {
        root /var/www/html;
    }

    location / {
        return 301 https://\$host\$request_uri;
    }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${DOMAIN};

    # تُحدَّث تلقائياً بواسطة certbot إن وُجدت مسارات أخرى
    ssl_certificate     /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    location / {
        proxy_pass http://127.0.0.1:${LIVEKIT_PORT};
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_read_timeout 86400s;
        proxy_send_timeout 86400s;
    }
}
EOF

ln -sfn "${NGINX_SITE}" "/etc/nginx/sites-enabled/${DOMAIN}.conf"

# شهادة مؤقتة حتى يعمل certbot إن لم تكن موجودة
if [[ ! -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" ]]; then
  echo "==> إصدار شهادة Let's Encrypt لـ ${DOMAIN}"
  # إعداد HTTP-only مؤقتاً لإصدار الشهادة
  cat >"${NGINX_SITE}" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    location /.well-known/acme-challenge/ { root /var/www/html; }
    location / {
        proxy_pass http://127.0.0.1:${LIVEKIT_PORT};
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_read_timeout 86400s;
    }
}
EOF
  nginx -t && systemctl reload nginx
  certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos -m "${EMAIL}" --redirect || {
    echo "فشل certbot — تأكد أن DNS لـ ${DOMAIN} يشير إلى ${VPS_IP} ثم أعد التشغيل."
    exit 1
  }
fi

nginx -t && systemctl reload nginx

# محاولة إضافة مفتاح Glottical إلى إعداد LiveKit دون حذف مفاتيح Muallimx
for CFG in /etc/livekit.yaml /opt/livekit/livekit.yaml /root/livekit.yaml /etc/livekit/config.yaml; do
  if [[ -f "${CFG}" ]]; then
    echo "==> تحديث مفاتيح LiveKit في ${CFG}"
    if ! grep -q "${API_KEY}" "${CFG}"; then
      cp -a "${CFG}" "${CFG}.bak.$(date +%s)"
      if grep -q '^keys:' "${CFG}"; then
        sed -i "/^keys:/a\\  ${API_KEY}: ${API_SECRET}" "${CFG}"
      else
        printf '\nkeys:\n  %s: %s\n' "${API_KEY}" "${API_SECRET}" >> "${CFG}"
      fi
      systemctl restart livekit 2>/dev/null || systemctl restart livekit-server 2>/dev/null || docker restart livekit 2>/dev/null || true
    fi
    break
  fi
done

echo "==> فحص الصحة"
curl -fsSI "https://${DOMAIN}/" | head -n 8 || true
curl -fsS "https://${DOMAIN}/" || true
echo
echo "تم. أبقِ live.muallimx.com كما هو (Jitsi)."
echo "LIVEKIT_URL=wss://${DOMAIN}"
echo "LIVEKIT_API_KEY=${API_KEY}"
