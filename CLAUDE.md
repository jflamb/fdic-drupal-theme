# FDIC Drupal Theme — Agent Guide

## Static Site Preview

The `public/` directory contains a statically exported snapshot of the Drupal site used for preview and CI.

**Never manually patch files in `public/`.** All changes must be made in the source files (`css/theme.css`, templates in `templates/`, DS components in `fdic-design-system`) and then re-exported:

1. Make changes to source files (CSS, Twig templates, DS components)
2. If DS components changed, rebuild and vendor: `cd ../fdic-design-system && npm run build:components` then `cp -r packages/components/dist/ ../fdic-drupal-theme/node_modules/@fdic-ds/components/dist/`
3. Clear Drupal cache: `ddev drush cr`
4. Re-export: `bash scripts/export-static.sh`
5. Copy DS dist into the export: `cp -r node_modules/@fdic-ds/components/dist/ public/themes/custom/fdic/node_modules/@fdic-ds/components/dist/`

The export script crawls the live Drupal site and writes HTML + assets to `public/`. The DS component chunks in `public/themes/custom/fdic/node_modules/` must match the build vendored into `node_modules/` — the export does not automatically copy dynamically-imported JS module chunks.

## Design System (`fdic-design-system`)

The companion design system lives at `/Users/jlamb/Projects/fdic-design-system`. When making DS component changes:

- Customize DS components through their **custom property API** (e.g., `--fd-event-date-size`, `--fd-global-header-shell-max-width`). Do not override DS component internals from the theme CSS.
- If the custom property API doesn't expose what you need, add the property to the DS component source rather than working around it in the theme.
- After modifying DS source, rebuild with `npm run build:components` and vendor into the Drupal theme's `node_modules/`.
