# FDIC Drupal Theme — Implementation Rubric

Scoring guide for evaluating the quality of the `fdic-drupal-theme` implementation.
Each criterion is rated 0–3:

| Score | Meaning |
|-------|---------|
| 0 | Missing or fundamentally broken |
| 1 | Present but incomplete or incorrect |
| 2 | Functional with minor issues |
| 3 | Correct, complete, and well-crafted |

---

## 1. Package & Dependency Management (15 pts)

| # | Criterion | Max | What "3" looks like |
|---|-----------|-----|---------------------|
| 1.1 | `package.json` declares `@fdic-ds/components` and `@fdic-ds/tokens` as dependencies with semver ranges | 3 | Both listed with `^0.1.0`, `private: true`, no extraneous dependencies |
| 1.2 | `.npmrc` scopes `@fdic-ds` to GitHub Packages registry | 3 | Single line `@fdic-ds:registry=https://npm.pkg.github.com`, no hardcoded auth tokens |
| 1.3 | No vendored/copied DS assets — all references point to `node_modules/@fdic-ds/` | 3 | Zero DS source files checked into the theme repo; libraries.yml paths all start with `node_modules/@fdic-ds/` |
| 1.4 | `.gitignore` excludes `node_modules/` | 3 | Present and correct |
| 1.5 | No build step required for the theme itself | 3 | Theme works with just `npm install` + `drush cr` — no webpack, no PostCSS, no compilation |

## 2. Drupal Theme Definition (12 pts)

| # | Criterion | Max | What "3" looks like |
|---|-----------|-----|---------------------|
| 2.1 | `fdic.info.yml` is valid Drupal 10+ theme config | 3 | Correct `type: theme`, `core_version_requirement`, `base theme`, machine name matches filename |
| 2.2 | Regions defined appropriately | 3 | At minimum: `header`, `content`, `sidebar_first`, `sidebar_second`, `footer`, `page_top`, `page_bottom`, `breadcrumb`, `highlighted` |
| 2.3 | Global libraries attached | 3 | `libraries:` key attaches tokens, components, and theme CSS globally |
| 2.4 | `fdic.libraries.yml` declares all libraries correctly | 3 | Each library has correct paths, `type: module` attribute on ES module JS, dependency chains are correct (`global-header` → `components` → `tokens`) |

## 3. Token Integration (9 pts)

| # | Criterion | Max | What "3" looks like |
|---|-----------|-----|---------------------|
| 3.1 | `semantic.css` loaded as a base CSS library | 3 | Listed under `css: > base:` in libraries.yml; `interaction.css` is auto-imported by semantic.css so not separately declared |
| 3.2 | Theme CSS uses DS tokens, not hardcoded values | 3 | `theme.css` references `var(--ds-color-*)`, `var(--ds-focus-*)`, `var(--ds-motion-*)` — zero hardcoded colors/spacing that duplicate token values |
| 3.3 | Token fallbacks where appropriate | 3 | Any token usage in theme-owned CSS includes a fallback value for resilience: `var(--ds-color-bg-surface, #ffffff)` |

## 4. Component Integration (18 pts)

| # | Criterion | Max | What "3" looks like |
|---|-----------|-----|---------------------|
| 4.1 | `register-all.js` loaded as ES module | 3 | `attributes: { type: module }` in libraries.yml; script tag will render with `type="module"` |
| 4.2 | Global header uses the Drupal adapter | 3 | `createFdGlobalHeaderContentFromDrupal()` called with menu data; result set on `<fd-global-header>` `.content` property |
| 4.3 | Menu data serialization in PHP is correct | 3 | `fdic_preprocess_page()` outputs a JSON structure matching `DrupalMenuLinkLike` interface: `title`, `url`, `current`, `below[]`, `description`, `keywords`, `overviewTitle`, `overviewUrl`, `ariaLabel` |
| 4.4 | Menu data passed via `<script type="application/json">` | 3 | JSON block in header region template; JS reads it with `JSON.parse()`; no inline event handlers or global variables |
| 4.5 | Status messages map to `<fd-alert>` variants | 3 | `status`→`success`, `warning`→`warning`, `error`→`error`, default→`info`; messages loop correctly; ARIA attributes present |
| 4.6 | Pager replaced with `<fd-pagination>` | 3 | Current page, total pages, and page URLs passed as attributes/properties; previous/next links work; accessible |

## 5. Form Element Templates (18 pts)

| # | Criterion | Max | What "3" looks like |
|---|-----------|-----|---------------------|
| 5.1 | `<fd-input>` replaces text inputs | 3 | Twig template passes `name`, `value`, `type`, `required`, `disabled`, `placeholder` attributes through |
| 5.2 | `<fd-textarea>` replaces textareas | 3 | Passes `name`, `value`, `rows`, `required`, `disabled` through |
| 5.3 | `<fd-selector>` replaces selects | 3 | Options rendered correctly; selected state preserved; `required`/`disabled` passed through |
| 5.4 | `<fd-checkbox>` / `<fd-radio>` replace native equivalents | 3 | `name`, `value`, `checked`, `required`, `disabled` all pass through; group wrappers use `<fd-checkbox-group>` / `<fd-radio-group>` |
| 5.5 | `<fd-field>` + `<fd-label>` wrap form elements | 3 | Error messages, descriptions, and required markers rendered in the field wrapper; `for`/`id` association correct |
| 5.6 | Progressive enhancement — forms work without JS | 3 | Native HTML `<input>`, `<select>`, `<textarea>` are present in the DOM as fallbacks or within the shadow DOM; form submission works if custom element JS fails to load |

