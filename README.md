# Canopy

Canopy is a read-only technical assurance toolkit for Drupal codebases. It
collects structured evidence from a project, its DDEV environment, Drupal, and
supporting services such as Solr, then presents findings that developers can
review and act on.

Canopy is intended to work independently of any project generator or hosting
platform. Projects install a versioned Composer package and can expose it as a
small DDEV command. The same result format can later support CI reports,
cross-project comparisons, or a separate dashboard.

## Goals

- Give developers one consistent way to inspect one or more Drupal projects.
- Keep observations separate from remediation; an audit must not change the
  project, database, index, or hosting environment.
- Combine static repository evidence with explicitly authorised runtime checks.
- Distinguish a failed check from one that is unknown or deliberately skipped.
- Describe editorial and operational capabilities without requiring identical
  Drupal configuration in every project.
- Produce stable, redacted, machine-readable results alongside concise human
  reports.
- Allow prompts to explain evidence and guide investigation without making AI
  output authoritative.

## Intended audit packs

The first planned packs are:

- **Solr:** versions, configsets, service wiring, Drupal connectors, cores, and
  Search API tracker state.
- **Drupal configuration:** schema, dependencies, active/exported drift, Config
  Split behaviour, and environment-specific values.
- **Editorial capabilities:** revisions, moderation, preview, media, redirects,
  aliases, audit history, and other profile-defined capabilities.
- **Update readiness:** Composer blockers, security advisories, patches,
  deprecated APIs, database updates, and regression guidance.

Possible later packs include dependency health, custom-code quality, multisite
parity, security posture, caching and performance, content-model quality, CI
readiness, and operational recovery.

## Status model

Every deterministic check must return one of:

- `pass` — the observed evidence satisfies the check.
- `warn` — review is advisable, but the condition is not an established failure.
- `fail` — the observed evidence violates a defined expectation.
- `unknown` — the check ran but could not reach a reliable conclusion.
- `skipped` — the check was deliberately not run, commonly because permission
  or a required runtime dependency was unavailable.

Missing Docker access, a database, credentials, or network permission must
never be presented as a pass.

## Proposed usage

The first implemented audit pack statically inspects committed Solr configsets
across one or more projects:

```shell
bin/canopy audit solr --project=/path/to/drupal-project
bin/canopy audit solr \
  --project=unity1=/path/to/unity \
  --project=unity2=/path/to/unity2
bin/canopy audit solr --inventory=config/inventories/nics-drupal.example.yml
```

Use `--format=json` for the complete machine-readable evidence. Canopy does not
execute code from an audited repository by default. The Unity-family projects
provide their own additional static verifier; explicitly enable it with:

```shell
bin/canopy audit solr \
  --inventory=config/inventories/nics-drupal.example.yml \
  --run-project-verifier
```

This initial pack discovers modern per-site configsets and older single hosted
or DDEV config directories. It checks required files, fingerprints content and
file manifests, records Lucene compatibility metadata, compares duplicate
hosted/local sources, compares repository service versions, and reports
estate-wide structural and version variants. Inventories may define explicit
`expectations.solr.version` and `expectations.solr.lucene_match_version` values;
Canopy treats configset mismatches against those declared expectations as
failures rather than assuming every project belongs to the same Solr baseline.
It does not yet start DDEV, connect to Solr, bootstrap Drupal, or claim runtime
and indexing health.

The executable also provides an `about` command:

```shell
composer install
bin/canopy about
```

A consuming project may add a thin `.ddev/commands/web/canopy` wrapper which
delegates to `/var/www/html/vendor/bin/canopy`. Audit logic should remain in
this package rather than being copied into project wrappers.

## Design outline

```text
Canopy CLI
├── project and site discovery
├── deterministic audit packs
├── permission-aware runtime adapters
├── capability profiles and project exceptions
├── versioned JSON result documents
├── Markdown and standalone HTML reports
└── optional evidence-grounded prompt workflows
```

The CLI and its JSON contract are the source of truth. A future central service
may store and display results, but should not reimplement checks or require
broader credentials than the checks themselves.

See [the architecture notes](docs/architecture.md) and the initial
[result schema](schemas/result.schema.json). The implemented checks and their
limitations are documented in the [Solr audit guide](docs/solr-audit.md).

## Development

Canopy targets PHP 8.2 or later and supports Symfony 6.4 and 7.x so it can be
installed alongside supported Drupal projects.

```shell
composer install
composer verify
```

The repository is licensed under the [MIT License](LICENSE).
