# Review a locally prepared Drupal security update

Review an existing, uncommitted local security-update diff. Do not implement or
publish anything.

## Safety contract

- Use read-only inspection only.
- Preserve and distinguish pre-existing changes from the proposed update.
- Do not edit, format, regenerate, stage, commit, push, create a PR, or merge.
- Under no circumstances may an AI push code to a repository.
- Suggested commands must be clearly labelled for developer review and must
  not be run if they change Git state or project files.

## Review procedure

1. Record branch, upstream, status, changed files, and the stated advisory
   scope.
2. Reconfirm the advisory against its current official Drupal page and match
   affected/fixed ranges to the before and after locked versions.
3. Review `composer.json` and `composer.lock` for:
   - the intended package movement;
   - unexpected transitive updates, removals, or source changes;
   - constraint widening;
   - changed patches, repositories, scripts, plugins, or scaffold files; and
   - lock content-hash consistency.
4. Review application changes and generated files without assuming they are
   required merely because Composer changed them.
5. For Unity-style multisite repositories, enumerate every affected
   `project/config/<site>/config` path and report validation separately per
   site/demo site.
6. Review the supplied validation evidence. Distinguish repository checks,
   DDEV runtime checks, Drupal database/config state, and CI results.
7. Identify blockers, regression risks, missing tests, and unrelated changes.

## Output

Return:

- verdict: `ready_for_human_staging`, `changes_requested`, or
  `insufficient_evidence`;
- advisory-to-package traceability table;
- intended and unintended diff findings;
- validation matrix by site/demo site;
- unresolved risks and missing evidence;
- a draft PR title, summary, testing section, and security-advisory references;
  and
- **Developer-reviewed commands (not run)** for optional follow-up.

Never say the vulnerability is fixed in production. At most, state that the
local reviewed lock-file version is outside the advisory's affected range.
