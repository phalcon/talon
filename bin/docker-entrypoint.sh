#!/usr/bin/env bash
set -euo pipefail

# Wait for services only when they are part of the run
if [ "${TALON_WAIT_FOR_DB:-1}" = "1" ]; then
    bash bin/wait-for-db || true
fi

# Ensure writable output directories exist (never use /tmp)
mkdir -p tests/_output

# Hand off to the container command
exec "$@"
