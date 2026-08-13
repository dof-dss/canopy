# Editorial capability audit

The editorial audit compares exported Drupal configuration with a named,
version-controlled capability profile. It is designed to identify meaningful
editorial differences across the NICS Drupal estate without requiring every
site to have byte-identical configuration.

## Run it

From a Canopy checkout:

```shell
bin/canopy audit editorial --project=site-a=~/projects/site-a
bin/canopy audit editorial \
  --inventory=config/inventories/example.yml
bin/canopy audit editorial \
  --inventory=config/inventories/example.yml \
  --format=json
```

When Canopy is installed through Composer, a project DDEV wrapper can delegate
to `vendor/bin/canopy` using the same arguments. The command is read-only and
does not bootstrap Drupal or execute code from an audited repository.

Use `--profile=/absolute/or/relative/profile.yml` to select another capability
profile. Without it, Canopy uses `config/editorial/nics.yml` from the installed
package.

## NICS baseline

The bundled profile records capabilities shared by the DEPT and NIDirect
applications:

- revision history for moderated content, with webforms recorded as the known
  non-revisioned exception;
- draft, review, published, and archived moderation states;
- submission, publishing, rejection, archival, and restoration transitions;
- distinct author/submission and publisher permissions;
- reusable image and document media;
- automatic aliases, redirects, and structured metadata;
- review-date fields as a preferred capability;
- scheduled publishing through either Scheduler or Scheduled Transitions; and
- concurrent-edit protection through either Content Lock or Node Edit
  Protection.

The alternatives are deliberate: the profile describes what an editor can do,
while detectors map that capability to recognised Drupal implementations.

## Discovery and evidence

The connector recognises these repository layouts:

- `config/sync` for a single Drupal application; and
- `project/config/<site>/config` for a Unity-family multisite repository.

For each site it reads `core.extension`, node type revision defaults, enabled
content moderation workflows, role permissions, media types, node field names,
Pathauto patterns, and Metatag defaults. Results identify the repository, site,
profile, capability expectation, config path, observed values, and missing or
matched evidence.

Required capabilities produce `fail` when their exported evidence is absent.
Preferred capabilities produce `warn`. Optional capabilities produce `skipped`
when absent. Missing or unreadable projects and unparseable configuration are
`unknown`. Any `fail` or `unknown` gives the command a non-zero exit code.

## Boundaries

This first connector evaluates committed exported configuration. A passing
result does not establish that:

- the export matches Drupal's active configuration;
- permissions are effective after all configuration splits and overrides;
- workflow forms and transitions are usable by real accounts;
- scheduled transitions, cron, queues, or notifications operate correctly; or
- the capability provides a good editorial experience.

Those questions require separate, explicitly authorised runtime connectors.
Keep their findings distinct from this static evidence so an unavailable DDEV
environment cannot invalidate or overstate the repository audit.
