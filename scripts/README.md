# Prepare a release

Set up the local schemas as described in [the test instructions](../tests/README.md),
then run from the repository root:

```sh
php scripts/prepare-release.php 3.2.3
```

This updates the extension manifest version, module registration version, and
manifest release date together. It also updates the date-based `@version` headers
in the module and all `*.dict.br-riskassessment.php` dictionaries, then runs
`tests/run.php`. The default date uses Europe/Berlin. Supply a date explicitly
when needed:

```sh
php scripts/prepare-release.php 3.2.3 2026-11-06
```

The version must be newer than the existing version and use `X.Y.Z` format.
The current manifest and module versions must match. All inputs and file contents
are checked before writing. Missing or ambiguous date-based PHP headers are
rejected. A write failure triggers an attempt to restore all originals; this is
not a transaction across filesystem failures. Existing formatting outside the
release metadata is preserved. The minimum iTop version, module dependency
versions, XML design version, historical migration thresholds, and README's
tested iTop versions remain unchanged.

If tests fail, the command exits unsuccessfully and retains the version edits
for inspection. Resolve failures before publishing. The script does not stage,
commit, push, tag, or publish anything.

Use a release branch named `release/<version>`, for example `release/3.2.3`,
for this preparation. Review the diff, commit on that branch, and open a PR
into `main`. PR CI requires the branch version to match both project versions.

Merging a `release/X.Y.Z` PR from this same repository automatically starts the
`Release` workflow. The merge is the publication approval: the workflow verifies
the successful `PR checks` result and publishes the merge commit as tag `vX.Y.Z`,
ZIP package, and GitHub Release. Closing an unmerged PR, merging another branch
prefix, or merging a fork's PR does not publish a release.

The ZIP contains a single top-level `br-riskassessment/` folder. Inside it,
`extension.xml`, `LICENSE`, and the `br-riskassessment/` module folder retain their
project-relative structure. Extracting into iTop's `extensions/` directory keeps
all package files under `extensions/br-riskassessment/`.

Each release includes `br-riskassessment-vX.Y.Z.zip.sha256` alongside its ZIP.
Download both into the same directory and run
`sha256sum --check br-riskassessment-vX.Y.Z.zip.sha256` to verify the package.
On recovery, already uploaded assets must match the regenerated files before
the workflow adds a missing asset or publishes an interrupted draft.

The `Release` workflow can still be started manually on `main` for recovery.
Retry the original run to retain its exact release commit if `main` has advanced.
Existing tags are reused only when they point to the same commit. Missing or
incomplete uploads and interrupted drafts can be completed by a repeat run.
