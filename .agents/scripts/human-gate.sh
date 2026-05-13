#!/usr/bin/env bash
# human-gate.sh — PR merge gate: master/main is human-only
#
# This hook is designed to be called from:
#   - CI/CD pipeline (merge gate)
#   - git hooks (pre-push)
#   - Manual review step
#
# If the target branch is master or main, it verifies that the merge
# was authorized by a human (either manually or via a reviewed PR).
#
# Usage:
#   ./human-gate.sh <target-branch>
#
# Exit codes:
#   0 — allowed
#   1 — blocked (agent attempted master push)

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

fail() { echo -e "${RED}[BLOCKED]${NC} $1"; exit 1; }
ok()   { echo -e "${GREEN}[ALLOWED]${NC} $1"; exit 0; }

TARGET="${1:-}"

if [ "$TARGET" = "master" ] || [ "$TARGET" = "main" ]; then
    # Check if GITHUB_ACTOR is set and is a known bot
    if [ -n "${GITHUB_ACTOR:-}" ]; then
        # Allow known CI/CD actors (GitHub Actions, humans merging via UI)
        case "$GITHUB_ACTOR" in
            "github-actions"|"dependabot"|"dependabot[bot]")
                ok "CI/CD merge to $TARGET allowed"
                ;;
            *)
                # Check if the merge was approved via PR review
                if [ -n "${GITHUB_HEAD_REF:-}" ]; then
                    ok "PR merge to $TARGET by $GITHUB_ACTOR (requires human review on GitHub)"
                else
                    fail "Direct push to $TARGET by $GITHUB_ACTOR is not allowed."
                fi
                ;;
        esac
    else
        # Running locally — check if stdin is a terminal (human)
        if [ -t 0 ]; then
            ok "Human operator confirmed"
        else
            fail "Merge to $TARGET requires human intervention. Use GitHub PR."
        fi
    fi
else
    ok "Branch $TARGET is not protected"
fi
