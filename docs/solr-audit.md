# Solr audit pack

The Solr pack audits committed repository evidence across one project or an
inventory of projects. It does not start DDEV, connect to a Solr service,
bootstrap Drupal, or inspect Search API tracker state.

## Usage

Audit the current project:

```shell
bin/canopy audit solr
```

Audit named projects:

```shell
bin/canopy audit solr \
  --project=unity1=/path/to/unity \
  --project=dept=/path/to/dept
```

Audit an inventory and emit structured evidence:

```shell
bin/canopy audit solr \
  --inventory=config/inventories/nics-drupal.example.yml \
  --format=json
```

Canopy does not execute repository-owned scripts by default. For trusted
projects, explicitly add `--run-project-verifier` to execute an available
`scripts/solr/verify-configsets` and normalize its exit status, `ERROR` lines,
and `NOTICE` lines.

## Inventory expectations

An inventory can state the intended baseline independently for each project:

```yaml
projects:
  - id: example
    path: /path/to/example
    expectations:
      solr:
        version: '9.9'
        lucene_match_version: '9.12.2'
```

A discovered configset that disagrees with an explicit Lucene expectation is a
failure. Multiple service versions are a warning when the expected version is
still present, and a failure when it is absent. Without expectations, Canopy
records declarations and warns about ambiguity rather than imposing another
project family's baseline.

## Supported configset layouts

- `.platform/solr_configsets/<site>/conf` for per-site configsets.
- `.platform/solr_config` for older single hosted configsets.
- `.platform/solr*_config` for version-named hosted configsets.
- `.ddev/solr/conf` for older local configsets.
- `.ddev/solr/configsets/<name>` for local named configsets.

## Checks

| Check ID | Evidence and classification |
| --- | --- |
| `solr.service.version_declarations` | Reads version declarations from hosted services, DDEV Compose, and the Solr Dockerfile. |
| `solr.configset.discovery` | Records discovered repository-owned sources; no source is `unknown`. |
| `solr.configset.completeness` | Requires `schema.xml` and `solrconfig.xml`; records content and manifest SHA-256 fingerprints. |
| `solr.configset.compatibility_metadata` | Reads `solrcore.properties` and compares `solr.luceneMatchVersion` with any explicit expectation. |
| `solr.configset.source_parity` | Compares duplicate hosted/local sources by relative filename and content hash. Differences are warnings because they may be intentional. |
| `solr.project_verifier` | Explicitly executes and classifies a trusted project verifier. Missing verifiers are skipped. |
| `solr.estate.configset_structure` | Groups configsets by file manifest and warns when structural variants exist. |
| `solr.estate.lucene_compatibility` | Groups declared Lucene versions and configsets without compatibility metadata. |

Terminal output summarizes each project and displays non-passing results. JSON
contains every result and its bounded evidence. The command exits unsuccessfully
when at least one result is `fail` or `unknown`; warnings and deliberately
skipped checks do not by themselves fail the command.

## Limitations

Static consistency is not runtime readiness. A complete configset and passing
project verifier do not prove that a persistent local core loaded the current
files, Drupal uses the intended connector, indexes are enabled, trackers are
complete, document counts are credible, or the service has adequate resources.
Those require separately permissioned runtime checks.
