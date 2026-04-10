# Task: Scaffold the FDIC Drupal Theme

Create the initial version of a Drupal 10+ theme called `fdic` that consumes the
FDIC Design System web components and tokens.

## Context

The FDIC Design System (`fdic-design-system` repo) publishes two npm packages to
GitHub Packages (`npm.pkg.github.com`):

### `@fdic-ds/tokens` (v0.1.0)
CSS custom property files:
- `semantic.css` — color primitives, semantic roles, dark-mode via `light-dark()`,
  uses `@property` for animated tokens, imports `interaction.css`
- `interaction.css` — focus ring geometry, overlay intensities, motion durations
- `legacy-fdic-colors.css` — backwards-compat hex color map

Token naming convention: `--ds-color-{role}-{variant}` for semantics,
`--ds-color-{family}-{step}` for primitives. Focus tokens: `--ds-focus-*`.
Motion tokens: `--ds-motion-*`.

### `@fdic-ds/components` (v0.1.0)
Lit-based web components, all prefixed `fd-`. Key exports:

**Layout/chrome:**
- `fd-global-header` — site header with mega-menu navigation
- `fd-global-footer` — site footer
- `fd-page-header` / `fd-page-header-button` — page-level title bar
- `fd-hero` — hero banner
- `fd-stripe` — full-width colored section wrapper
- `fd-drawer` — slide-out panel

**Content:**
- `fd-card` / `fd-card-group` — content cards
- `fd-tile` / `fd-tile-list` — tile grid
- `fd-event` / `fd-event-list` — event listings
- `fd-badge` / `fd-badge-group` — status badges
- `fd-chip` / `fd-chip-group` — filter chips
- `fd-alert` — inline alerts
- `fd-message` — status messages
- `fd-pagination` — page navigation
- `fd-icon` — icon renderer
- `fd-visual` — decorative visuals
- `fd-link` — styled link
- `fd-page-feedback` — "was this helpful?" widget

**Form controls:**
- `fd-button` / `fd-button-group` / `fd-split-button`
- `fd-input` / `fd-textarea` / `fd-slider` / `fd-file-input`
- `fd-checkbox` / `fd-checkbox-group`
- `fd-radio` / `fd-radio-group`
- `fd-selector` — dropdown select
- `fd-field` — form field wrapper
- `fd-label` — form label
- `fd-menu` / `fd-menu-item` — dropdown menu
- `fd-header-search` — header search input

**Drupal-specific (already built into the components package):**
- `@fdic-ds/components/fd-global-header-drupal` exports:
  - `createFdGlobalHeaderNavigationFromDrupal(items)` — maps Drupal menu link
    arrays (`{ title, url, current, below[], description, keywords,
    overviewTitle, overviewUrl, ariaLabel }`) to the header's navigation model
  - `createFdGlobalHeaderContentFromDrupal(source)` — full content builder
    including search config

**Registration:**
- `@fdic-ds/components/register-all` — registers all custom elements at once
- `@fdic-ds/components/register/fd-{name}` — individual registration per component

## What to build

### 1. package.json
```json
{
  "name": "fdic-drupal-theme",
  "private": true,
  "dependencies": {
    "@fdic-ds/components": "^0.1.0",
    "@fdic-ds/tokens": "^0.1.0"
  }
}
```

Include an `.npmrc` pointing to GitHub Packages for the `@fdic-ds` scope:
```
@fdic-ds:registry=https://npm.pkg.github.com
```

### 2. fdic.info.yml
Drupal 10+ theme definition. Base theme: `stable9` (or `false` for standalone).
Define regions: header, content, sidebar_first, sidebar_second, footer,
page_top, page_bottom, breadcrumb, highlighted.

### 3. fdic.libraries.yml
Declare libraries that load the DS assets from `node_modules/@fdic-ds/`:

- `fdic/tokens` — loads `semantic.css` (which imports `interaction.css`)
- `fdic/components` — loads `register-all.js` as ES module; depends on `fdic/tokens`
- `fdic/global-header` — loads `fd-global-header-drupal.js` as ES module; depends
  on `fdic/components`
