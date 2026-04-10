# Task: Review the FDIC Drupal Theme Against the Implementation Rubric

You are reviewing the `fdic-drupal-theme` repository against a detailed rubric.
Your job is to read every file in the repo, score each criterion, identify gaps,
and produce a concrete improvement plan.

## Repositories

- **Theme repo (under review):** The current working directory (`fdic-drupal-theme`)
- **Design system repo (reference):** `../fdic-design-system`
  - Components package: `../fdic-design-system/packages/components/`
  - Tokens package: `../fdic-design-system/packages/tokens/`
  - Drupal adapter: `../fdic-design-system/packages/components/src/fd-global-header-drupal.ts`

Read files from the design system repo as needed to verify the theme's integration
is correct (e.g., checking that attribute names, export paths, and data shapes match).

## Review Process

### Step 1: Inventory

List every file in the theme repo. Note any files that are missing from the
expected file tree:

```
fdic-drupal-theme/
├── .npmrc
├── .gitignore
├── package.json
├── fdic.info.yml
├── fdic.libraries.yml
├── fdic.theme
├── css/
│   └── theme.css
├── js/
│   └── global-header-init.js
├── templates/
│   ├── layout/
│   │   ├── html.html.twig
│   │   ├── page.html.twig
│   │   ├── region--header.html.twig
│   │   ├── region--footer.html.twig
│   │   └── region--content.html.twig
│   ├── content/
│   │   └── node.html.twig
│   ├── misc/
│   │   └── status-messages.html.twig
│   ├── navigation/
│   │   └── pager.html.twig
│   └── form/
│       ├── input.html.twig
│       ├── textarea.html.twig
│       ├── select.html.twig
│       ├── form-element.html.twig
│       ├── form-element--checkbox.html.twig
│       └── form-element--radio.html.twig
└── README.md
```

### Step 2: Read and score

Read every file in the repo. For each rubric criterion, assign a score (0–3)
with a one-line justification. Use this format:

```
| # | Criterion | Score | Justification |
|---|-----------|-------|---------------|
| 1.1 | package.json dependencies | 2 | Both packages listed but using fixed version instead of semver range |
```

### Step 3: Check critical failures

Evaluate each of the 7 critical-failure conditions. If ANY are triggered,
the overall grade is F regardless of numeric score.

Critical failures:
1. DS assets vendored/copied instead of consumed from `node_modules`
2. Auth tokens committed to `.npmrc` or any file
3. `display: block` on `<table>` (breaks screen reader navigation)
4. No skip link or broken skip link target
5. Form submission broken when JS is disabled
6. Invalid `fdic.info.yml` that prevents Drupal from recognizing the theme
7. Hardcoded menu data instead of dynamic menu tree loading

### Step 4: Cross-reference with the design system

Verify these integration points against the actual DS source code:

- [ ] `libraries.yml` paths match the real export paths in `@fdic-ds/components/package.json`
      (check the `exports` map — the theme must use paths that exist)
- [ ] Menu data shape in PHP matches the `DrupalMenuLinkLike` interface in
      `fd-global-header-drupal.ts` (fields: `title`, `url`, `current`, `below`,
      `description`, `keywords`, `overviewTitle`, `overviewUrl`, `ariaLabel`)
- [ ] `global-header-init.js` imports from the correct package export path
      (`@fdic-ds/components/fd-global-header-drupal`)
- [ ] Token variable names used in `theme.css` match actual variables defined in
      `semantic.css` and `interaction.css`
- [ ] Component tag names and attribute names used in Twig templates match the
      actual Lit component definitions in the DS

### Step 5: Verify Drupal correctness

Check these Drupal-specific requirements:

- [ ] `fdic.info.yml` has valid syntax and all required keys (`name`, `type`,
      `core_version_requirement`, `regions`)
- [ ] `fdic.libraries.yml` uses correct Drupal library syntax (CSS categories:
      `base`/`layout`/`component`/`theme`/`state`; JS attributes for ES modules)
- [ ] PHP hook names follow `fdic_hookname()` pattern
- [ ] Twig templates extend or override the correct Drupal base templates
- [ ] `attach_library()` calls use correct `theme/library` syntax
- [ ] Form templates preserve Drupal's form token and hidden fields for CSRF

## Output Format

Produce your report in this exact structure:

### Score Card

The full criterion-by-criterion table (all rows from the rubric).

### Critical Failure Check

| # | Condition | Pass/Fail | Notes |
|---|-----------|-----------|-------|

### Category Totals

| Category | Score | Max | % |
|----------|-------|-----|---|
| 1. Package & Dependency Management | ? | 15 | |
| 2. Drupal Theme Definition | ? | 12 | |
| 3. Token Integration | ? | 9 | |
| 4. Component Integration | ? | 18 | |
| 5. Form Element Templates | ? | 18 | |
| 6. Twig Template Quality | ? | 15 | |
| 7. PHP Theme Functions | ? | 9 | |
| 8. Accessibility | ? | 12 | |
| 9. Documentation | ? | 6 | |
| 10. Architecture & Separation of Concerns | ? | 6 | |
| **Total** | **?** | **120** | |

### Grade: ?

### Cross-Reference Issues

List any mismatches found between the theme and the actual DS source code.

### Top Issues (ranked by impact)

For each issue, include:
1. **What's wrong** — specific file and line
2. **Why it matters** — which rubric criterion it affects and by how many points
3. **How to fix** — concrete change (code snippet, file to create, line to edit)

### Improvement Plan

Group fixes into phases:

**Phase 1: Critical fixes** (required to pass)
- Numbered list of changes, each with file path and description

**Phase 2: Score improvements** (to reach grade A)
- Numbered list, ordered by points recovered (highest first)

**Phase 3: Polish** (nice-to-have)
- Numbered list of enhancements beyond the rubric

---

Do NOT make any changes to the code. This is a read-only review.
Read every file thoroughly before scoring — do not guess or assume content.
