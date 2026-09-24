#!/usr/bin/env bash
# إعداد نطاق live.hissatak.online → LiveKit على VPS حصتك
# التشغيل على السيرفر: sudo bash setup-live-hesetak-livekit.sh
set -euo pipefail

DOMAIN="live.hissatak.online"
VPS_IP="187.127.87.170"
LIVEKIT_PORT="${LIVEKIT_PORT:-7880}"
API_KEY="${LIVEKIT_API_KEY:-}"
API_SECRET="${LIVEKIT_API_SECRET:-}"
EMAIL="${LETSENCRYPT_EMAIL:-info@hissatak.online}"

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
# حصتك LiveKit — ${DOMAIN}
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
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;
    server_name ${DOMAIN};

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
        proxy_buffering off;
    }
}
EOF

ln -sfn "${NGINX_SITE}" "/etc/nginx/sites-enabled/${DOMAIN}.conf"

if [[ ! -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" ]]; then
  echo "==> إصدار شهادة Let's Encrypt لـ ${DOMAIN}"
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

# أعد كتابة موقع HTTPS الكامل بعد الشهادة
cat >"${NGINX_SITE}" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    location /.well-known/acme-challenge/ { root /var/www/html; }
    location / { return 301 https://\$host\$request_uri; }
}
server {
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;
    server_name ${DOMAIN};
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
        proxy_buffering off;
    }
}
EOF

nginx -t && systemctl reload nginx

install -d -m 755 /opt/livekit/certs
cp -L "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" /opt/livekit/certs/fullchain.pem
cp -L "/etc/letsencrypt/live/${DOMAIN}/privkey.pem" /opt/livekit/certs/privkey.pem
chmod 644 /opt/livekit/certs/fullchain.pem
chmod 600 /opt/livekit/certs/privkey.pem

if [[ -f /opt/livekit/docker-compose.yml ]]; then
  cd /opt/livekit && docker compose restart livekit || docker restart mx-livekit || true
fi

echo "==> جاهز: https://${DOMAIN}/"