## 6. Twig Template Quality (15 pts)

| # | Criterion | Max | What "3" looks like |
|---|-----------|-----|---------------------|
| 6.1 | `html.html.twig` is correct and complete | 3 | `lang` attribute on `<html>`, proper `<head>` structure, `{{ page_top }}`, `{{ page }}`, `{{ page_bottom }}` in body, `no-js`/`js` class toggle |
| 6.2 | `page.html.twig` uses semantic landmark structure | 3 | `<header>`, `<main id="main-content">`, `<footer>` landmarks; skip link target is `#main-content`; sidebar regions conditional |
| 6.3 | Region templates are minimal and correct | 3 | Each region template wraps `{{ content }}` with appropriate DS markup; no logic duplication with page template |
| 6.4 | `node.html.twig` applies `.prose` and `<fd-page-header>` | 3 | Title rendered in `<fd-page-header>`; body content wrapped in `.prose`; metadata (author, date) rendered appropriately |
| 6.5 | Templates use Drupal's `attach_library()` correctly | 3 | Region/component-specific libraries attached where needed (e.g., `{{ attach_library('fdic/global-header') }}` in the header region, not globally) |

## 7. PHP Theme Functions (9 pts)

| # | Criterion | Max | What "3" looks like |
|---|-----------|-----|---------------------|
| 7.1 | `fdic_preprocess_page()` serializes menu tree correctly | 3 | Uses `\Drupal::menuTree()` with proper parameters; recursively maps `MenuLinkTreeElement` to `DrupalMenuLinkLike` shape; handles empty menus gracefully |
| 7.2 | `fdic_preprocess_html()` adds class toggle | 3 | Adds `no-js` to `<html>` classes; small inline script swaps to `js` — standard progressive enhancement pattern |
| 7.3 | PHP follows Drupal coding standards | 3 | Correct `<?php` opening, `use` statements, proper hook naming, type hints where Drupal conventions allow, doc blocks on functions |

## 8. Accessibility (12 pts)

| # | Criterion | Max | What "3" looks like |
|---|-----------|-----|---------------------|
| 8.1 | Skip link present and functional | 3 | `<a href="#main-content" class="visually-hidden focusable">Skip to main content</a>` as first focusable element |
| 8.2 | ARIA landmarks correct | 3 | `<header role="banner">`, `<main role="main">`, `<footer role="contentinfo">`; no duplicate landmarks; nav regions have `aria-label` |
| 8.3 | Alert/message ARIA roles correct | 3 | `<fd-alert>` instances include appropriate `role` attributes; error messages use `role="alert"` or `aria-live="assertive"` |
| 8.4 | Form labels associated correctly | 3 | Every form control has an associated label via `for`/`id` or wrapping; required fields indicated visually and programmatically |

## 9. Documentation (6 pts)

| # | Criterion | Max | What "3" looks like |
|---|-----------|-----|---------------------|
| 9.1 | README covers setup and usage | 3 | Prerequisites, install steps, how libraries reference node_modules, how to update DS version, and how to add new component templates |
| 9.2 | Code comments explain non-obvious decisions | 3 | PHP preprocess functions document the data shape; Twig templates note which DS component they map to; libraries.yml comments explain dependency chain |

## 10. Architecture & Separation of Concerns (6 pts)

| # | Criterion | Max | What "3" looks like |
|---|-----------|-----|---------------------|
| 10.1 | Clean dependency direction | 3 | Theme depends on DS packages only via npm; no circular references; no DS source code in the theme; adapter code stays in the DS package |
| 10.2 | No unnecessary complexity | 3 | No custom build pipeline; no framework bridges; no PHP classes where hooks suffice; no JS where Twig can pass attributes directly |

---

## Scoring Summary

| Category | Max Points |
|----------|-----------|
| 1. Package & Dependency Management | 15 |
| 2. Drupal Theme Definition | 12 |
| 3. Token Integration | 9 |
| 4. Component Integration | 18 |
| 5. Form Element Templates | 18 |
| 6. Twig Template Quality | 15 |
| 7. PHP Theme Functions | 9 |
| 8. Accessibility | 12 |
| 9. Documentation | 6 |
| 10. Architecture & Separation of Concerns | 6 |
| **Total** | **120** |

## Grade Thresholds

| Grade | Score | Meaning |
|-------|-------|---------|
| A | 108–120 (90%+) | Production-ready with minimal review |
| B | 96–107 (80–89%) | Solid foundation, minor gaps to address |
| C | 84–95 (70–79%) | Functional but needs significant polish |
| D | 72–83 (60–69%) | Major gaps — needs rework before use |
| F | < 72 (< 60%) | Incomplete — restart or restructure |

## Critical Failures (automatic F regardless of score)

Any of these issues override the numeric score:

1. **DS assets vendored/copied** instead of consumed from `node_modules`
2. **Auth tokens committed** to `.npmrc` or any file
3. **`display: block` on `<table>`** (breaks screen reader navigation)
4. **No skip link** or broken skip link target
5. **Form submission broken** when JS is disabled
6. **Invalid `fdic.info.yml`** that prevents Drupal from recognizing the theme
7. **Hardcoded menu data** instead of dynamic menu tree loading
