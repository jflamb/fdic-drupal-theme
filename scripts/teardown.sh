#!/usr/bin/env bash
# Destroys the DDEV project and removes the generated Drupal directory.
#
# This is the inverse of bootstrap.sh. After running, the repo is back to a
# clean state (only committed theme files remain).
#
# Usage: scripts/teardown.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
DRUPAL_DIR="$PROJECT_ROOT/drupal"

info() { printf '\033[1;34m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m OK\033[0m %s\n' "$*"; }

cd "$PROJECT_ROOT"

# Stop and remove DDEV project (containers, volumes, network).
if ddev describe &>/dev/null; then
  info "Removing DDEV project"
  ddev delete -Oy
  ok "DDEV project removed"
else
  info "No active DDEV project found"
fi

# Remove the generated Drupal directory.
if [[ -d "$DRUPAL_DIR" ]]; then
  info "Removing $DRUPAL_DIR"
  rm -rf "$DRUPAL_DIR"
  ok "Drupal directory removed"
fi

echo ""
echo "Teardown complete. Run scripts/bootstrap.sh to start fresh."
