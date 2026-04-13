#!/usr/bin/env bash
set -euo pipefail

print_usage() {
  echo "Usage:"
  echo "  ./setup.sh                # initialize this already-cloned repository"
  echo "  ./setup.sh <project-name> # create and initialize a new project"
  echo "  ./setup.sh --help"
}

MODE=""
PROJECT_NAME=""
PARENT_DIR="$(pwd)"
PROJECT_DIR=""
MKCERT_REQUEST_FILE=""
MKCERT_NOTICE=0
MKCERT_NOTICE_REASON=""
MKCERT_NOTICE_COMMAND=""

if [ "$#" -eq 0 ]; then
  MODE="existing"
  PROJECT_DIR="$PARENT_DIR"
elif [ "$#" -eq 1 ]; then
  case "$1" in
    -h|--help)
      print_usage
      exit 0
      ;;
    *)
      MODE="new"
      PROJECT_NAME="$1"
      PROJECT_DIR="$PARENT_DIR/$PROJECT_NAME"
      ;;
  esac
else
  print_usage
  exit 1
fi

MKCERT_REQUEST_FILE="$PROJECT_DIR/.mkcert-request"

get_env_value() {
  local key="$1"
  local env_file="$2"
  local line value

  line="$(grep -E "^${key}=" "$env_file" | tail -n 1 || true)"
  value="${line#*=}"
  value="${value%\"}"
  value="${value#\"}"

  printf '%s' "$value"
}

maybe_update_hosts_file() {
  local env_file listen_ip listen_domain hosts_line run_hosts_reply

  env_file="$PROJECT_DIR/.env"
  if [ ! -f "$env_file" ]; then
    return
  fi

  listen_ip="$(get_env_value "LISTEN_IP" "$env_file")"
  listen_domain="$(get_env_value "LISTEN_DOMAIN" "$env_file")"
  if [ -z "$listen_ip" ] || [ -z "$listen_domain" ]; then
    return
  fi

  hosts_line="$listen_ip $listen_domain db.$listen_domain mail.$listen_domain"

  if grep -Fq "$hosts_line" /etc/hosts; then
    return
  fi

  if [ ! -t 0 ]; then
    echo ">> Add this entry to /etc/hosts to enable local domains:"
    echo ">> $hosts_line"
    echo ">> Suggested command: sudo sh -c 'echo \"$hosts_line\" >> /etc/hosts'"
    return
  fi

  echo ">> setup.sh can run the suggested /etc/hosts update command now"
  echo ">> Command: sudo sh -c 'echo \"$hosts_line\" >> /etc/hosts'"
  read -r -p ">> Run this command automatically? [Y/n] " run_hosts_reply
  case "$run_hosts_reply" in
    y|Y|yes|YES|"")
      sudo sh -c "echo \"$hosts_line\" >> /etc/hosts"
      ;;
  esac
}

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

if [ "$MODE" = "existing" ]; then
  if [ ! -f "$PROJECT_DIR/composer.json" ] || [ ! -f "$PROJECT_DIR/app/reactor" ]; then
    echo ">> Existing-clone mode requires running from the repository root"
    echo ">> Missing required files in: $PROJECT_DIR"
    echo ">> Expected: composer.json and app/reactor"
    exit 1
  fi

  echo ">> Initializing existing project at: $PROJECT_DIR"
else
  echo ">> Creating project: $PROJECT_NAME"
fi

DOCKER_FLAGS="--rm -i"
[ -t 0 ] && DOCKER_FLAGS="$DOCKER_FLAGS -t" # add the -t flag if the script is run in an interactive terminal

if [ "$MODE" = "existing" ]; then
  docker run $DOCKER_FLAGS \
    --user "$(id -u):$(id -g)" \
    -e MAKO_SKIP_AUTOMATIC_MKCERT=1 \
    -v "$PROJECT_DIR:/app" \
    -v /etc/hosts:/etc/hosts:ro \
    -w /app \
    composer:latest \
    sh -c "composer install && php app/reactor post-create-project"
else
  docker run $DOCKER_FLAGS \
    --user "$(id -u):$(id -g)" \
    -e MAKO_SKIP_AUTOMATIC_MKCERT=1 \
    -v "$PARENT_DIR:/workspace" \
    -v /etc/hosts:/etc/hosts:ro \
    -w /workspace \
    composer:latest \
    composer create-project inventor96/mako-vue "$PROJECT_NAME"
fi

maybe_update_hosts_file

process_mkcert_request

echo ">> Running frontend build"

docker run $DOCKER_FLAGS \
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
