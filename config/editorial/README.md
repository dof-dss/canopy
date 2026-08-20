# Editorial profiles

This directory contains reusable capability profiles for the `editorial` audit
pack. A profile defines the editorial capabilities to check, how Canopy detects
them, and whether each capability is required, preferred, or optional.

The bundled `nics.yml` profile is used by default. Select another compatible
profile with `bin/canopy audit editorial --profile=/path/to/profile.yml`.

Profiles describe shared expectations. Target-specific paths and exceptions
belong in the consuming project's `.canopy/inventory.yml`.

Register CKEditor 5 toolbar policies by text format in a top-level
`ckeditor5_toolbars` mapping and classify buttons explicitly:

```yaml
ckeditor5_toolbars:
  basic_html:
    label: Basic HTML toolbar
    expectation: required
    core: [heading, link, bulletedList, numberedList]
    optional: [siteSpecificButton]
    unexpected: fail
```

Core buttons must be present. Optional buttons may be present or absent.
Separators are ignored. With `unexpected: fail`, every other configured button
is an unregistered variation and fails the capability; use `allow` only when a
profile deliberately permits unrestricted additions.

Optional items may be an item-to-reason mapping. The reason is included in
result evidence whenever that variation is observed:

```yaml
optional:
  chart: DEPT integrates its charting content model with CKEditor.
  location: NIDirect integrates location and map content with CKEditor.
```

Text-format filter policy uses the same classification model:

```yaml
text_formats:
  basic_html:
    core_filters: [filter_allowed, linkit, media_embed]
    optional_filters:
      site_filter: Required by the site's reviewed specialist feature.
    unexpected: fail
```

Use `config_patterns` capabilities to require durable exported configuration
as well as an enabled module. Patterns use shell-style `*` matching against
configuration names without the `.yml` suffix.

The bundled NICS profile owns the shared `basic_html` core and the reviewed
NIDirect/DEPT reference variations. Other consuming projects should select a
project-owned profile which keeps the shared core and gives reasons for its
additional supported items. This makes the variation explicit without treating
it as a universal baseline.
