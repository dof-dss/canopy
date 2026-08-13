# Inventory examples

This directory contains synthetic examples of Canopy inventories. An inventory
identifies the concrete repositories to audit using stable IDs and paths, and
may provide expectations or justified exceptions for individual targets.

Real inventories should normally be owned by the consuming project as
`.canopy/inventory.yml`. Do not store credentials, private hostnames, or other
sensitive estate data in this public example directory.

Pass an inventory explicitly with `bin/canopy audit <pack> --inventory=<path>`,
or run Canopy from a project containing `.canopy/inventory.yml`.
