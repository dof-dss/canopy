# Output security

Canopy source code and generated audit evidence have different publication
boundaries. A public Canopy repository does not make a report safe to publish.

## Public-safe defaults

JSON result documents:

- omit local repository paths from project metadata;
- replace the audited project root and the caller's home path in emitted text;
- redact values under keys that commonly identify credentials;
- redact common bearer tokens, credential URLs, and secret assignments;
- remove terminal control characters and bound individual free-text values; and
- record the applied redaction policy in the document.

Redaction is defence in depth, not a data-classification decision. Project and
site IDs, software versions, module names, workflow and role identifiers,
configured capabilities, and missing controls can still be sensitive.

## Handling rules

- Use neutral project IDs if results may be shared beyond the delivery team.
- Write local reports under `.audit/`; the package ignores that directory.
- Do not upload reports as public CI artifacts or paste them into public issues.
- Keep real inventories and project-specific exceptions in the consuming
  project with visibility appropriate to the target.
- Do not add response bodies, field values, database contents, credentials, or
  environment dumps to check evidence.
- Review a report before moving it across a trust boundary, even if redaction
  metadata is present.

## Trusted project verifier

`--run-project-verifier` executes code owned by the audited project. Canopy
strips inherited environment variables, provides only a minimal process
environment, refuses a symlinked entrypoint, and redacts parsed `NOTICE:` and
`ERROR:` lines. It does not provide filesystem isolation. Only run a verifier
that has been reviewed and is trusted; use an external read-only sandbox for
untrusted repository code.
