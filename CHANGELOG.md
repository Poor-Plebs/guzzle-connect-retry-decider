# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog][1], and this project adheres to
[Semantic Versioning][2].

*Types of changes:*

- `Added`: for new features.
- `Changed`: for changes in existing functionality.
- `Deprecated`: for soon-to-be removed features.
- `Removed`: for now removed features.
- `Fixed`: for any bug fixes.
- `Security`: in case of vulnerabilities.

## [Unreleased]

## [1.2.0] - 2026-10-28

### Added

- Added support for `guzzlehttp/guzzle` 8. Guzzle 8 only throws a connect
  exception while the connection is being established, so those are retried
  for every HTTP method. GET requests are also retried on any other network
  exception, which Guzzle 7 reported as connect exceptions.

## [1.1.1] - 2022-12-21

### Fixed

- Fixed failing test because of user agent inconsistencies.

## [1.1.0] - 2022-12-21

### Added

- Added support for an on before retry callback, which is executed when it is
  decided to retry.

## [1.0.0] - 2022-12-18

### Added

- Initial version of the `poor-plebs/guzzle-connect-retry-decider`.

[1]: https://keepachangelog.com/en/1.1.0/
[2]: https://semver.org/spec/v2.0.0.html

[Unreleased]: https://github.com/Poor-Plebs/guzzle-connect-retry-decider/compare/1.2.0...HEAD
[1.2.0]: https://github.com/Poor-Plebs/guzzle-connect-retry-decider/releases/1.2.0
[1.1.1]: https://github.com/Poor-Plebs/guzzle-connect-retry-decider/releases/1.1.1
[1.1.0]: https://github.com/Poor-Plebs/guzzle-connect-retry-decider/releases/1.1.0
[1.0.0]: https://github.com/Poor-Plebs/guzzle-connect-retry-decider/releases/1.0.0
