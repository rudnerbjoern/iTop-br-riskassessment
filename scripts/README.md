# Prepare a release

1. Choose the target extension version in `X.Y.Z` format and create the
   corresponding `release/X.Y.Z` branch.
2. In `extension.xml`, write a user-facing summary of this release in
   `version_description`. Set up the [local test schemas](../tests/README.md).
3. Prepare the release from the repository root. Replace the placeholders
   below with the target version and the intended release date.

   ```text
   php scripts/prepare-release.php X.Y.Z YYYY-MM-DD
   ```

   Omitting the date uses today's date in Europe/Berlin. If the feature branch
   already carries the target version, rerun the same command on the release
   branch to refresh the date. Releases on the same day can share a date.
4. Review the manifest, both PHP module registrations, the release date and
   summary. The script also updates the bridge dependency on the bundled base
   module and preserves the required System Landscape version. It runs the native
   tests. The iTop minimum is 3.3.0, the design schema is 3.3, and the required
   Configuration Management, Enhanced Customer Portal and ITIL profiles modules
   each require 3.3.0. These compatibility values and the installer's historical
   migration thresholds are independent of future extension version bumps.
5. Verify the metadata for the intended branch with
   `php scripts/prepare-release.php --check release/X.Y.Z`. Review the test
   results and the diff before committing and opening a release PR into `main`.
   If tests fail, the script leaves the edits available for inspection.

Merging a `release/X.Y.Z` PR from this same repository automatically starts the
`Release` workflow. The merge is the publication approval: the workflow verifies
the successful `PR checks` result and publishes the merge commit as tag `vX.Y.Z`,
ZIP package, and GitHub Release. Closing an unmerged PR, merging another branch
prefix, or merging a fork's PR does not publish a release.

The ZIP contains a single top-level `br-riskassessment/` folder. Inside it,
`extension.xml`, `LICENSE`, and the base and bridge module folders retain their
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
