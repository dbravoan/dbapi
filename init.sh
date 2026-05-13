#!/usr/bin/env bash
# init.sh — Harness verification + bootstrap for dbapi
#
# Run this at the START of every agent session, and again BEFORE flipping a
# feature to `done`. If it doesn't exit 0, do not advance.
#
# Output format: bracketed status tags so it can be parsed.
#   [OK]    everything is fine
#   [WARN]  optional / informational
#   [FAIL]  blocking; exit code will be non-zero
#
# Designed to work in two modes:
#   1. Sail mode  — if `./vendor/bin/sail` exists and `sail ps` reports running
#                   containers, we run PHP commands through Sail.
#   2. Local mode — otherwise, we fall back to the host's `php` binary.
#
# The harness invariants (files exist, ≤1 in_progress feature, no telephone
# game in progress/) are checked in both modes.

set -u

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m'

ok()    { printf "${GREEN}[OK]${NC}    %s\n" "$1"; }
warn()  { printf "${YELLOW}[WARN]${NC}  %s\n" "$1"; }
fail()  { printf "${RED}[FAIL]${NC}  %s\n" "$1"; }
section() { printf "\n${BLUE}── %s ──${NC}\n" "$1"; }

EXIT_CODE=0

# Helper: run a PHP snippet via the chosen runner.
# Sail requires `sail php -r '...'` (bare `sail -r` does not work).
php_run() {
  if [ "$PHP_RUNNER" = "./vendor/bin/sail" ]; then
    "$PHP_RUNNER" php -r "$1"
  else
    "$PHP_RUNNER" -r "$1"
  fi
}

php_artisan() {
  if [ "$PHP_RUNNER" = "./vendor/bin/sail" ]; then
    "$PHP_RUNNER" artisan "$@"
  else
    "$PHP_RUNNER" artisan "$@"
  fi
}

# ---------------------------------------------------------------------------
section "1. Detecting PHP runtime"
# ---------------------------------------------------------------------------

PHP_RUNNER=""
if [ -x "./vendor/bin/sail" ]; then
  if ./vendor/bin/sail ps --status running 2>/dev/null | grep -q "laravel.test"; then
    PHP_RUNNER="./vendor/bin/sail"
    ok "Sail is running — using sail for PHP commands"
  else
    warn "Sail exists but containers are not up. Falling back to host PHP."
  fi
fi

if [ -z "$PHP_RUNNER" ]; then
  if command -v php >/dev/null 2>&1; then
    PHP_RUNNER="php"
    ok "Using host php -> $(php -r 'echo PHP_VERSION;')"
  else
    fail "Neither Sail nor host php available. Install one before continuing."
    exit 1
  fi
fi

# ---------------------------------------------------------------------------
section "2. Harness base files"
# ---------------------------------------------------------------------------

REQUIRED_FILES=(
  "AGENTS.md"
  "OPENCODE.md"
  "CHECKPOINTS.md"
  "feature_list.json"
  "progress/current.md"
  "progress/history.md"
  "docs/architecture.md"
  "docs/conventions.md"
  "docs/verification.md"
  ".agents/agents/leader.md"
  ".agents/agents/implementer.md"
  ".agents/agents/reviewer.md"
)

for f in "${REQUIRED_FILES[@]}"; do
  if [ ! -f "$f" ]; then
    fail "Missing harness file: $f"
    EXIT_CODE=1
  else
    ok "Exists: $f"
  fi
done

# ---------------------------------------------------------------------------
section "3. feature_list.json invariants"
# ---------------------------------------------------------------------------

