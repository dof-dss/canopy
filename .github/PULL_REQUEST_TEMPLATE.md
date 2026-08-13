## Summary

Describe the assurance outcome and why the change is needed.

## Evidence and limitations

Describe the evidence used, permissions required, and what the change cannot
establish.

## Safety checklist

- [ ] The audit remains read-only and remediation is explanatory only.
- [ ] Fixtures and examples are synthetic and contain no sensitive target data.
- [ ] Result output is bounded and redacted.
- [ ] Stable check IDs and status meanings are preserved, or schema impact is documented.
- [ ] Unit tests cover classification, parsing, redaction, and failure modes as applicable.
- [ ] `composer verify` passes.
- [ ] `composer audit --locked` reports no unreviewed advisory.
- [ ] `git diff --check` passes.
