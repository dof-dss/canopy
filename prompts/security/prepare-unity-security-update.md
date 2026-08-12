# Prepare a Unity-style Drupal security update

Guide a developer interactively through preparing a Drupal security update in
a Unity-style Composer/DDEV repository. The developer remains the decision
maker and publisher at every stage.

## Absolute boundaries

- Begin read-only. Do not change any file until you have presented the evidence,
  proposed the exact local changes, and received explicit human confirmation
  for that specific phase.
- A confirmation to inspect is not permission to edit. A confirmation to edit
  is not permission to run DDEV, import configuration, update a database, or
  perform any later phase.
- Preserve all pre-existing working-tree changes. If an intended edit overlaps
  them, stop and ask the developer how to proceed.
- Never stage or commit changes. Never push, force-push, publish a branch or
  tag, create or update a pull request, or merge code. Under no circumstances
  may an AI push code to any repository.
- You may prepare explicitly approved changes in the local working tree and
  review their diff. The developer must review, stage, commit, push, and create
  any PR themselves.
- You may run read-only Git commands. Any state-changing Git command must only
  be suggested for the developer; do not execute it.
- Never deploy, reindex, import configuration, run database updates, or alter a
  hosted environment.

## Unity assumptions to verify, not presume

- Composer files are at the repository root.
- Drupal is Composer-managed, commonly through `drupal/core-recommended`.
- Multiple demo/site exports may exist under `project/config/<site>/config`.
- DDEV may provide the supported PHP/Composer runtime.
- Project scripts, patches, generated files, config splits, and CI rules may
  impose additional update requirements.

## Interactive checklist

Work through one phase at a time. Show the checklist after every phase using
`[x]`, `[ ]`, or `[blocked]`, including evidence and the next decision needed.

### Phase 1 — read-only scope and applicability

- [ ] Confirm repository root and named sites/demo sites in scope.
- [ ] Record branch, upstream, and pre-existing working-tree changes.
- [ ] Inspect `composer.json`, `composer.lock`, patches, repositories, scripts,
      platform constraints, DDEV configuration, and project guidance.
- [ ] Obtain current official Drupal advisory evidence and authoritative URLs.
- [ ] Match affected ranges to locked packages and classify applicability.
- [ ] Identify dependency blockers with read-only Composer analysis.
- [ ] Present exact proposed package targets, commands, expected file changes,
      risks, and validation plan.

Stop and ask: **May I prepare only these listed changes in the local working
tree?** Do not proceed without an explicit answer.

### Phase 2 — locally prepare the confirmed dependency change

Only after confirmation:

- [ ] Recheck working-tree state immediately before mutation.
- [ ] Use the narrowest Composer operation that targets the confirmed affected
      packages and their necessary dependencies.
- [ ] Do not opportunistically update unrelated packages.
- [ ] Record the command, exit status, and changed files.
- [ ] Inspect Composer solver output and the `composer.json`/`composer.lock`
      diff for unexpected additions, removals, source changes, or constraint
      widening.
- [ ] Stop if the result differs materially from the approved proposal.

Show the local diff summary and ask separately before running any validation
that starts DDEV or modifies local runtime state.

### Phase 3 — validate with separate confirmation

Propose the smallest relevant sequence, adapting it to repository guidance:

- [ ] Composer validation and lock consistency.
- [ ] Project lint, static analysis, and unit/kernel/functional tests.
- [ ] Patch application and scaffold-file review.
- [ ] Drupal database-update requirements assessed without running them first.
- [ ] Exported configuration reviewed per site/demo site; do not assume one
      demo site's result represents the others.
- [ ] DDEV/Drush checks run only when specifically authorised.
- [ ] Any failed, skipped, or unavailable check recorded independently.
- [ ] Final `git diff --check`, `git status`, and focused diff review.

Do not import config or run database updates merely because an update indicates
they may be required. Present those as developer-run or separately confirmed
local-runtime steps.

### Phase 4 — human handoff

Return:

- applicable advisories and official sources;
- before/after package versions;
- exact local files changed;
- validations run and their outcomes;
- unperformed or blocked checks;
- site/demo-site-specific concerns;
- suggested PR title and body, clearly labelled as a draft; and
- **Developer-reviewed commands (not run)** for any desired Git staging,
  commit, push, or PR workflow.

End with: **No files were staged or committed, and no code was pushed or
published by the AI.** If that statement would be false, stop and disclose the
boundary violation instead of continuing.
