# Drupal security update prompts

These draft prompts help a developer assess and prepare Drupal security
updates while retaining human control. They are advisory workflows, not
deterministic Canopy checks, and must not be treated as proof that a site is
secure or updated.

They also provide a tool-neutral foundation for agentic services to support the
process. Canopy does not prescribe a particular AI agent, model, IDE, or
automation service: developers can choose tools that fit their context, role,
and authority. The selected tool must first discover and follow all applicable
`AGENTS.md` directives, as well as the prompt's constraints and the developer's
current instructions.

The prompt library enforces one permanent boundary: an AI may inspect and,
after explicit confirmation, prepare local uncommitted changes, but it must
never stage, commit, push, publish a branch/tag, open a PR, or merge code. The
developer owns all repository publication.

## Choose a prompt

- [`assess-drupal-security-releases.md`](../prompts/security/assess-drupal-security-releases.md)
  performs read-only advisory/package applicability research.
- [`prepare-unity-security-update.md`](../prompts/security/prepare-unity-security-update.md)
  provides the interactive, confirmation-gated local preparation checklist.
- [`review-local-security-update.md`](../prompts/security/review-local-security-update.md)
  reviews an already-prepared uncommitted diff and drafts a human handoff.

## Example: read-only applicability review

Start an agent in the Unity checkout and provide the assessment prompt followed
by scoped context:

```text
Use Canopy's prompts/security/assess-drupal-security-releases.md.

Project root: /home/developer/projects/unity
Scope: all demo sites in project/config/*/config
Advisory window: Drupal advisories published in the last 14 days
Network permission: official drupal.org security advisory pages only

Remain read-only. Show the package/advisory matching evidence and commands I
could review, but do not make changes.
```

A useful response identifies the exact lock-file package/version, links the
official advisory, classifies applicability, and reports ambiguity instead of
guessing. A list of available updates without affected-version matching is not
sufficient.

## Example: interactive local preparation

```text
Use Canopy's prompts/security/prepare-unity-security-update.md.

Repository: /home/developer/projects/unity4
Advisory: SA-CORE-YYYY-NNN
Sites: anotherway, coimisineir, cusubt, and oice

Complete Phase 1 only. Do not edit anything until you show me the exact
proposed package operation, affected files, and validation plan, then ask for
confirmation.
```

After reviewing Phase 1, the developer can respond narrowly:

```text
Confirmed: prepare only the proposed Drupal core dependency update locally.
Do not change contributed modules, run DDEV, stage, commit, push, or create a
PR. Stop after showing the Composer diff and solver outcome.
```

That approval applies only to the stated local Composer change. It does not
authorise validation phases or any Git publication operation.

## Example: review a prepared diff

```text
Use Canopy's prompts/security/review-local-security-update.md.

Review the current uncommitted diff against SA-CONTRIB-YYYY-NNN. Check all
Unity demo sites separately and return a draft PR description. Do not edit,
stage, commit, push, or open the PR.
```

## Suggested developer-run discovery commands

Review and adapt these commands before running them. They are examples rather
than an instruction for an agent to execute:

```shell
git status --short --branch
composer validate --no-check-publish
composer show --locked 'drupal/*'
composer audit --locked
composer why drupal/core-recommended
composer why-not drupal/core-recommended '<fixed-version>'
```

Composer advisory data can be useful supporting evidence, but current official
Drupal Security Advisory pages remain the source for Drupal affected ranges,
risk details, and fixed releases.

## Evidence boundaries

- `composer.json` expresses permitted constraints; it is not the installed or
  locked version.
- `composer.lock` is the primary repository evidence for the selected package
  version.
- `vendor` may be stale or absent and should be reported separately.
- A successful local update does not establish database update completion,
  configuration compatibility, CI success, deployment, or production safety.
- One Unity demo site's exported configuration or runtime result does not prove
  another site's result.
- AI-authored interpretations and PR drafts require human review.
