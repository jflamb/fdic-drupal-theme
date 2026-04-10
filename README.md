# FDIC Drupal Theme

Drupal 10+ theme that consumes the FDIC Design System npm packages directly from `node_modules/@fdic-ds/`.

## Prerequisites

- Drupal 10.2+
- Node 18+
- npm with GitHub Packages access for the published `@jflamb` FDIC Design System packages
- Drush

## Install

```sh
npm install
drush cr
```

Install dependencies with the committed `package-lock.json` so local checks and CI use the same package graph.

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

The theme keeps `@fdic-ds/*` dependency names as npm aliases so Drupal library paths remain stable under `node_modules/@fdic-ds/`. The published packages currently live under the GitHub Packages owner scope `@jflamb`.

## Theme Services

The menu serializer currently lives in `fdic.theme` and uses Drupal static service access, which is normal for lightweight theme hooks. If the serialization logic grows or needs unit tests, move it into an injectable theme service and keep the preprocess hook as a thin caller.

## Local Development (DDEV)

A DDEV configuration and bootstrap script create a disposable Drupal site for testing the theme without an existing Drupal project.

DDEV is the canonical Drupal integration environment for this theme. Static output is only a rendered preview of that DDEV site; it is not a replacement for Drupal bootstrap and verification.

### Prerequisites

- Docker Desktop (or OrbStack / Colima)
- [DDEV](https://ddev.readthedocs.io/en/stable/) (`brew install ddev/ddev/ddev`)
- Node 18+ and npm with GitHub Packages auth for the published `@jflamb` packages

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
5. Runs `npm install` for the theme's `@fdic-ds` dependencies
6. Installs Drupal with the standard profile
7. Enables and sets the FDIC theme as default
8. Places blocks in the theme's regions (header, content, breadcrumb, highlighted)
9. Creates a curated homepage, basic pages, and enough articles to exercise the listing pager
10. Sets the curated homepage node as Drupal's front page

`scripts/bootstrap.sh --quick` keeps the same Drupal install and theme checks but skips seeded sample content. Use the full `scripts/bootstrap.sh` when producing a preview snapshot so the rendered site has meaningful content.

### Linked theme directory

The generated `drupal/web/themes/custom/fdic` directory is not a symlink to the repository root. Instead, the bootstrap script recreates it as a small directory containing relative symlinks to the files and directories Drupal needs:

- `config/`
- `css/`
- `js/`
- `templates/`
- `node_modules/`
- `fdic.breakpoints.yml`
- `fdic.info.yml`
- `fdic.libraries.yml`
- `fdic.theme`
- `logo.svg`

This preserves live local edits while preventing Drupal extension discovery from recursing through the generated `drupal/` application.

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

A minimal GitHub Actions job looks like this:

```yaml
jobs:
  theme-test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: actions/setup-node@v4
        with:
          node-version: 20

      # Write GitHub Packages auth for the @jflamb-backed npm aliases.
      - env:
          FDIC_DS_NPM_TOKEN: ${{ secrets.FDIC_DS_NPM_TOKEN }}
          GITHUB_TOKEN: ${{ github.token }}
        run: |
          token="${FDIC_DS_NPM_TOKEN:-$GITHUB_TOKEN}"
          printf '//npm.pkg.github.com/:_authToken=%s\n' "$token" >> .npmrc

      # Install DDEV (Linux).
      - run: curl -fsSL https://pkg.ddev.com/apt/gpg.key | gpg --dearmor | sudo tee /usr/share/keyrings/ddev.gpg > /dev/null
      - run: echo "deb [signed-by=/usr/share/keyrings/ddev.gpg] https://pkg.ddev.com/apt/ * *" | sudo tee /etc/apt/sources.list.d/ddev.list
      - run: sudo apt-get update && sudo apt-get install -y ddev

      - run: npm ci
      - run: npm test
      - run: scripts/bootstrap.sh
      - run: scripts/verify-ddev.sh
      - run: scripts/export-static.sh
```

Requirements:

- Docker available on the runner (GitHub-hosted Ubuntu runners include it)
- DDEV installed (see install commands above or the [DDEV docs](https://ddev.readthedocs.io/en/stable/users/install/))
- `packages: read` permission for the workflow's `GITHUB_TOKEN`, or a repository secret (`FDIC_DS_NPM_TOKEN`) with a GitHub token that has `read:packages` scope for the `@jflamb` packages, written into `.npmrc` before `npm ci` or the bootstrap runs
- GitHub Pages configured with source set to **GitHub Actions** before the deploy job can publish the static snapshot

## Design System Updates

Update the aliased `@fdic-ds/components` and `@fdic-ds/tokens` dependencies in `package.json`, then run:

```sh
npm install
drush cr
```

The theme consumes the published packages only. Do not copy or vendor built design system files into this repository.
