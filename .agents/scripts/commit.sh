#!/usr/bin/env bash
# commit.sh — Agent-driven commit harness
#
# Usage:
#   ./commit.sh "<commit-message>"
#
# Rules:
#   1. Stages ALL changes (git add -A)
#   2. Commits with the provided message (imperative present tense)
#   3. Runs pre-commit checks (init.sh + test suite) before committing
#   4. NEVER pushes to remote — that is a human-only action
#   5. NEVER commits to master/main directly
#
# Exit codes:
#   0 — commit created successfully
#   1 — pre-commit checks failed
#   2 — no changes to commit
#   3 — attempted to commit to protected branch

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
NC='\033[0m'

ok()   { echo -e "${GREEN}[OK]${NC}   $1"; }
fail() { echo -e "${RED}[FAIL]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }

# ── Guard: protected branches ────────────────────────────────────────────────
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [ "$CURRENT_BRANCH" = "main" ] || [ "$CURRENT_BRANCH" = "master" ]; then
    fail "Direct commits to $CURRENT_BRANCH are FORBIDDEN. Create a feature branch first."
    exit 3
fi

# ── Guard: commit message ────────────────────────────────────────────────────
if [ $# -lt 1 ] || [ -z "$1" ]; then
    fail "Usage: ./commit.sh \"<imperative commit message>\""
    exit 1
fi
COMMIT_MSG="$1"

# ── Guard: unstaged changes check ────────────────────────────────────────────
if [ -z "$(git status --porcelain)" ]; then
    fail "No changes to commit."
    exit 2
fi

# ── Pre-commit: run harness check ────────────────────────────────────────────
echo ""
warn "Running pre-commit checks..."

if [ -x "./init.sh" ]; then
    if ! ./init.sh; then
        fail "init.sh failed. Fix issues before committing."
        exit 1
    fi
    ok "init.sh passed"
fi

# ── Pre-commit: run tests ────────────────────────────────────────────────────
if [ -f "vendor/bin/phpunit" ]; then
    PHP_RUNNER="php"
    if [ -x "./vendor/bin/sail" ] && ./vendor/bin/sail ps --status running 2>/dev/null | grep -q "laravel.test"; then
        PHP_RUNNER="./vendor/bin/sail"
    fi

    if [ "$PHP_RUNNER" = "./vendor/bin/sail" ]; then
        TEST_CMD="$PHP_RUNNER artisan test --without-tty"
    else
        TEST_CMD="$PHP_RUNNER artisan test"
    fi

    if ! eval "$TEST_CMD"; then
        fail "Test suite failed. Fix before committing."
        exit 1
    fi
    ok "Tests passed"
fi

# ── Stage all changes ────────────────────────────────────────────────────────
git add -A
ok "Staged all changes"

# ── Commit ───────────────────────────────────────────────────────────────────
git commit -m "$COMMIT_MSG"
ok "Committed: $COMMIT_MSG"

echo ""
echo "────────────────────────────────────────────"
echo "  Commit created on branch: $CURRENT_BRANCH"
echo "  Next step: git push origin $CURRENT_BRANCH"
echo "  Then create a PR (human review required)."
echo "────────────────────────────────────────────"
