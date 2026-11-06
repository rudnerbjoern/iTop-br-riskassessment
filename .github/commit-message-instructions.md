# Commit-message generation

Generate a message only for the staged change in this iTop 3.2 Risk Assessment extension project.
Use English and this header:

```text
:<icon>: <type>(<scope>): <imperative outcome>
```

Requirements:

- Never commit on `main` or push directly to it. Commit on a separate feature
  branch and submit the change through a GitHub pull request targeting `main`.
- Keep the raw header, including the shortcode, at or below 80 characters.
- Use lower-case `type` and `scope`; require one concise capability scope.
- Start the summary with a lower-case imperative verb and omit its final period.
- Describe the outcome, not a file list.
- Use exactly one deterministic type and its matching icon:
  - `:sparkles: feat` for new extension capabilities.
  - `:bug: fix` for corrected behavior.
  - `:recycle: refactor` for restructuring without intended behavior changes.
  - `:zap: perf` for performance improvements.
  - `:test_tube: test` for test coverage or validation behavior.
  - `:memo: docs` for documentation-only changes.
  - `:package: build` for dependencies, packaging, or build tooling.
  - `:gear: ci` for GitHub Actions workflow behavior or CI infrastructure.
  - `:wrench: chore` for maintenance that fits none of the types above.
- Choose the type for the primary staged outcome. Accompanying tests and
  documentation do not change the type of a feature or fix.
- Prefer the domain scope `risk` for protection requirements, risk audit rules,
  field presentation, or field translations, and `bcm` for recovery objectives
  and their validation. Use these scopes even if the implementation is XML or
  embedded PHP.
- Use a technical scope such as `setup`, `maintenance`, `tests`, `deps`, or `ci`
  only for a genuinely cross-cutting or
  technical change. Do not use an upstream module identifier, filename, or
  programming language as the scope.
- Omit the body for a small self-explanatory change.
- Otherwise add `Why:` followed by the motivation, then `Changes:` with only
  the material effects. Mention required iTop setup/model compilation or data
  migrations only when the staged change requires them.
- Add `Refs: #<issue>` for a related issue in this repository or `Fixes: #<issue>`
  when the staged change resolves it. Include references only when explicitly
  established by the staged change or its associated task.
- Add a `BREAKING CHANGE:` footer for an incompatible contract or migration,
  explaining the incompatibility and required action.
- Never invent verification results, issue identifiers, effects, or rationale.
  A syntax/header check is not full XSD validation; local tests or workflow
  validation are not an executed GitHub Actions workflow. Distinguish static
  checks from testing in an installed iTop instance.
- Never include secrets, personal data, customer/CMDB records, database contents,
  generated iTop configuration, or sensitive diagnostic details.
