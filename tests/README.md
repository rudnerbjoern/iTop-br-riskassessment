# PHP tests

Run from the repository root with PHP 8.0 or later, SimpleXML, and DOM enabled.
First download the two standalone schemas used by the project's XML files:

```sh
mkdir -p .tmp/schemas/3.3
curl --fail --location https://rudnerbjoern.github.io/iTop-schema/extension.xsd -o .tmp/schemas/extension.xsd
curl --fail --location https://rudnerbjoern.github.io/iTop-schema/dist/3.3/itop_design.xsd -o .tmp/schemas/3.3/itop_design.xsd
```

PowerShell alternative:

```powershell
New-Item -ItemType Directory -Force .tmp/schemas/3.3 | Out-Null
Invoke-WebRequest https://rudnerbjoern.github.io/iTop-schema/extension.xsd -OutFile .tmp/schemas/extension.xsd
Invoke-WebRequest https://rudnerbjoern.github.io/iTop-schema/dist/3.3/itop_design.xsd -OutFile .tmp/schemas/3.3/itop_design.xsd
```

Alternatively, set `ITOP_SCHEMA_DIR` to an existing iTop-schema checkout containing
`extension.xsd` and `3.3/itop_design.xsd`. Then run:

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
The tests derive the next version and an explicit release date from the manifest.
They also verify the default Europe/Berlin date, refreshing a prepared version,
and preservation of compatibility and historical migration thresholds.

`module.php` loads the real module with small iTop substitutes. It checks module
registration, initial audit setup, version-gated upgrades, category/domain links,
and installation without audit classes. These checks use an in-memory object
store; they do not parse or execute audit OQL against iTop.

Schema tests validate the manifest and both module data models against the local XSDs.
Missing schemas fail the tests with setup instructions. Tests run offline after
setup; refresh the cached schemas explicitly when needed. Results apply to the
schema versions present locally.

The offline suite does not exercise iTop event dispatch or database persistence.
Only run the test kit against trusted local project code: the extracted PHP
method is executed using `eval`.

## Installer tests

Run `php .dev/tests/native.php` for just the module registration and installer
tests. These also run as part of `php tests/run.php` in CI. The native tests
cover historical upgrade thresholds, optional audit classes, a failed write
followed by a retry, and preservation of administrator-edited rules.

To exercise the installed module with MariaDB, run this command from the sibling
iTop 3.3 development workspace. The runner creates and removes its disposable
`itop33-tests` volumes.

```powershell
./scripts/tests/Invoke-Tests.ps1 -Suite Extensions -ExtensionPath ../iTop-br-riskassessment -ExtensionSuite Integration -Profile Vanilla
```

The standalone run verifies that iTop skips the bridge. For the positive case,
run the following command from the Risk Assessment checkout. The helper adds
the real Landscape module to a temporary test bundle so setup selects both
base modules. The integration test checks that iTop selects the bridge and
renders the landscape field exactly once as read-only.

```powershell
./tests/Invoke-LandscapeIntegration.ps1 -DevelopmentRoot ../itop-development-3-3 -LandscapeRoot ../itop-development-3-3/extensions/br-landscape
```

The integration test checks the category, five rules, domain and link created
by iTop Setup. It executes the stored OQL against linked and unlinked servers,
including opposite authenticity and non-repudiation ratings. It then runs the
installer with a 3.2.2 rule, verifies the upgrade to the registered version, and checks that
repeated setup preserves the rule IDs and a locally edited query. Fixture
changes run inside a transaction in the dedicated test database.
