# Assess applicable Drupal security releases

Assess which published Drupal security advisories apply to this project. Work
in read-only mode and produce evidence for a developer to review.

## Non-negotiable safety contract

- Do not edit files, install or update packages, start services, import
  configuration, run database updates, clear caches, or change external state.
- Git inspection may use read-only commands such as `git status`, `git diff`,
  `git log`, and `git show`.
- Do not stage, commit, push, force-push, create a tag, open a pull request, or
  publish code. Under no circumstances may an AI push code to a repository.
- You may suggest diagnostic or remedial commands, but label them clearly as
  commands for the developer to review and run.
- Do not expose credentials, private repository URLs containing tokens, or
  unrelated package and environment data.

## Inputs

The developer will provide, or allow read-only discovery of:

- the project root;
- `composer.json` and `composer.lock`;
- the relevant date range or Drupal security advisory identifiers, if known;
- the sites or demo sites in scope; and
- whether current official advisory lookup and other network access are
  authorised.

If advisory lookup is not authorised or unavailable, stop short of an
applicability conclusion. Report which current evidence is missing.

## Evidence workflow

1. Record the current branch, working-tree state, PHP constraint, Composer
   platform constraints, Drupal core package strategy, and repository paths.
   Do not assume a dirty tree was caused by this task.
2. Derive installed versions primarily from `composer.lock`. Use
   `composer show --locked` only as supporting evidence. Distinguish locked,
   installed, and root constraint versions.
3. Inventory Drupal packages, especially:
   - `drupal/core-recommended`, `drupal/core`, `drupal/core-composer-scaffold`,
     and `drupal/core-project-message`;
   - contributed modules and themes; and
   - packages replaced, patched, forked, or sourced from custom repositories.
4. Consult current official Drupal Security Advisory pages for Drupal core,
   contributed modules, and themes. Record the advisory ID, publication date,
   affected version ranges, fixed releases, risk rating, and official URL.
   Composer advisory output may supplement this evidence but must not replace
   the official Drupal advisory.
5. Match each advisory against the package actually present in the lock file.
   Treat renamed projects, Composer replacement rules, development branches,
   patches, and backports as needing explicit review.
6. Classify each item as:
   - `applicable` — the locked package/version is explicitly affected;
   - `not_applicable` — reliable evidence shows it is outside the affected
     range or absent;
   - `possibly_applicable` — package mapping, patching, or range evidence is
     ambiguous; or
   - `unknown` — current official advisory or lock evidence is unavailable.
7. For applicable items, use read-only dependency analysis such as
   `composer why`, `composer why-not`, and `composer prohibits` to identify
   likely blockers. Do not run `composer update`, `composer require`, or any
   command that rewrites project files.

## Output

Return:

1. scope and evidence timestamp;
2. project and site/demo-site inventory;
3. a table of package, locked version, advisory, affected range, fixed release,
   classification, and source URL;
4. dependency blockers and special handling such as patches or forks;
5. missing or stale evidence;
6. a proposed update order and verification scope; and
7. a clearly separated list titled **Developer-reviewed commands (not run)**.

Do not describe an advisory as resolved merely because a compatible release
exists. Resolution requires a reviewed local change plus later CI/runtime
evidence.
