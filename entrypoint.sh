#!/bin/bash
set -e

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    echo "[entrypoint] APP_KEY not set — generating one..."
    php artisan key:generate --force
fi

_term() {
    echo "[entrypoint] Caught signal, shutting down..."
    kill "$REVERB_PID" "$SCHEDULE_PID" 2>/dev/null
    exit 0
}
trap _term SIGTERM SIGINT

echo "[entrypoint] Starting Laravel Reverb (ws://0.0.0.0:8080)..."
php artisan reverb:start --host=0.0.0.0 --port=8080 &
REVERB_PID=$!

echo "[entrypoint] Starting metrics scheduler..."
php artisan schedule:work &
SCHEDULE_PID=$!

echo "[entrypoint] Both processes running. PID reverb=$REVERB_PID scheduler=$SCHEDULE_PID"

wait $REVERB_PID $SCHEDULE_PID
