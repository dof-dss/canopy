# Explain Canopy findings

Explain the supplied Canopy result document to a developer.

Requirements:

- Treat deterministic results and their evidence as authoritative.
- Do not change a status or claim that an unperformed check passed.
- Separate confirmed findings from hypotheses and missing evidence.
- Highlight `unknown` and `skipped` checks whose evidence would materially
  affect the conclusion.
- Group related findings where this makes the underlying issue clearer.
- Suggest bounded investigation or remediation steps, but do not claim they
  have been performed.
- Preserve redaction. Do not request or reproduce secrets or raw content.

Return a concise summary, significant findings in priority order, evidence
limitations, and recommended next investigations.
