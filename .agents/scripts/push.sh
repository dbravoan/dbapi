#!/usr/bin/env bash
# push.sh — Agent-driven push harness
#
# Usage:
#   ./push.sh [--force]
#
# Rules:
#   1. Pushes current branch to origin
#   2. Refuses to push to master/main unless --force is passed (human gate)
#   3. Shows the URL to open a PR after push
#
# Exit codes:
#   0 — pushed successfully
#   1 — push refused (protected branch)
#   2 — no remote configured

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
NC='\033[0m'

ok()   { echo -e "${GREEN}[OK]${NC}   $1"; }
fail() { echo -e "${RED}[FAIL]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }

FORCE=0
if [ $# -ge 1 ] && [ "$1" = "--force" ]; then
    FORCE=1
fi

# ── Guard: remote exists ─────────────────────────────────────────────────────
REMOTE=$(git remote get-url origin 2>/dev/null || echo "")
if [ -z "$REMOTE" ]; then
    fail "No remote 'origin' configured."
    exit 2
fi

# ── Guard: protected branch gate ─────────────────────────────────────────────
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [ "$CURRENT_BRANCH" = "main" ] || [ "$CURRENT_BRANCH" = "master" ]; then
    if [ "$FORCE" -eq 0 ]; then
        fail "Pushing to $CURRENT_BRANCH is FORBIDDEN for agents."
        echo "  If you are HUMAN and want to push anyway, run:"
        echo "    ./push.sh --force"
        echo "  Or push manually: git push origin $CURRENT_BRANCH"
        exit 1
    else
        warn "⚠  HUMAN OVERRIDE: Pushing to $CURRENT_BRANCH"
    fi
fi

# ── Push ─────────────────────────────────────────────────────────────────────
git push origin "$CURRENT_BRANCH"
ok "Pushed $CURRENT_BRANCH → origin"

# ── PR hint ──────────────────────────────────────────────────────────────────
if command -v gh &>/dev/null; then
    echo ""
    echo "  To create a PR: gh pr create --fill"
    echo "  PR URL will appear above. Only HUMANS may merge to master/main."
else
    echo ""
    echo "  Open a PR on GitHub:"
    echo "    https://github.com/$(echo "$REMOTE" | sed -E 's|.*[:/]([^/]+/[^/.]+)(\.git)?$|\1|')/pull/new/$CURRENT_BRANCH"
fi
