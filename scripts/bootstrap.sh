#!/usr/bin/env bash
# Bootstraps a disposable Drupal site with the FDIC theme active.
# Idempotent — safe to re-run. Each step skips if already completed.
#
# Prerequisites: ddev, docker, node 18+, npm
#
# Usage:
#   scripts/bootstrap.sh          # full setup
#   scripts/bootstrap.sh --quick  # skip sample content

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
DRUPAL_DIR="$PROJECT_ROOT/drupal"
THEME_DIR="$DRUPAL_DIR/web/themes/custom/fdic"

# Container-relative paths (DDEV mounts project root at /var/www/html).
CONTAINER_DRUPAL_DIR="/var/www/html/drupal"
CONTAINER_BLOCKS_SCRIPT="/var/www/html/scripts/php/place-blocks.php"
CONTAINER_SEED_SCRIPT="/var/www/html/scripts/php/seed-content.php"

SEED_CONTENT=true
if [[ "${1:-}" == "--quick" ]]; then
  SEED_CONTENT=false
fi

# ---------- helpers ----------

info()  { printf '\033[1;34m==>\033[0m %s\n' "$*"; }
ok()    { printf '\033[1;32m OK\033[0m %s\n' "$*"; }
warn()  { printf '\033[1;33mWARN\033[0m %s\n' "$*" >&2; }
die()   { printf '\033[1;31mERROR\033[0m %s\n' "$*" >&2; exit 1; }

check_command() {
  command -v "$1" &>/dev/null || die "$1 is required but not found. $2"
}

is_ddev_preinstall_skeleton() {
  [[ -d "$DRUPAL_DIR" ]] || return 1

  local unexpected
  unexpected="$(
    find "$DRUPAL_DIR" -mindepth 1 \
      ! -path "$DRUPAL_DIR/web" \
      ! -path "$DRUPAL_DIR/web/sites" \
      ! -path "$DRUPAL_DIR/web/sites/default" \
      ! -path "$DRUPAL_DIR/web/sites/default/.gitignore" \
      ! -path "$DRUPAL_DIR/web/sites/default/files" \
      ! -path "$DRUPAL_DIR/web/sites/default/files/sync" \
      ! -path "$DRUPAL_DIR/web/sites/default/settings.ddev.php" \
      ! -path "$DRUPAL_DIR/web/sites/default/settings.php" \
      -print -quit
  )"

  [[ -z "$unexpected" ]]
}

create_recommended_project() {
  info "Creating Drupal project (drupal/recommended-project ^10)"
  ddev composer create-project drupal/recommended-project:^10 --no-interaction -y
  ok "Drupal project created"
}

# ---------- prerequisites ----------

check_prerequisites() {
  info "Checking prerequisites"
  check_command ddev   "Install: https://ddev.readthedocs.io/en/stable/"
  check_command docker "Install Docker Desktop, OrbStack, or Colima."
  check_command node   "Install Node 18+ via nvm or brew."
  check_command npm    "Installed with Node."

  # Verify Docker is reachable (ddev needs it before ddev start).
  if ! docker info &>/dev/null; then
    die "Docker is not running. Start Docker Desktop (or colima/orbstack) and retry."
  fi

  ok "All prerequisites met"
}

# ---------- DDEV + Drupal ----------

start_ddev() {
  info "Starting DDEV"
  cd "$PROJECT_ROOT"
  ddev start
  ok "DDEV running"
}

create_drupal_project() {
  if [[ -f "$DRUPAL_DIR/composer.json" ]]; then
    info "Drupal project exists — running composer install"
    ddev composer install --no-interaction
  elif [[ -d "$DRUPAL_DIR" ]] && [[ -n "$(ls -A "$DRUPAL_DIR" 2>/dev/null)" ]]; then
    if is_ddev_preinstall_skeleton; then
      info "Removing DDEV-generated pre-install Drupal skeleton"
      rm -rf "$DRUPAL_DIR"
      create_recommended_project
    else
      # drupal/ exists but has no composer.json — partial or corrupt state.
      warn "drupal/ exists but has no composer.json."
      warn "Remove it and re-run: rm -rf drupal/ && scripts/bootstrap.sh"
      die "Cannot continue from partial Drupal state."
    fi
  else
    create_recommended_project
  fi

  # Ensure Drush is available — recommended-project does not include it.
  if ! ddev exec test -f "$CONTAINER_DRUPAL_DIR/vendor/bin/drush"; then
    info "Installing Drush"
    ddev composer require drush/drush --no-interaction
    ok "Drush installed"
  fi

  # Sanity check: Drupal core must be present.
  if ! ddev exec test -d "$CONTAINER_DRUPAL_DIR/web/core"; then
    die "drupal/web/core not found after composer install. Remove drupal/ and re-run."
  fi
}

install_drupal_site() {
  if ddev drush status --field=bootstrap 2>/dev/null | grep -q "Successful"; then
    info "Drupal already installed — skipping site:install"
    return
  fi

  info "Installing Drupal (standard profile)"
  ddev drush site:install standard \
    --account-name=admin \
    --account-pass=admin \
    --site-name="FDIC Theme Dev" \
    --no-interaction -y
  ok "Drupal installed"
}

