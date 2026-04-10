# FDIC Drupal Theme Notes

## Asset Loading

This theme intentionally consumes FDIC Design System assets from `node_modules/@fdic-ds/`.

Deployment must run an authenticated `npm install` before Drupal serves the theme. The installed `node_modules/@fdic-ds` package files must be present wherever Drupal serves theme libraries.

The theme does not copy built design system files into the repository or into a generated vendor directory.

## Versioning

`package.json` allows compatible `0.1.x` DS package updates. After the first authenticated install, commit `package-lock.json` to lock transitive dependencies as well.

## Component Contracts

Form templates use FDIC Design System form-associated custom elements that
participate in native form submission via `ElementInternals`. Drupal form
attributes (name, value, required, disabled, etc.) are passed directly to the
DS host element, with no slotted native controls.

When upgrading `@fdic-ds/components`, verify these host-attribute contracts:

- `fd-input` receives all `<input>` attributes on the host
- `fd-textarea` receives all `<textarea>` attributes; value as text content
- `fd-selector` receives `<select>` attributes; children are `<fd-option>` elements
- `fd-checkbox` / `fd-radio` receive `label`, `name`, `value`, `checked`, `disabled` on the host
- `fd-field` auto-wires direct-child `fd-label` to its form control
- `fd-alert` uses `type`, `title`, `live` attributes
- `fd-pagination` uses `current-page`, `total-pages`, `href-template` attributes
- `fd-page-header` uses `heading` attribute for the page title
- `fd-message` uses `state` attribute, not `variant`
- `fd-button` uses text content as the button label
- `fd-global-footer` uses structured properties and does not render Drupal block content through a default slot

## Breakpoints

The Drupal breakpoint definitions mirror the CSS layout thresholds:

- Mobile: below `48rem`
- Tablet: `48rem` through below `64rem`
- Desktop: `64rem` and up

The sidebar layout uses both thresholds: three-column desktop layouts become two-column tablet layouts, then collapse to one column on mobile.