if [ -f "feature_list.json" ]; then
  # Use the PHP runtime we already have — no python dependency.
  php_run '
    $raw = @file_get_contents("feature_list.json");
    if ($raw === false) { fwrite(STDERR, "[FAIL]  cannot read feature_list.json\n"); exit(1); }
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data["features"]) || !is_array($data["features"])) {
        fwrite(STDERR, "[FAIL]  feature_list.json malformed (missing features array)\n");
        exit(1);
    }
    $valid = ["pending", "in_progress", "done", "blocked"];
    $inprog = 0;
    foreach ($data["features"] as $f) {
        if (!in_array($f["status"] ?? "", $valid, true)) {
            fwrite(STDERR, "[FAIL]  invalid status on feature " . ($f["id"] ?? "?") . ": " . ($f["status"] ?? "?") . "\n");
            exit(1);
        }
        if (($f["status"] ?? "") === "in_progress") { $inprog++; }
    }
    if ($inprog > 1) {
        fwrite(STDERR, "[FAIL]  $inprog features are in_progress (max 1 allowed)\n");
        exit(1);
    }
    $total = count($data["features"]);
    echo "[OK]    feature_list.json valid (total={$total}, in_progress={$inprog})\n";
  ' || EXIT_CODE=1
fi

# ---------------------------------------------------------------------------
section "4. progress/ hygiene"
# ---------------------------------------------------------------------------

# Detect orphan impl_/review_ reports (no matching feature name in feature_list.json)
if [ -f "feature_list.json" ]; then
  php_run '
    $data = json_decode(file_get_contents("feature_list.json"), true);
    $names = [];
    foreach ($data["features"] ?? [] as $f) { $names[$f["name"] ?? ""] = true; }
    $orphans = [];
    foreach (glob("progress/impl_*.md") ?: [] as $p) {
        $n = preg_replace("/^progress\\/impl_(.+)\\.md$/", "$1", $p);
        if (!isset($names[$n])) { $orphans[] = $p; }
    }
    foreach (glob("progress/review_*.md") ?: [] as $p) {
        $n = preg_replace("/^progress\\/review_(.+)\\.md$/", "$1", $p);
        if (!isset($names[$n])) { $orphans[] = $p; }
    }
    if (!empty($orphans)) {
        foreach ($orphans as $o) { fwrite(STDERR, "[WARN]  orphan progress report: $o\n"); }
        exit(0); // warnings only, do not fail
    }
    echo "[OK]    progress/ has no orphan reports\n";
  '
fi

# ---------------------------------------------------------------------------
section "5. Composer autoload"
# ---------------------------------------------------------------------------

if [ ! -f "vendor/autoload.php" ]; then
  fail "vendor/autoload.php missing. Run 'composer install --ignore-platform-reqs'."
  EXIT_CODE=1
else
  ok "Composer autoload present"
fi

# ---------------------------------------------------------------------------
section "6. PHPUnit test suite"
# ---------------------------------------------------------------------------

if [ -f "phpunit.xml" ] || [ -f "phpunit.xml.dist" ]; then
  # First, sanity-check that the runtime can even bootstrap composer.
  if ! php_run 'require "vendor/autoload.php"; echo "ok";' >/dev/null 2>&1; then
    if [ "$PHP_RUNNER" = "php" ]; then
      warn "Host PHP cannot bootstrap composer (likely a PHP version mismatch with composer.json)."
      warn "Start Sail and re-run: ./vendor/bin/sail up -d  &&  ./init.sh"
      warn "Skipping test run — DO NOT mark any feature as done from this shell."
      EXIT_CODE=1
    else
      fail "Sail PHP cannot bootstrap composer. Check 'sail logs'."
      EXIT_CODE=1
    fi
  else
    TEST_OUTPUT=$(php_artisan test --without-tty 2>&1)
    TEST_STATUS=$?
    echo "$TEST_OUTPUT" | tail -n 40
    if [ $TEST_STATUS -eq 0 ]; then
      ok "All tests pass"
    else
      fail "Some tests failed"
      EXIT_CODE=1
    fi
  fi
else
  warn "No phpunit.xml — skipping test run"
fi

# ---------------------------------------------------------------------------
section "7. Summary"
# ---------------------------------------------------------------------------

if [ $EXIT_CODE -eq 0 ]; then
  ok "Environment is ready. You may pick a pending feature and start work."
else
  fail "Environment is NOT ready. Resolve the failures above before advancing."
fi

exit $EXIT_CODE
