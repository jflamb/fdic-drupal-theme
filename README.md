# FDIC Drupal Theme

Drupal 10+ theme that consumes the FDIC Design System directly from the sibling `fdic-design-system` workspace and serves the installed package artifacts from `node_modules/@jflamb/`.

## Prerequisites

- Drupal 10.2+
- Node 18+
- sibling checkout of `/Users/jlamb/Projects/fdic-design-system`
- Drush

## Install

```sh
npm install
drush cr
```

Install dependencies with the committed `package-lock.json` so local checks and CI use the same package graph.

The theme does not require bundling or a postinstall asset copy. Drupal libraries point directly at installed package files under `node_modules/@jflamb/`:

- `node_modules/@jflamb/fdic-ds-tokens/styles.css` loads the canonical token runtime.
- `node_modules/@jflamb/fdic-ds-components/dist/register/register-all.js` registers the FDIC web components as ES modules.
- `node_modules/@jflamb/fdic-ds-components/dist/fd-global-header-drupal.js` remains available for Drupal menu adapter experiments.

The theme emits a small import map so local glue modules can use the package export-style specifiers `@jflamb/fdic-ds-components` and `@jflamb/fdic-ds-components/fd-global-header-drupal` while Drupal still serves the installed files directly from the theme assets.

See `docs/_theming.md` for deployment requirements around installed npm assets.

The header region renders `<fd-global-header>` and initializes it with Drupal-shaped navigation data through `js/global-header-init.js`. The GitHub Pages snapshot therefore exercises the same Design System global header component as the DDEV-rendered Drupal site.

The pager template passes `current-page`, `total-pages`, and `href-template` to `<fd-pagination>`. Drupal's pager labels are 1-indexed, but the `?page=` query value is 0-indexed; `js/pagination-init.js` adapts the component's 1-indexed page requests back to Drupal URLs. A `<noscript>` fallback keeps plain Drupal pager links available when JavaScript is disabled.

Form templates keep native Drupal controls and labels as fallbacks for form-associated custom elements. `js/form-fallback-init.js` disables those fallbacks only after the matching FDIC custom element is defined, so checkbox, radio, text input, textarea, and select controls remain submittable when JavaScript is disabled or component registration fails. Select elements with optgroups intentionally stay native until the design system exposes a compatible grouped option API.

The page template renders `<fd-page-feedback>` and `<fd-global-footer>` so the DDEV-exported GitHub Pages snapshot has complete Design System global chrome even when no footer blocks are placed. Footer utility and social links are initialized from static JSON by `js/design-system-init.js`.

The header search defaults are stored in `config/install/fdic.settings.yml` and read through theme settings:

- `header_search_action`
- `header_search_param_name`
- `header_search_placeholder`

## Tokens

The npm token package is canonical for this theme. Use the FDIC public tokens exported by `@jflamb/fdic-ds-tokens/styles.css`, especially the shared `--fdic-color-*`, `--fdic-spacing-*`, `--fdic-layout-*`, `--fdic-font-*`, and focus-ring tokens that the design system components already consume.

`css/theme.css` is intentionally limited to Drupal shell layout, prose/table wrappers, fallback visibility, and FDICnet-specific decorative exceptions. Reusable page composition now comes from the documented `fdic-composition-*` classes shipped in `@jflamb/fdic-ds-components/styles.css`. The theme should not introduce token aliases or restyle FDIC components internally.

## Theme Base

This theme uses `base theme: false` so Drupal does not add Stable9 compatibility markup or assets. Common Drupal output is handled by local Twig overrides and progressively enhanced with FDIC Design System custom elements.

The theme also disables common System/Stable9 compatibility CSS libraries that are replaced by local templates and Design System components. Keep functional Drupal core JavaScript libraries enabled unless a replacement behavior is implemented locally.

## Dependency Source

Phase 1 uses sibling workspace dependencies:

- `@jflamb/fdic-ds-components` → `file:../fdic-design-system/packages/components`
- `@jflamb/fdic-ds-tokens` → `file:../fdic-design-system/packages/tokens`

That removes the vendored tarball flow and keeps the Drupal theme aligned with the design system's published package surface. For local verification, keep both repositories as siblings under `/Users/jlamb/Projects`.

## Theme Services

The menu serializer currently lives in `fdic.theme` and uses Drupal static service access, which is normal for lightweight theme hooks. If the serialization logic grows or needs unit tests, move it into an injectable theme service and keep the preprocess hook as a thin caller.

## Local Development (DDEV)

A DDEV configuration and bootstrap script create a disposable Drupal site for testing the theme without an existing Drupal project.

DDEV is the canonical Drupal integration environment for this theme. Static output is only a rendered preview of that DDEV site; it is not a replacement for Drupal bootstrap and verification.

### Prerequisites

