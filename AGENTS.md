# Contributor guidance

## Purpose

Canopy is a read-only technical assurance toolkit. Preserve the boundary
between observing a target and changing it.

## Safety rules

- Checks must not deploy, reindex, clear caches, import configuration, run
  database updates, alter services, or modify audited projects.
- Runtime and network access must be explicit. When permission or a dependency
  is unavailable, return `unknown` or `skipped`; never infer success.
- Do not emit secrets, field values, response bodies, database contents, or
  other unnecessary sensitive data. Prefer bounded metadata and measurements.
- Keep remediation as explanatory output. Do not execute it from an audit.
- Preserve unrelated working-tree changes in this and all target projects.
- Do not commit, push, publish packages, or change external systems unless the
  user explicitly requests it.

## Architecture rules

- Deterministic checks are authoritative; prompts may explain evidence but
  must not determine check status.
- Each check has a stable ID and returns a structured result using one of
  `pass`, `warn`, `fail`, `unknown`, or `skipped`.
- Separate static repository evidence, Drupal runtime evidence, service
  readiness, and operational capacity. Do not collapse them into one claim.
- Treat capability profiles as expectations, not prescriptions for identical
  Drupal configuration.
- Project-specific exceptions belong in the consuming project and must include
  a reason. Prefer review dates for time-bound exceptions.
- Keep DDEV commands as thin wrappers around the Composer-installed CLI.
- Add external integrations behind interfaces so checks remain independently
  testable.
- Changes to required result fields or their meaning require a schema-version
  review.

## Implementation conventions

- Use PHP 8.2-compatible code and declare strict types.
- Keep classes focused and immutable where practical.
- Add unit tests for status classification, parsers, redaction, and check
  results. Use fixtures for external command output.
- Avoid shell pipelines when structured APIs or Symfony Process arguments are
  available.
- Document each check's evidence, limitations, permissions, and failure modes.
- Run `composer verify` and `git diff --check` before handing off changes.
