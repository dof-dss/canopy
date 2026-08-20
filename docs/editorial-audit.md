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

The NIDirect consumer integration provides the maintained NIDirect, DEPT, and
Unity estate DDEV comparison command. Unity is exposed as a multi-site project,
so discovery includes every exported site below `project/config/<site>/config`
while the consumer inventory records the expected site IDs. A missing expected
export is `unknown`; an additional discovered but undeclared site is `warn`.
The default report compares status totals and lists non-passing findings;
`--detail` lists every check. `--status` can be repeated or accept a
comma-separated list.
Filtering changes what is displayed, but the exit code still reflects the
complete unfiltered audit, so hiding a failure cannot make CI pass.

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
- Basic HTML text-filter and CKEditor toolbar policy;
- Diff revision comparison settings and per-bundle displays;
- Metatag shared defaults and registered schema extensions;
- Pathauto patterns and automatic Redirect policy;
- Simple XML Sitemap settings, variants, types, and bundle exports;
- review-date fields as a preferred capability;
- Scheduled Transitions configuration and its cron queue job; and
- concurrent-edit protection through either Content Lock or Node Edit
  Protection.

The alternatives are deliberate: the profile describes what an editor can do,
while detectors map that capability to recognised Drupal implementations. The
baseline requires every common moderation transition, rather than checking only
the shortest publish path.

## Registered variations

The shared policy does not require byte-identical configuration. Required
items express the common editorial contract. Optional items are reviewed
extensions and are recorded as item-to-reason mappings so the report can show
why an observed difference is accepted. The current reference set records:

- DEPT's chart and anchor toolbar buttons and absolute-link rewriting for its
  multi-domain and charting model;
- DEPT and Unity's explicit bold/italic toolbar buttons;
- NIDirect's location/map toolbar integration and inline-image lazy-loading;
- Schema.org metadata as an optional extension currently present on NIDirect;
  and
- bundle-specific Metatag defaults, aliases, Diff displays, sitemap rules, and
  scheduling coverage as inventory detail rather than universal bundle names.

Do not add an observed item to `optional` merely to turn a failure green. Name
the owning feature, state why it is legitimate, confirm that the common core is
still present, and review the change to the version-controlled profile. Use
`unexpected: fail` for bounded policies so unregistered toolbar or filter drift
remains visible.

## Discovery and evidence

The connector recognises these repository layouts:

- `config/sync` for a single Drupal application; and
- `project/config/<site>/config` for a Unity-family multisite repository.

For each site it reads `core.extension`, node type revision defaults, enabled
content moderation workflows, role permissions, media types, node field names,
Pathauto patterns, Metatag defaults, enabled text-format filters, enabled
CKEditor 5 toolbar items, and the names of exported configuration entities.
Configuration-pattern checks require both the relevant module and durable
exports such as settings, variants, types, cron jobs, and per-bundle displays.
Toolbar and filter profiles classify items as required core capabilities,
registered optional variations with reasons, or unexpected additions. Results
identify the repository, site, profile, capability expectation, config path,
observed values, registered variations, and missing or matched evidence.

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
