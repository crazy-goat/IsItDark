#!/usr/bin/env bash
# Run all static analysis, linters and formatter checks. --fix applies fixes first.
set -uo pipefail
cd "$(dirname "$0")/.." || exit 1

FIX=0
[ "${1:-}" = "--fix" ] && FIX=1
failed=()

step() {
    local name="$1"; shift
    echo "==> $name"
    "$@" || failed+=("$name")
}

if [ "$FIX" = 1 ]; then
    vendor/bin/rector process
    vendor/bin/php-cs-fixer fix
fi

step "composer validate" composer validate --strict
step "composer audit" composer audit
step "php-cs-fixer" vendor/bin/php-cs-fixer fix --dry-run --diff
step "rector" vendor/bin/rector process --dry-run
step "phpstan" vendor/bin/phpstan analyse --no-progress
step "shellcheck" bash -c 'git ls-files -z --cached --others --exclude-standard "*.sh" | xargs -0 -r shellcheck'
if [ -n "$(git ls-files --cached --others --exclude-standard | grep -E '(^|/)Dockerfile' || true)" ]; then
    step "hadolint" bash -c 'git ls-files -z --cached --others --exclude-standard | grep -zE "(^|/)Dockerfile" | xargs -0 -r hadolint'
fi

if [ "${#failed[@]}" -gt 0 ]; then
    echo "Failed: ${failed[*]}" >&2
    exit 1
fi
echo "All checks passed."