# ---------- theme wiring ----------

link_theme() {
  info "Linking theme files into Drupal"
  rm -rf "$THEME_DIR"
  mkdir -p "$THEME_DIR"

  # Relative symlinks work both on the host and inside DDEV, where the project
  # root is mounted at /var/www/html. Link only the actual theme surface so
  # Drupal extension discovery cannot recurse through the generated drupal/ app.
  local theme_root="../../../../.."
  local paths=(
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
  for path in "${paths[@]}"; do
    ln -s "$theme_root/$path" "$THEME_DIR/$path"
  done

  ok "Theme files linked at $THEME_DIR"
}

stage_theme_node_modules() {
  info "Staging browser-served npm runtime into Drupal theme"

  local runtime_root="$THEME_DIR/node_modules"
  local theme_node_modules="$PROJECT_ROOT/node_modules"
  rm -rf "$runtime_root"
  mkdir -p "$runtime_root/@jflamb" "$runtime_root/@lit" "$runtime_root/@xmldom"

  cp -LR "$theme_node_modules/@jflamb/fdic-ds-components" "$runtime_root/@jflamb/"
  cp -LR "$theme_node_modules/@jflamb/fdic-ds-tokens" "$runtime_root/@jflamb/"
  cp -R "$theme_node_modules/lit" "$runtime_root/"
  cp -R "$theme_node_modules/lit-html" "$runtime_root/"
  cp -R "$theme_node_modules/lit-element" "$runtime_root/"
  cp -R "$theme_node_modules/@lit/reactive-element" "$runtime_root/@lit/"
  cp -R "$theme_node_modules/@xmldom/xmldom" "$runtime_root/@xmldom/"

  ok "Runtime npm packages staged into $runtime_root"
}

install_theme_deps() {
  # Check for actual required files, not just the directory existing.
  local components_css="$PROJECT_ROOT/node_modules/@jflamb/fdic-ds-components/styles.css"
  local register_js="$PROJECT_ROOT/node_modules/@jflamb/fdic-ds-components/dist/register/register-all.js"

  if [[ -f "$components_css" ]] && [[ -f "$register_js" ]]; then
    info "Theme npm deps already installed"
    return
  fi

  info "Installing theme npm dependencies"
  (cd "$PROJECT_ROOT" && npm install) || {
    warn "npm install failed."
    warn "Ensure NODE_AUTH_TOKEN or an npm login can read @jflamb packages from GitHub Packages."
    die "Cannot continue without the published @jflamb FDIC Design System packages."
  fi

  # Verify the critical files actually arrived.
  if [[ ! -f "$components_css" ]] || [[ ! -f "$register_js" ]]; then
    die "@jflamb FDIC Design System packages installed but expected files are missing. Check the published package contents."
  fi

  ok "npm dependencies installed"
}

enable_theme() {
  info "Enabling FDIC theme"
  ddev drush theme:enable fdic -y
  ddev drush config:set system.theme default fdic -y
  ok "FDIC is now the default theme"
}

# ---------- blocks + content ----------

place_blocks() {
  info "Placing blocks for FDIC theme regions"
  ddev drush php:script "$CONTAINER_BLOCKS_SCRIPT"
  ok "Blocks placed"
}

seed_content() {
  if [[ "$SEED_CONTENT" != true ]]; then
    info "Skipping sample content (--quick)"
    return
  fi

  info "Creating sample content"
  ddev drush php:script "$CONTAINER_SEED_SCRIPT"
  ok "Sample content created"
}

# ---------- finish ----------

rebuild_cache() {
  info "Rebuilding Drupal cache"
  ddev drush cache:rebuild
}

print_summary() {
  local url
  # Node is already a checked prerequisite, so use it to parse the JSON
  # reliably instead of grepping ddev describe text output.
  url="$(ddev describe -j 2>/dev/null \
    | node -e "let d='';process.stdin.on('data',c=>d+=c);process.stdin.on('end',()=>{try{console.log(JSON.parse(d).raw.primary_url)}catch{process.exit(1)}})" 2>/dev/null \
    || echo "http://fdic-theme.ddev.site")"

  echo ""
  echo "================================================"
  echo " FDIC theme dev site is ready"
  echo "================================================"
  echo " URL:      $url"
  echo " Login:    admin / admin"
  echo ""
  echo " Useful commands:"
  echo "   ddev launch          Open site in browser"
  echo "   ddev drush cr        Rebuild cache after changes"
  echo "   ddev stop            Stop containers"
  echo "   ddev start           Restart containers"
  echo "   scripts/teardown.sh  Destroy everything"
  echo "================================================"
}

# ---------- main ----------

main() {
  check_prerequisites
  start_ddev
  create_drupal_project
  link_theme
  install_theme_deps
  stage_theme_node_modules
  install_drupal_site
  enable_theme
  place_blocks
  seed_content
  rebuild_cache
  print_summary
}

main "$@"
