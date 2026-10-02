# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

## [0.1.0] - 2026-10-02

First release: a PHP 8.1+ library that tells whether it is dark at a given location and time, with sunrise, sunset, twilight phases, day length and polar day/night handling (NOAA and Meeus calculators).

### Added
- [#2] `bin/lint.sh` (composer validate and audit, php-cs-fixer, Rector, PHPStan at level max,
  shellcheck; `--fix` applies fixes) with `friendsofphp/php-cs-fixer`, `rector/rector` and
  `phpstan/phpstan` in `require-dev`. `composer lint`, `composer lint-fix` and `composer test`
  call the tools.
- [#2] First CI workflow `tests.yaml`: PHPUnit on PHP 8.1 to 8.5, a `lint` job, documentation
  checks, and the `ci-ok` aggregate check. Documentation-only pull requests run only the fast checks.
- [#2] `release.yaml` workflow: pushing a `v*` tag creates the GitHub Release with the notes
  taken from the matching `CHANGELOG.md` section.
- [#2] `LICENSE` (MIT).
- [#2] Development process documentation: `docs/workflow.md`, `docs/release-workflow.md`,
  `AGENTS.md`, worktree scripts and `pick-issue.sh` under `bin/`, a pull request template and
  Dependabot configuration for Composer and GitHub Actions.

### Changed
- [#2] Code style normalised with php-cs-fixer (PER-CS 2.0) and Rector (`instanceof` checks instead of
  `=== null` on objects, `readonly` on a private property); no behaviour change.
- [#2] `composer.json` pins `config.platform.php` to 8.1.0 so the lock file resolves for the lowest supported PHP.

### Fixed
- [#2] `Location` was declared `readonly class`, which is a parse error on PHP 8.1 although
  `composer.json` requires `>=8.1`. It now uses `readonly` properties; the public API is unchanged.