- `fdic/theme` — loads the theme's own CSS overrides (Drupal admin bar spacing,
  region layout, etc.)

Attach `fdic/tokens`, `fdic/components`, and `fdic/theme` globally via
`fdic.info.yml` `libraries:` key.

### 4. Twig templates

Create these template overrides in `templates/`:

**`templates/layout/html.html.twig`**
- Standard Drupal html template
- Include `lang` attribute on `<html>`

**`templates/layout/page.html.twig`**
- Use `<fd-global-header>` in the header region
- Render native Drupal footer blocks until a footer adapter is available
- Main content wrapped in `<main>` with `id="main-content"`
- Sidebar regions when populated

**`templates/layout/region--header.html.twig`**
- Renders the global header component
- Attaches `fdic/global-header` library

**`templates/layout/region--footer.html.twig`**
- Renders Drupal footer region content in a native `<footer>`

**`templates/layout/region--content.html.twig`**
- Wraps content in a container with appropriate DS spacing tokens

**`templates/content/node.html.twig`**
- Wraps node content in `.prose` class for content types that need prose styling
- Uses `<fd-page-header>` for the node title

**`templates/misc/status-messages.html.twig`**
- Maps Drupal status message types to `<fd-alert>` variants:
  - `status` → `success`
  - `warning` → `warning`
  - `error` → `error`
  - default → `info`

**`templates/navigation/pager.html.twig`**
- Replace default pager with `<fd-pagination>`

**`templates/form/input.html.twig`**
- Wrap/replace with `<fd-input>`

**`templates/form/textarea.html.twig`**
- Wrap/replace with `<fd-textarea>`

**`templates/form/select.html.twig`**
- Wrap/replace with `<fd-selector>`

**`templates/form/form-element.html.twig`**
- Use `<fd-field>` wrapper with `<fd-label>`

**`templates/form/form-element--checkbox.html.twig`**
- Use `<fd-checkbox>`

**`templates/form/form-element--radio.html.twig`**
- Use `<fd-radio>`

### 5. fdic.theme (PHP)
Theme preprocess functions:

- `fdic_preprocess_page()` — Pass main menu tree to the page template as a
  JSON-serializable array matching the `DrupalMenuLinkLike` interface. Use
  `\Drupal::menuTree()` to load and serialize the menu.
- `fdic_preprocess_html()` — Add `no-js` / `js` class toggle pattern.
- `fdic_page_attachments_alter()` — Remove default Drupal meta viewport if
  the theme provides its own.

### 6. Theme CSS (`css/theme.css`)
Minimal overrides:
- Drupal admin toolbar offset: `body.toolbar-fixed { padding-top: 79px; }`
- Region layout using CSS custom properties from the DS token system
- Print styles inheriting from DS tokens
- `.prose` class application rules for content regions

### 7. JS integration (`js/global-header-init.js`)
Small ES module script that:
- Reads the Drupal menu data from a `<script type="application/json">` block
  in the header region (populated by the preprocess function)
- Calls `createFdGlobalHeaderContentFromDrupal()` from
  `@fdic-ds/components/fd-global-header-drupal`
- Passes the result to the `<fd-global-header>` element's `.content` property

### 8. README.md
Brief setup instructions:
- Prerequisites: Drupal 10.2+, Node 18+, npm
- Install: `npm install` then `drush cr`
- How Drupal libraries reference node_modules assets
- How to update the design system version

## Architecture principles
- The theme CONSUMES the DS packages — it never copies or vendors built files
- All component JS loads as ES modules (`type: module` in libraries.yml)
- Tokens are loaded as plain CSS — no build step needed for the theme itself
- The Drupal adapter (`fd-global-header-drupal`) ships with `@fdic-ds/components`,
  not in this theme
- Twig templates use `<fd-*>` custom elements directly — no React/Vue bridge
- Form element templates progressively enhance — if JS fails to load,
  native HTML form elements still work

## File tree to create
```
fdic-drupal-theme/
├── .npmrc
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

Do NOT install npm packages (the GitHub Packages registry requires auth).
Just create all the files with correct content. After creating everything,
run `git status` to show what was created.
