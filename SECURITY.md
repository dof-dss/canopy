# Security policy

## Reporting a vulnerability

Do not disclose a suspected vulnerability, credential, private audit result,
or affected target through a public issue or pull request.

Use GitHub's **Report a vulnerability** control in the repository Security tab
to open a private vulnerability report. If that control is unavailable,
contact the responsible dof-dss maintainers through an established private
departmental channel and include only the minimum evidence needed to reproduce
the issue.

Report:

- the affected Canopy version or commit;
- the security boundary that can be crossed;
- minimal reproduction steps using synthetic data;
- likely impact; and
- any known workaround.

Do not include live credentials, personal data, database contents, response
bodies, or an unredacted audit report. Maintainers should acknowledge the
report privately, agree a disclosure plan, and publish remediation only after
affected users have a reasonable opportunity to update.

## Supported versions

Until Canopy publishes stable releases, only the current default branch is
supported. Published releases should add an explicit supported-version table
here before the first stable tag.

## Security boundary

Canopy is an assurance tool, not a security sandbox. Static inspection is the
default. Runtime, network, container, database, hosting, and trusted project
code execution are separate capabilities and require explicit permission.
Generated audit results must be classified independently from this source
repository; see [output security](docs/output-security.md).
