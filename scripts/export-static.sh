#!/usr/bin/env bash
# Exports a static rendered snapshot from an already bootstrapped DDEV site.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
OUT_DIR="${1:-$PROJECT_ROOT/public}"

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

snapshot_paths() {
  ddev drush php:eval '
    $storage = \Drupal::entityTypeManager()->getStorage("node");
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition("status", 1)
      ->condition("title", "FDIC Theme Example:%", "LIKE")
      ->sort("type")
      ->sort("title");
    $nids = $query->execute();
    if (empty($nids)) {
      fwrite(STDERR, "No FDIC Theme Example nodes found. Run scripts/bootstrap.sh before exporting.\n");
      exit(1);
    }
    $paths = ["/", "/node", "/node?page=1"];
    foreach ($storage->loadMultiple($nids) as $node) {
      $paths[] = $node->toUrl()->toString();
    }
    print json_encode(array_values(array_unique($paths)), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  '
}

main() {
  cd "$PROJECT_ROOT"

  info "Checking static export prerequisites"
  check_command ddev
  check_command node
  ok "Static export prerequisites met"

  info "Checking Drupal bootstrap"
  ddev drush status --field=bootstrap | grep -q "Successful" || die "Drupal bootstrap is not successful."
  ok "Drupal is bootstrapped"

  local url
  url="$(ddev_url)"
  url="${url%/}"

  local paths_json
  paths_json="$(snapshot_paths)"

  info "Exporting rendered snapshot to $OUT_DIR"
  NODE_TLS_REJECT_UNAUTHORIZED=0 node "$SCRIPT_DIR/static-export.mjs" \
    --base "$url" \
    --out "$OUT_DIR" \
    --paths-json "$paths_json"

  [[ -f "$OUT_DIR/index.html" ]] || die "$OUT_DIR/index.html was not created."
  [[ ! -e "$OUT_DIR/.npmrc" ]] || die "$OUT_DIR/.npmrc should not be exported."
  ok "Static snapshot exported"
}

main "$@"
