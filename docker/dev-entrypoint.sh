#!/bin/sh
set -e

# Named volume /app/vendor starts empty on a fresh clone. Serialize install so
# migrate, app, queue, and reverb do not race composer on the same volume.
if [ ! -f /app/vendor/autoload.php ]; then
    mkdir -p /app/vendor
    (
        flock 9
        if [ ! -f /app/vendor/autoload.php ]; then
            composer install --no-interaction --prefer-dist
        fi
    ) 9>/app/vendor/.composer-install.lock
fi

exec "$@"
