# FDIC Drupal Theme — Agent Guide

## Project Purpose

This is a Drupal theme that integrates `fdic-design-system` for use in Drupal CMS websites. It is a thin integration layer — it wires DS components into Drupal's template system but **never overrides their behavior or styling**.

## Core Principles

1. **`fdic-design-system` is the source of truth** for all component behavior, styling, layout, and visual defaults. This theme consumes DS components as-is.
2. **Never override the design system** from this project — no custom property overrides, no external CSS that reaches into DS components. If a component needs to change, contribute the change to `fdic-design-system`.
3. **Drupal-specific styles are acceptable** only when they use DS primitives (tokens, variables) and only for Drupal-specific concerns (e.g., Drupal block/region layout glue). Layout and component styling belong in the DS.
4. **`public/` is a build artifact** — never manually patch files there. Always change source files and re-export.

## Static Site Workflow

When making changes:

1. Make changes to source files (CSS, Twig templates, DS components)
2. If DS components changed:
   - `cd /Users/jlamb/Projects/fdic-design-system && npm run build:components`
   - `cp -r packages/components/dist/ /Users/jlamb/Projects/fdic-drupal-theme/node_modules/@fdic-ds/components/dist/`
3. Clear Drupal cache: `ddev drush cr`
4. Re-export: `bash scripts/export-static.sh`
5. Copy DS dist into the export: `cp -r node_modules/@fdic-ds/components/dist/ public/themes/custom/fdic/node_modules/@fdic-ds/components/dist/`

The export script crawls the live Drupal site and writes HTML + assets to `public/`. The DS component chunks in `public/themes/custom/fdic/node_modules/` must match the build vendored into `node_modules/` — the export does not automatically copy dynamically-imported JS module chunks.

## Design System (`fdic-design-system`)

The companion design system lives at `/Users/jlamb/Projects/fdic-design-system`.

- The **DS component implementation** (HTML, CSS, JS) is the source of truth — not Figma, not Storybook stories. Only reference Figma or Storybook when specifically directed.
- All component defaults (sizes, colors, spacing, typography, interactions) are owned by the DS.
- If a change is needed, contribute it to the DS component source — do not override from the theme.
- Layout tokens (`--ds-layout-*`) define the canonical page dimensions. DS components like the global header consume these directly.
- After modifying DS source, rebuild with `npm run build:components` and vendor into the Drupal theme's `node_modules/`.
