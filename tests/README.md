# PHP tests

Run from the repository root with PHP 8.0 or later, SimpleXML, and DOM enabled.
First download the two standalone schemas used by the project's XML files:

```sh
mkdir -p .tmp/schemas/3.2
curl --fail --location https://rudnerbjoern.github.io/iTop-schema/extension.xsd -o .tmp/schemas/extension.xsd
curl --fail --location https://rudnerbjoern.github.io/iTop-schema/3.2/itop_design.xsd -o .tmp/schemas/3.2/itop_design.xsd
```

PowerShell alternative:

```powershell
New-Item -ItemType Directory -Force .tmp/schemas/3.2 | Out-Null
Invoke-WebRequest https://rudnerbjoern.github.io/iTop-schema/extension.xsd -OutFile .tmp/schemas/extension.xsd
Invoke-WebRequest https://rudnerbjoern.github.io/iTop-schema/3.2/itop_design.xsd -OutFile .tmp/schemas/3.2/itop_design.xsd
```

Alternatively, set `ITOP_SCHEMA_DIR` to an existing iTop-schema checkout containing
`extension.xsd` and `3.2/itop_design.xsd`. Then run:

```sh
php tests/run.php
```

On Windows, use the same command with `php.exe` if needed.
No Composer packages, database, or iTop installation are required.
The runner prints each result and exits with `0` on success or `1` on failure.

For every push and every PR targeting `main`, CI sets up PHP 8.3 with DOM and SimpleXML,
downloads both schemas into `.tmp/schemas`, and runs the same command.
The required `PR checks` job also requires this test job to succeed; a schema
download failure fails the job. Push-only runs execute both Gitleaks and PHP tests.
For `release/*` PRs, CI also validates the `release/X.Y.Z` branch name against
the manifest and module versions, and includes that result in `PR checks`.

The tests execute `OnFunctionalCICheckToWriteBCM` extracted from the local XML
model, using minimal substitutes for changes, attribute access, check issues,
event data, and dictionary formatting. They cover RTO/MTD ordering, equal values,
empty values, numeric strings, and validation only when RTO or MTD changes.
Add validation cases to `bcm-validation.php`. Release preparation tests check
metadata updates and rejection of invalid versions, dates, or inconsistent input.
They also check that compatibility and historical migration versions remain unchanged.

`module.php` loads the real module with small iTop substitutes. It checks module
registration, initial audit setup, version-gated upgrades, category/domain links,
and installation without audit classes. These checks use an in-memory object
store; they do not parse or execute audit OQL against iTop.

Two additional tests validate the manifest and data model against the local XSDs.
Missing schemas fail the tests with setup instructions. Tests run offline after
setup; refresh the cached schemas explicitly when needed. Results apply to the
schema versions present locally.

These tests do not test iTop event dispatch, attribute flags, persistence, or installation.
Only run the test kit against trusted local project code: the extracted PHP
method is executed using `eval`.
