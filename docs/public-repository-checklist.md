# Public repository checklist

Complete this checklist when Canopy moves into the dof-dss organization. These
controls are GitHub settings and cannot be enforced by package source alone.

## Ownership and changes

- Assign a responsible dof-dss team and add it to `.github/CODEOWNERS` before
  enabling public contributions. Do not substitute a personal account as the
  long-term owner.
- Protect the default branch with a ruleset requiring pull requests, at least
  one approval, successful CI, resolved review conversations, and no force
  pushes or deletions.
- Restrict tag and release creation to maintainers and use semantic versioning.
- Retain the MIT licence and confirm the required Crown Copyright notice with
  the departmental owner before the first public release.

## GitHub security

- Enable private vulnerability reporting.
- Enable secret scanning, push protection, Dependabot alerts, and Dependabot
  security updates.
- Enable code scanning if available and review Actions permissions at the
  organization level.
- Require two-factor authentication for organization members and use teams,
  not ad-hoc collaborators, for access.
- Run an organization-approved full-history secret scan before changing
  visibility. Treat every discovered credential as compromised and rotate it
  before publication.

## Actions and publication

- Keep the workflow token read-only unless a reviewed release job requires a
  narrower additional permission.
- Require approval for workflows from first-time or external contributors.
- Do not expose organization secrets to pull requests from forks.
- Do not publish Canopy audit results as Actions artifacts, job summaries,
  Pages content, issue attachments, or release assets without a separate data
  review.
- Review the complete Git history, issues, pull requests, Actions logs, release
  assets, and repository variables before changing visibility.

## Operational separation

- Keep real inventories, project exceptions, internal integrations, and
  generated reports in consuming repositories with appropriate visibility.
- Prefer a consumer-owned `.canopy/inventory.yml`; use neutral target IDs if
  results may cross the delivery team's trust boundary.
- Use only synthetic data in fixtures and inventories; do not place private
  target data in documentation.
- Re-review this checklist whenever a check gains network, database, container,
  Drupal runtime, hosting, or external-service access.
