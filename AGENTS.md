# AGENTS.md

Project commands and specifics for IsItDark, a small PHP library that tells whether it is
dark at a given location and time (sun position, twilight phases). The development process
(issue, worktree, review, PR, merge) is in [docs/workflow.md](docs/workflow.md), the release
process in [docs/release-workflow.md](docs/release-workflow.md). The default branch is `main`.

Everything is written in English (code, comments, docs, commits, issues).

## Layout

| Path | Content |
|---|---|
| `src/` | Library, namespace `CrazyGoat\IsItDark\` (`IsItDark`, `Location`, `Calculator/`, `Enum/`, `Exception/`) |
| `tests/` | PHPUnit tests, they need no network or database |
| `examples/` | Runnable usage examples (`php examples/01-basic.php`) |
| `docs/plans/` | Original design and implementation plans |
| `bin/` | Lint script, worktree scripts and `pick-issue.sh` |

## Commands

PHP 8.1+ and Composer.

```bash
composer install

composer lint            # runs bin/lint.sh (check only)
composer lint-fix        # runs bin/lint.sh --fix, then checks again
composer test            # PHPUnit
```

`bin/lint.sh` runs `composer validate`, `composer audit`, php-cs-fixer (dry run), Rector (dry run),
PHPStan (level max, no baseline) and `shellcheck` on all shell scripts. It runs every step and fails
if any step failed. `shellcheck` must be installed. `bin/pick-issue.sh`, `bin/worktree.sh` and
`bin/worktree-done.sh` are byte-identical copies of the shared scripts in `crazy-goat/.github`:
do not edit them.

Run `composer lint-fix` and then `composer lint` before committing. Push only when
`composer lint` and `composer test` pass.

## Docker

The library needs no containers and has no compose file. If you add one, use
`"${NAME_PORT:-N}:N"` for published ports and no `container_name`, so that worktrees can run
side by side.

## CI

`.github/workflows/tests.yaml` runs `changes` and `docs` always. `lint` and `tests`
(PHP 8.1 to 8.5) run for code changes. `ci-ok` aggregates them and is the only required check.
The CI `lint` job only runs `bin/lint.sh`. Tagging `vX.Y.Z` runs `.github/workflows/release.yaml`.

## Notes

- Keep the public API stable: Rector and PHP-CS-Fixer run on `src/`, so check the diff of
  `composer lint-fix` before committing it.
- `composer.json` pins `config.platform.php` to 8.1.0, so `composer.lock` always resolves
  packages that install on the lowest supported PHP. Do not use syntax newer than PHP 8.1
  (for example `readonly class` needs 8.2).
- `var/` holds the php-cs-fixer cache and is gitignored.
