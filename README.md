# FDIC Drupal Theme

Drupal 10+ theme that consumes the FDIC Design System npm packages directly from `node_modules/@fdic-ds/`.

## Prerequisites

- Drupal 10.2+
- Node 18+
- npm with GitHub Packages access for the `@fdic-ds` scope
- Drush

## Install

```sh
npm install
drush cr
```

This scaffold does not include `package-lock.json` because dependency installation was intentionally skipped. Commit the generated lockfile after the first authenticated install.

The theme does not require bundling or a postinstall asset copy. Drupal libraries point directly at published files under `node_modules/@fdic-ds/`:

- `node_modules/@fdic-ds/tokens/semantic.css` loads the token CSS.
- `node_modules/@fdic-ds/components/dist/register/register-all.js` registers the FDIC web components as ES modules.
- `node_modules/@fdic-ds/components/dist/fd-global-header-drupal.js` provides the Drupal menu adapter for the global header.

The theme emits a small import map for `@fdic-ds/components/fd-global-header-drupal` so local glue modules can use the package export-style specifier while Drupal still serves the published file from the installed theme assets.

See `docs/_theming.md` for deployment requirements around installed npm assets.

The `fdic/global-header` library is attached by `templates/layout/region--header.html.twig`. If the header region is not rendered because no header blocks are placed, the global-header Drupal adapter script is not loaded.

The pager template passes `current-page`, `total-pages`, and `href-template` to `<fd-pagination>`. Drupal's pager labels are 1-indexed, but the `?page=` query value is 0-indexed; `js/pagination-init.js` adapts the component's 1-indexed page requests back to Drupal URLs. A `<noscript>` fallback keeps plain Drupal pager links available when JavaScript is disabled.

Form templates keep native Drupal controls and labels as fallbacks for form-associated custom elements. `js/form-fallback-init.js` disables those fallbacks only after the matching FDIC custom element is defined, so checkbox, radio, text input, textarea, and select controls remain submittable when JavaScript is disabled or component registration fails. Select elements with optgroups intentionally stay native until the design system exposes a compatible grouped option API.

The footer template renders Drupal footer blocks as native footer content until the design system exposes a Drupal footer adapter or a compatible structured data API.

The header search defaults are stored in `config/install/fdic.settings.yml` and read through theme settings:

- `header_search_action`
- `header_search_param_name`
- `header_search_placeholder`

## Tokens

The npm token package is canonical for this theme. Use the `--ds-*` custom properties published by `@fdic-ds/tokens`, including semantic colors such as `--ds-color-{role}-{variant}`, focus tokens such as `--ds-focus-ring-*`, and motion tokens such as `--ds-motion-*`.

`css/theme.css` defines a small compatibility alias set for common `--fdic-*` names used in prose and older docs. New theme CSS should still prefer `--ds-*` tokens. If more legacy token names are needed, add them to that alias block rather than mixing raw fallback values into component CSS.

## Theme Base

This theme uses `base theme: false` so Drupal does not add Stable9 compatibility markup or assets. Common Drupal output is handled by local Twig overrides and progressively enhanced with FDIC Design System custom elements.

The theme also disables common System/Stable9 compatibility CSS libraries that are replaced by local templates and Design System components. Keep functional Drupal core JavaScript libraries enabled unless a replacement behavior is implemented locally.

## GitHub Packages

Authenticate npm to GitHub Packages before running `npm install`. GitHub documents the required npm token setup at [Working with the npm registry](https://docs.github.com/packages/working-with-a-github-packages-registry/working-with-the-npm-registry).

## Theme Services

The menu serializer currently lives in `fdic.theme` and uses Drupal static service access, which is normal for lightweight theme hooks. If the serialization logic grows or needs unit tests, move it into an injectable theme service and keep the preprocess hook as a thin caller.

## Design System Updates

Update `@fdic-ds/components` and `@fdic-ds/tokens` in `package.json`, then run:

```sh
npm install
drush cr
```

The theme consumes the published packages only. Do not copy or vendor built design system files into this repository.
