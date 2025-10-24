#!/usr/bin/env sh
set -eu

php artisan schedule:work --verbose &
SCHED_PID=$!


php-fpm --nodaemonize &
PHPFPM_PID=$!

echo "Started: scheduler PID=$SCHED_PID, php-fpm PID=$PHPFPM_PID"

forward_signal() {
  echo "Signal $1 received, forwarding..."
  kill -"$1" "$SCHED_PID" "$PHPFPM_PID" 2>/dev/null || true
}
trap 'forward_signal TERM' TERM
trap 'forward_signal INT'  INT
trap 'forward_signal QUIT' QUIT


while :; do
  if ! kill -0 "$SCHED_PID" 2>/dev/null; then
    echo "Scheduler exited. Stopping php-fpm..."
    kill -TERM "$PHPFPM_PID" 2>/dev/null || true
    wait "$PHPFPM_PID" 2>/dev/null || true
    exit 1
  fi

  if ! kill -0 "$PHPFPM_PID" 2>/dev/null; then
    echo "php-fpm exited. Stopping scheduler..."
    kill -TERM "$SCHED_PID" 2>/dev/null || true
    wait "$SCHED_PID" 2>/dev/null || true
    exit 1
  fi

  sleep 2
done
