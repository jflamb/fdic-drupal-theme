#!/usr/bin/env bash
# Verifies the DDEV Drupal site created by scripts/bootstrap.sh.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
THEME_DIR="$PROJECT_ROOT/drupal/web/themes/custom/fdic"

info()  { printf '\033[1;34m==>\033[0m %s\n' "$*"; }
ok()    { printf '\033[1;32m OK\033[0m %s\n' "$*"; }
die()   { printf '\033[1;31mERROR\033[0m %s\n' "$*" >&2; exit 1; }

check_command() {
  command -v "$1" &>/dev/null || die "$1 is required but not found."
}

ddev_url() {
  ddev describe -j 2>/dev/null \
    | node -e "let d='';process.stdin.on('data',c=>d+=c);process.stdin.on('end',()=>{try{console.log(JSON.parse(d).raw.primary_url)}catch{process.exit(1)}})" 2>/dev/null \
    || echo "http://fdic-theme.ddev.site"
}

assert_symlink() {
  local path="$1"
  [[ -L "$THEME_DIR/$path" ]] || die "$THEME_DIR/$path is not a symlink."
}

assert_file() {
  local path="$1"
  [[ -f "$THEME_DIR/$path" ]] || die "$THEME_DIR/$path is missing."
}

assert_http_head() {
  local url="$1"
  curl -fsSI "$url" >/dev/null || die "Expected HTTP 2xx response for $url"
}

main() {
  cd "$PROJECT_ROOT"

  info "Checking verification prerequisites"
  check_command ddev
  check_command node
  check_command curl
  ok "Verification prerequisites met"

  info "Validating DDEV configuration"
  ddev debug configyaml >/dev/null
  ok "DDEV configuration is valid"

  info "Checking Drupal bootstrap and active theme"
  ddev drush status --field=bootstrap | grep -q "Successful" || die "Drupal bootstrap is not successful."
  [[ "$(ddev drush config:get system.theme default --format=string)" == "fdic" ]] || die "FDIC theme is not the default theme."
  ok "Drupal is bootstrapped with FDIC as the default theme"

  info "Checking generated theme directory"
  [[ -d "$THEME_DIR" ]] || die "$THEME_DIR does not exist."
  [[ ! -e "$THEME_DIR/drupal" ]] || die "$THEME_DIR/drupal exists; theme directory recurses into generated Drupal app."

  local linked_paths=(
    "config"
    "assets"
    "css"
    "js"
    "templates"
    "fdic.breakpoints.yml"
    "fdic.info.yml"
    "fdic.libraries.yml"
    "fdic.theme"
    "logo.svg"
  )

  local path
  for path in "${linked_paths[@]}"; do
    assert_symlink "$path"
  done
  assert_file "node_modules/@jflamb/fdic-ds-components/styles.css"
  assert_file "node_modules/@jflamb/fdic-ds-tokens/styles.css"
  assert_file "node_modules/@jflamb/fdic-ds-components/dist/register/register-all.js"
  assert_file "node_modules/lit/index.js"
  ok "Generated theme directory contains the expected symlinked source and staged npm runtime"

  local url
  url="$(ddev_url)"
  url="${url%/}"

  info "Checking rendered site and library assets"
  assert_http_head "$url/"
  assert_http_head "$url/themes/custom/fdic/node_modules/@jflamb/fdic-ds-components/styles.css"
  assert_http_head "$url/themes/custom/fdic/node_modules/@jflamb/fdic-ds-tokens/styles.css"
  assert_http_head "$url/themes/custom/fdic/node_modules/@jflamb/fdic-ds-components/dist/register/register-all.js"
  assert_http_head "$url/themes/custom/fdic/node_modules/@jflamb/fdic-ds-components/dist/fd-global-header-drupal.js"
  ok "DDEV site and FDIC Design System assets are reachable"
}

main "$@"
