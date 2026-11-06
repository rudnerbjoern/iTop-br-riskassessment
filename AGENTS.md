# Instructions for AI agents

These instructions apply to every AI agent working in this repository.
Treat MUST and MUST NOT as mandatory requirements.

## Project identity and scope

- You MUST treat this repository as `iTop-br-riskassessment`, an iTop 3.2
  extension for information security protection requirements and BCM recovery
  objectives on `FunctionalCI`, including audit rules and portal views.
- You MUST derive branch names, commit messages, and pull request content from
  the actual changes in this repository and the authorized task.
- You MUST NOT copy project names, issue identifiers, capability scopes, or
  planning conventions from unrelated repositories or previous tasks.
- You MUST write branch names, commit messages, and pull request content in English.

## Authorization and working files

- You MUST obtain explicit user authorization before staging, committing,
  pushing, or creating a pull request. Existing authorization for the respective
  operation remains valid; you MUST NOT request it again unnecessarily.
- You MUST NOT interpret an instruction to edit files as authorization to stage,
  commit, push, or create a pull request.
- You MUST preserve unrelated user changes.
- You MUST treat staged files as reviewed by the user. You MUST NOT modify,
  unstage, replace, or include them in another operation unless explicitly
  requested. You MUST NOT change the index without an explicit request.
- You MUST store temporary project files in `.tmp/`.
- You MUST follow `.editorconfig` and `.gitattributes` when editing files.

## Before creating a branch

- You MUST inspect the current branch and working tree before creating or
  switching branches. You MUST preserve existing user changes.
- You MUST create or use a separate task branch before committing. You MUST NOT
  commit on or push directly to `main`.
- You MUST format branch names as `<type>/<description>` using lower-case
  letters, digits, and hyphens in the description.
- For release preparation, you MUST instead use `release/<version>`, where
  `<version>` is the target version in `X.Y.Z` format without a `v` prefix,
  for example `release/3.2.3`. This is an explicit exception to the description
  format above. You MUST NOT use `build/` or `chore/` for release preparation.
- You MUST select exactly one prefix according to the primary task outcome:

  | Prefix | Primary outcome |
  | --- | --- |
  | `feat/` | New extension capability |
  | `fix/` | Corrected behavior |
  | `doc/` | Documentation-only change |
  | `ci/` | GitHub Actions or CI infrastructure |
  | `refactor/` | Restructuring without intended behavior changes |
  | `perf/` | Performance improvement |
  | `test/` | Test coverage or validation behavior |
  | `build/` | Dependencies, packaging, or build tooling |
  | `release/` | Prepare a specific release version and its metadata |
  | `chore/` | Maintenance that fits none of the preceding outcomes |

- For branches other than `release/<version>`, you MUST make the description
  identify the affected project capability or
  tooling, for example `fix/bcm-recovery-validation` or `ci/release-packaging`.
- You MUST NOT use generic descriptions such as `updates`, `changes`, or `work`.

## Before creating a commit

- You MUST read `.github/commit-message-instructions.md` before generating a
  commit message and apply every requirement in that file.
- You MUST inspect the staged diff and generate the message only for that scope.
  You MUST NOT include unstaged changes or planned work in the message.
- You MUST use this header format:

  ```text
  :<icon>: <type>(<scope>): <imperative outcome>
  ```

- You MUST validate the matching icon and type, lower-case capability scope,
  lower-case imperative summary, absence of a final period, and maximum raw
  header length of 80 characters, including the icon shortcode.
- You MUST use `docs` as the commit type for documentation, even though the
  corresponding branch prefix is `doc/`.
- You MUST commit only the exact staged scope approved by the user.
- You MUST NOT invent issue references, motivation, effects, or verification results.

## Before creating or updating a pull request

- You MUST read `.github/commit-message-instructions.md` and
  `.github/pull_request_template.md` before generating pull request content.
- You MUST use the repository's `.github/pull_request_template.md` as the template
  for every pull request you create or update. You MUST preserve its applicable
  section headings and fill them with information about the actual change.
  You MUST NOT replace it with a generic or independently invented PR template.
- You MUST inspect the complete pull request diff against `main`.
- You MUST target `main` and use the same header format and validation rules for
  the pull request title as for commit messages.
- You MUST describe the complete final pull request scope in the title. You MUST
  NOT derive the title solely from the latest commit when other changes are included.
- You MUST use `.github/pull_request_template.md` for the description and apply
  the motivation, material effects, reference, breaking-change, and verification
  rules from `.github/commit-message-instructions.md` to its content.
- You MUST distinguish local checks, simulated checks, executed GitHub Actions
  runs, and testing in an installed iTop instance. You MUST NOT mark an unperformed
  check as passed.
- You MUST update the title and description if the final change scope changes.
- You MUST NOT merge a pull request without explicit user authorization or bypass
  the required `PR checks` completion check.
- Before requesting or executing a merge of a same-repository `release/X.Y.Z`
  pull request into `main`, you MUST state that merging automatically publishes
  the release tag, ZIP package, and GitHub Release. You MUST ensure the branch
  version matches the manifest and module versions and the required PR checks
  have succeeded. You MUST treat authorization to merge that release PR as
  authorization for its automatic publication.
