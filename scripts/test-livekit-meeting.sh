#!/usr/bin/env bash
# اختبار LiveKit: API + TURN + غرفة تجريبية
# Usage: LIVEKIT_API_KEY=... LIVEKIT_API_SECRET=... bash scripts/test-livekit-meeting.sh
set -euo pipefail

API_KEY="${LIVEKIT_API_KEY:-}"
API_SECRET="${LIVEKIT_API_SECRET:-}"
HTTP_URL="${LIVEKIT_HTTP_URL:-http://127.0.0.1:7880}"
WS_HOST="${LIVEKIT_PUBLIC_HOST:-live.hissatak.online}"
ROOM="mx-audio-test-$(date +%s)"

if [[ -z "$API_KEY" || -z "$API_SECRET" ]]; then
  echo "Set LIVEKIT_API_KEY and LIVEKIT_API_SECRET"
  exit 1
fi

b64url() { openssl base64 -e -A | tr '+/' '-_' | tr -d '='; }

make_jwt() {
  local payload="$1"
  local header='{"alg":"HS256","typ":"JWT"}'
  local h p sig
  h=$(printf '%s' "$header" | b64url)
  p=$(printf '%s' "$payload" | b64url)
  sig=$(printf '%s' "${h}.${p}" | openssl dgst -sha256 -hmac "$API_SECRET" -binary | b64url)
  printf '%s.%s.%s\n' "$h" "$p" "$sig"
}

now=$(date +%s)
exp=$((now + 600))

admin_payload=$(cat <<JSON
{"iss":"$API_KEY","sub":"test-admin","nbf":$((now-10)),"exp":$exp,"video":{"roomAdmin":true,"roomCreate":true}}
JSON
)
ADMIN_JWT=$(make_jwt "$admin_payload")

join_payload() {
  local id="$1" name="$2"
  cat <<JSON
{"iss":"$API_KEY","sub":"$id","nbf":$((now-10)),"exp":$exp,"name":"$name","video":{"roomJoin":true,"room":"$ROOM","canPublish":true,"canSubscribe":true}}
JSON
}

echo "==> Health"
curl -fsS "$HTTP_URL/" && echo " OK"

echo
echo "==> CreateRoom $ROOM"
curl -fsS -X POST "$HTTP_URL/twirp/livekit.RoomService/CreateRoom" \
  -H "Authorization: Bearer $ADMIN_JWT" \
  -H 'Content-Type: application/json' \
  -d "{\"name\":\"$ROOM\",\"empty_timeout\":120,\"max_participants\":4}" | head -c 400
echo

echo
echo "==> ListRooms"
curl -fsS -X POST "$HTTP_URL/twirp/livekit.RoomService/ListRooms" \
  -H "Authorization: Bearer $ADMIN_JWT" \
  -H 'Content-Type: application/json' \
  -d '{}' | grep -o "$ROOM" && echo " room listed"

HOST_JWT=$(make_jwt "$(join_payload test-host-1 Instructor)")
STUD_JWT=$(make_jwt "$(join_payload test-student-1 Student)")

echo
echo "==> Join tokens (first 80 chars)"
echo "Host:   ${HOST_JWT:0:80}..."
echo "Student:${STUD_JWT:0:80}..."

echo
echo "==> WSS + TURN check via livekit-server logs hint"
echo "WS URL: wss://$WS_HOST"
echo "Room:   $ROOM"

echo
echo "==> UDP/TCP ports on this host"
ss -tulpn 2>/dev/null | grep -E '7880|7881|34789|5351|livekit' || true

echo
echo "==> DeleteRoom cleanup"
curl -fsS -X POST "$HTTP_URL/twirp/livekit.RoomService/DeleteRoom" \
  -H "Authorization: Bearer $ADMIN_JWT" \
  -H 'Content-Type: application/json' \
  -d "{\"room\":\"$ROOM\"}" >/dev/null && echo "deleted $ROOM"

echo
echo "ALL API TESTS PASSED"
