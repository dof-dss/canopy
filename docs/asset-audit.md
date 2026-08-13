# Media and file asset audit

The asset audit evaluates committed Drupal configuration against a
version-controlled media and file capability profile. Every discovered config
directory is reported as a distinct target, so differences between demo sites
in a shared Unity repository remain visible and attributable.

## Run it

```shell
bin/canopy audit assets --project=site-a=~/projects/site-a
bin/canopy audit assets \
  --inventory=config/inventories/example.yml
bin/canopy audit assets \
  --inventory=config/inventories/example.yml \
  --format=json
```

Use `--profile=/path/to/profile.yml` to replace the bundled
`config/assets/nics.yml` profile.

## NICS baseline

The initial profile is derived from capabilities shared by DEPT and NIDirect:

- reusable image and document media through Media Library;
- valid media source plugins and matching source-field storage;
- explicit upload extension allowlists on configured file and image fields;
- configured image derivative styles;
- Responsive Image with at least one configured responsive style; and
- private file storage as a preferred capability.

Private storage is preferred rather than required because a public-only demo
site is not inherently unhealthy. Its absence is reported as a per-site
warning for review.

## Evidence and isolation

The connector discovers both `config/sync` and
`project/config/<site>/config`. Result targets use `repository:site`, for
example `unity4:anotherway`, and JSON evidence always includes the exact
relative config path.

The connector reads module enablement, media types and source fields, field
storage types and URI schemes, field-instance extension policies, image
styles, and responsive image styles. Inventory summaries include bounded
counts and identifiers, never uploaded file contents or field values.

## Boundaries

This is a static exported-configuration audit. It does not inspect files on
disk, storage usage, orphaned files, derivatives, private-path filesystem
permissions, malware scanning, remote media availability, active Drupal
configuration, or editor access. Future runtime connectors must report those
observations separately and require explicit authorisation.
