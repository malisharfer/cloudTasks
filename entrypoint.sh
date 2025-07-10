   #!/bin/bash
set -e

: "${CONTAINER_MODE:=app}"
: "${CONTAINER_PORT:=8000}"
: "${CONTAINER_WORKER_DELAY:=10}"
: "${CONTAINER_WORKER_SLEEP:=5}"
: "${CONTAINER_WORKER_TIMEOUT:=300}"
: "${CONTAINER_WORKER_TRIES:=3}"
: "${CONTAINER_SCHEDULER_INTERVAL:=60}"
: "${APP_ENV:=production}"

ARTISAN="php -d variables_order=EGPCS artisan"

# ... שאר הפונקציות שלך ...

run() {
    case "${CONTAINER_MODE}" in
        app)
            echo "🚀 Running octane..."
            # הפעל את octane עם --no-interaction כדי לא לנסות לעדכן FrankenPHP
            ${ARTISAN} queue:work -vv \
                --no-interaction \
                --tries="${CONTAINER_WORKER_TRIES}" \
                --sleep="${CONTAINER_WORKER_SLEEP}" \
                --timeout="${CONTAINER_WORKER_TIMEOUT}" \
                --delay="${CONTAINER_WORKER_DELAY}" &
            ${ARTISAN} schedule:work &
            
            # הפעל octane עם דגלים שימנעו עדכון FrankenPHP
            exec ${ARTISAN} octane:frankenphp --host=0.0.0.0 --port="${CONTAINER_PORT}" --no-interaction
            ;;
        # ... שאר המקרים ...
    esac
}

setup
run