- Docker Desktop (or OrbStack / Colima)
- [DDEV](https://ddev.readthedocs.io/en/stable/) (`brew install ddev/ddev/ddev`)
- Node 18+ and npm
- sibling checkout of `/Users/jlamb/Projects/fdic-design-system`

### Quick start

```sh
scripts/bootstrap.sh
```

This creates a `drupal/` directory (gitignored) containing a Drupal 10 site with the FDIC theme active, essential blocks placed, and sample content for exercising the pager, forms, and status messages. The theme files are linked into the Drupal site — edits to theme files are immediately live after `ddev drush cr`.

Login at the printed URL with `admin` / `admin`.

### Day-to-day commands

| Command | What it does |
|---------|-------------|
| `ddev launch` | Open the site in your browser |
| `ddev drush cr` | Rebuild cache after CSS / Twig / PHP changes |
| `ddev stop` | Stop containers (preserves data) |
| `ddev start` | Restart stopped containers |
| `scripts/teardown.sh` | Destroy DDEV project + `drupal/` directory |
| `scripts/bootstrap.sh` | Recreate from scratch |
| `scripts/bootstrap.sh --quick` | Bootstrap without sample content |
| `scripts/verify-ddev.sh` | Verify Drupal bootstrap, active theme, bounded linked theme directory, homepage, and FDIC Design System assets |
| `scripts/export-static.sh` | Export the verified DDEV site into `public/` as a static rendered preview |

### How it works

The theme repo is the DDEV project. `.ddev/config.yaml` points `docroot` at `drupal/web` and `composer_root` at `drupal/`. The bootstrap script:

1. Starts DDEV containers
2. Runs `ddev composer create drupal/recommended-project:^10` inside `drupal/`
3. Installs Drush (`drush/drush`) if not already present
4. Creates `drupal/web/themes/custom/fdic` and links the theme's Drupal files and asset directories into it
5. Runs `npm install` for the theme's sibling `@jflamb` design-system dependencies
6. Installs Drupal with the standard profile
7. Enables and sets the FDIC theme as default
8. Places blocks in the theme's regions (header, content, breadcrumb, highlighted)
9. Creates a curated homepage, basic pages, and enough articles to exercise the listing pager
10. Sets the curated homepage node as Drupal's front page

`scripts/bootstrap.sh --quick` keeps the same Drupal install and theme checks but skips seeded sample content. Use the full `scripts/bootstrap.sh` when producing a preview snapshot so the rendered site has meaningful content.

### Linked theme directory

The generated `drupal/web/themes/custom/fdic` directory is not a symlink to the repository root. Instead, the bootstrap script recreates it as a small directory containing relative symlinks to the Drupal-owned source files plus a staged runtime copy of the browser-served npm packages Drupal needs:

- `config/`
- `css/`
- `js/`
- `templates/`
- `fdic.breakpoints.yml`
- `fdic.info.yml`
- `fdic.libraries.yml`
- `fdic.theme`
- `logo.svg`

The staged runtime currently includes `@jflamb/fdic-ds-components`, `@jflamb/fdic-ds-tokens`, `lit`, `lit-html`, `lit-element`, `@lit/reactive-element`, and `@xmldom/xmldom`.

This preserves live local edits while preventing Drupal extension discovery from recursing through the generated `drupal/` application and while keeping Drupal from serving cross-repository symlinks out of `node_modules/`.

### Static Snapshot

After the DDEV site has been bootstrapped and verified, export a static preview:

```sh
scripts/export-static.sh
```

The exporter writes a clean `public/` directory (gitignored), snapshots `/`, the Drupal article listing, a pager page, and the seeded node detail pages, then downloads the CSS, JavaScript, images, and `node_modules` assets referenced by those pages. It is deterministic and safe to rerun.

The snapshot is rendered HTML only. It is useful for GitHub Pages previews, but it is not a live Drupal site. Forms, search, authenticated routes, and other dynamic Drupal behavior are not expected to submit or mutate state from the static output.

### CI / remote builds

The same bootstrap and verification scripts work locally and in CI:

```sh
scripts/bootstrap.sh
scripts/verify-ddev.sh
scripts/export-static.sh
```

The committed workflow at `.github/workflows/theme-ci.yml` runs npm checks, bootstraps DDEV, verifies the Drupal integration, and, on pushes to `main`, exports `public/` as a GitHub Pages artifact and deploys it with GitHub Actions. Pull requests run the checks without deploying.

Requirements:

- Docker available on the runner (GitHub-hosted Ubuntu runners include it)
- DDEV installed (see install commands above or the [DDEV docs](https://ddev.readthedocs.io/en/stable/users/install/))
- the `fdic-design-system` workspace available next to this repo, or an equivalent replacement for the local `file:` dependencies before `npm ci` or the bootstrap runs
- GitHub Pages configured with source set to **GitHub Actions** before the deploy job can publish the static snapshot

## Design System Updates

Update the sibling `@jflamb/fdic-ds-components` and `@jflamb/fdic-ds-tokens` dependencies in `package.json`, then run:

```sh
npm install
drush cr
```

The theme consumes the package surface directly. Do not copy or vendor built design system files into this repository.
