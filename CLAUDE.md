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
2. If DS packages changed:
   - `cd /Users/jlamb/Projects/fdic-design-system && npm run build:components`
   - `cd /Users/jlamb/Projects/fdic-drupal-theme && npm install`
3. Clear Drupal cache: `ddev drush cr`
4. Verify Drupal integration: `bash scripts/verify-ddev.sh`
5. Re-export if needed: `bash scripts/export-static.sh`

The export script crawls the live Drupal site and writes HTML + assets to `public/`. Do not copy or patch DS build outputs into this repository manually; the export should consume the installed package files already served by Drupal.

## Design System (`fdic-design-system`)

The companion design system lives at `/Users/jlamb/Projects/fdic-design-system`.

- The **DS component implementation** (HTML, CSS, JS) is the source of truth — not Figma, not Storybook stories. Only reference Figma or Storybook when specifically directed.
- All component defaults (sizes, colors, spacing, typography, interactions) are owned by the DS.
- If a change is needed, contribute it to the DS component source — do not override from the theme.
- Layout tokens (`--ds-layout-*`) define the canonical page dimensions. DS components like the global header consume these directly.
- After modifying DS source, rebuild with `npm run build:components` and refresh the Drupal theme install with `npm install`.
