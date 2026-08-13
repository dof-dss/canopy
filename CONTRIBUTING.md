# Contributing

Contributions are welcome when they preserve Canopy's read-only assurance
boundary.

## Before opening a pull request

- Use synthetic fixtures. Do not copy production configuration, reports,
  credentials, response bodies, database contents, personal data, internal
  hostnames, or private repository URLs into the project.
- Keep deterministic checks authoritative and return a stable check ID with one
  of `pass`, `warn`, `fail`, `unknown`, or `skipped`.
- Treat unavailable permissions or dependencies as `unknown` or `skipped`.
- Keep remediation explanatory; an audit must not perform it.
- Add unit tests for classification, parsing, redaction, and structured results.
- Document evidence, limitations, permissions, and failure modes.
- Run `composer verify`, `composer audit --locked`, and `git diff --check`.

Report security concerns privately as described in [SECURITY.md](SECURITY.md),
not in a public issue or pull request.
