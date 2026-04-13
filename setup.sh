#!/usr/bin/env bash
set -euo pipefail

PROJECT_NAME="${1:?Usage: ./setup.sh <project-name>}"
PARENT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$PARENT_DIR/$PROJECT_NAME"
MKCERT_REQUEST_FILE="$PROJECT_DIR/.mkcert-request"
MKCERT_NOTICE=0
MKCERT_NOTICE_REASON=""
MKCERT_NOTICE_COMMAND=""

is_valid_mkcert_domain() {
  local domain="$1"
  [[ "$domain" =~ ^[A-Za-z0-9]([A-Za-z0-9-]{0,61}[A-Za-z0-9])?(\.[A-Za-z0-9]([A-Za-z0-9-]{0,61}[A-Za-z0-9])?)+$ ]]
}

process_mkcert_request() {
  local request_domain cert_dir cert_file key_file mkcert_caroot

  if [ ! -f "$MKCERT_REQUEST_FILE" ]; then
    return
  fi

  request_domain="$(<"$MKCERT_REQUEST_FILE")"
  rm -f "$MKCERT_REQUEST_FILE"

  if ! is_valid_mkcert_domain "$request_domain"; then
    echo ">> Invalid mkcert request detected; skipping host certificate creation"
    MKCERT_NOTICE=1
    MKCERT_NOTICE_REASON="The queued mkcert request was invalid."
    return
  fi

  cert_dir="$PROJECT_DIR/docker/caddy/certs"
  cert_file="$cert_dir/_wildcard.$request_domain.pem"
  key_file="$cert_dir/_wildcard.$request_domain-key.pem"
  MKCERT_NOTICE_COMMAND="mkcert -cert-file \"$cert_file\" -key-file \"$key_file\" \"$request_domain\" \"*.$request_domain\""

  if ! command -v mkcert >/dev/null 2>&1; then
    echo ">> mkcert is not installed on the host; skipping automatic certificate creation"
    MKCERT_NOTICE=1
    MKCERT_NOTICE_REASON="mkcert is not installed on the host."
    return
  fi

  if ! mkcert_caroot="$(mkcert -CAROOT 2>/dev/null)" || [ ! -f "$mkcert_caroot/rootCA.pem" ]; then
    echo ">> mkcert local CA is not set up on the host; skipping automatic certificate creation"
    MKCERT_NOTICE=1
    MKCERT_NOTICE_REASON="mkcert is installed, but its local CA is not set up."
    return
  fi

  echo ">> Creating HTTPS certificate on host for $request_domain"
  if ! mkcert -cert-file "$cert_file" -key-file "$key_file" "$request_domain" "*.$request_domain"; then
    echo ">> mkcert failed on the host; skipping automatic certificate creation"
    MKCERT_NOTICE=1
    MKCERT_NOTICE_REASON="mkcert was available, but the certificate command failed."
  fi
}

echo ">> Creating project: $PROJECT_NAME"

DOCKER_FLAGS="--rm -i"
[ -t 0 ] && DOCKER_FLAGS="$DOCKER_FLAGS -t" # add the -t flag if the script is run in an interactive terminal
docker run $DOCKER_FLAGS \
  --user "$(id -u):$(id -g)" \
  -e MAKO_SKIP_AUTOMATIC_MKCERT=1 \
  -v "$PARENT_DIR:/workspace" \
  -v /etc/hosts:/etc/hosts:ro \
  -w /workspace \
  composer:latest \
  composer create-project inventor96/mako-vue "$PROJECT_NAME"

process_mkcert_request

echo ">> Running frontend build"

docker run --rm \
  --user "$(id -u):$(id -g)" \
  -v "$PROJECT_DIR:/app" \
  -w /app \
  node:lts \
  sh -c "npm install && npm run build"

if [ "$MKCERT_NOTICE" -eq 1 ]; then
  echo ">> Notice: mkcert could not be run automatically."
  echo ">> ${MKCERT_NOTICE_REASON:-Ensure mkcert is installed and configured on the host.}"
  if [ -n "$MKCERT_NOTICE_COMMAND" ]; then
    echo ">> Ensure mkcert is installed and/or set up, then run:"
    echo ">> $MKCERT_NOTICE_COMMAND"
  fi
fi

echo ">> Done. Project ready at $PROJECT_DIR"
