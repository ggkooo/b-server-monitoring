#!/bin/bash
set -e

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    echo "[entrypoint] APP_KEY not set — generating one..."
    php artisan key:generate --force
fi

# Ensure Reverb TLS certificate exists for encrypted upstream traffic.
REVERB_TLS_CERT="${REVERB_TLS_CERT:-/tmp/reverb/certs/reverb.crt}"
REVERB_TLS_KEY="${REVERB_TLS_KEY:-/tmp/reverb/certs/reverb.key}"
REVERB_TLS_VERIFY_PEER="${REVERB_TLS_VERIFY_PEER:-false}"
export REVERB_TLS_CERT REVERB_TLS_KEY REVERB_TLS_VERIFY_PEER

if [ ! -f "$REVERB_TLS_CERT" ] || [ ! -f "$REVERB_TLS_KEY" ]; then
    echo "[entrypoint] Reverb TLS cert/key not found — generating self-signed cert..."
    mkdir -p "$(dirname "$REVERB_TLS_CERT")"
    mkdir -p "$(dirname "$REVERB_TLS_KEY")"

    cat >/tmp/reverb-openssl.cnf <<'EOF'
[req]
distinguished_name=dn
x509_extensions=v3
prompt=no
[dn]
CN=backend
[v3]
subjectAltName=DNS:backend,DNS:localhost,IP:127.0.0.1
EOF

    openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
        -keyout "$REVERB_TLS_KEY" \
        -out "$REVERB_TLS_CERT" \
        -config /tmp/reverb-openssl.cnf

    rm -f /tmp/reverb-openssl.cnf
fi

_term() {
    echo "[entrypoint] Caught signal, shutting down..."
    kill "$REVERB_PID" "$SCHEDULE_PID" 2>/dev/null
    exit 0
}
trap _term SIGTERM SIGINT

echo "[entrypoint] Starting Laravel Reverb with internal TLS (wss://0.0.0.0:8080)..."
php artisan reverb:start --host=0.0.0.0 --port=8080 &
REVERB_PID=$!

echo "[entrypoint] Starting metrics scheduler..."
php artisan schedule:work &
SCHEDULE_PID=$!

echo "[entrypoint] Both processes running. PID reverb=$REVERB_PID scheduler=$SCHEDULE_PID"

wait $REVERB_PID $SCHEDULE_PID
