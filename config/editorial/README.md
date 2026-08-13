# Editorial profiles

This directory contains reusable capability profiles for the `editorial` audit
pack. A profile defines the editorial capabilities to check, how Canopy detects
them, and whether each capability is required, preferred, or optional.

The bundled `nics.yml` profile is used by default. Select another compatible
profile with `bin/canopy audit editorial --profile=/path/to/profile.yml`.

Profiles describe shared expectations. Target-specific paths and exceptions
belong in the consuming project's `.canopy/inventory.yml`.
