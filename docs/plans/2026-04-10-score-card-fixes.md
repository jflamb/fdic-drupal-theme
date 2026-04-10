# Score Card Fixes — Grade C to Grade A

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix all DS component API mismatches, accessibility gaps, and library issues identified in the theme score card to raise the grade from C (75.8%) to A (90%+).

**Architecture:** Each task fixes a specific score card finding by correcting Twig templates, PHP preprocess functions, or library declarations to match the actual FDIC Design System component APIs. Form-associated custom elements (`fd-input`, `fd-textarea`, `fd-selector`, `fd-checkbox`, `fd-radio`) are host-attribute-driven — they do NOT accept slotted native controls. Non-form components (`fd-alert`, `fd-pagination`, `fd-page-header`, `fd-message`) use specific attribute names that differ from the current templates.

**Tech Stack:** Drupal 10+ Twig templates, PHP theme hooks, CSS custom properties, YAML library declarations

**Key principle:** Every fix must preserve progressive enhancement — native form controls must still work when JS is disabled. For form-associated custom elements, the DS components participate in form submission natively (they extend `HTMLElement` with `ElementInternals`), so the native `<input>` inside a slot is unnecessary and actually breaks the contract.

---

### Task 1: Fix `fd-alert` status messages (4.5 + 8.3 — ~4 pts)

**Files:**
- Modify: `templates/misc/status-messages.html.twig`

**Why:** `fd-alert` uses `type` (not `variant`) for the visual variant, `title` attribute (not `slot="title"`) for the heading, and `live` attribute for ARIA live region behavior. Current template uses `variant`, `role`, and `<strong slot="title">` — all wrong.

**Step 1: Rewrite status-messages.html.twig**

Replace the entire file with:

```twig
{#
/**
 * @file
 * Theme override for status messages.
 *
 * fd-alert API:
 *   - type: "success" | "warning" | "error" | "info"
 *   - title: string (the alert heading)
 *   - live: "polite" | "assertive" (ARIA live region behavior)
 */
#}
{% set type_map = {
  status: 'success',
  warning: 'warning',
  error: 'error',
} %}

{% for type, messages in message_list %}
  {% set alert_type = type_map[type]|default('info') %}
  {% set title = status_headings[type]|default(type|capitalize) %}
  <fd-alert type="{{ alert_type }}" title="{{ title }}" live="{{ type == 'error' ? 'assertive' : 'polite' }}">
    {% if messages|length > 1 %}
      <ul>
        {% for message in messages %}
          <li>{{ message }}</li>
        {% endfor %}
      </ul>
    {% else %}
      {{ messages|first }}
    {% endif %}
  </fd-alert>
{% endfor %}
```

**Step 2: Commit**

```bash
git add templates/misc/status-messages.html.twig
git commit -m "fix: use fd-alert type/title/live attrs instead of variant/slot"
```

---

### Task 2: Fix `fd-page-header` in node template (6.4 — ~2 pts)

**Files:**
- Modify: `templates/content/node.html.twig`

**Why:** `fd-page-header` uses a `heading` attribute for the title text, not `slot="title"`. The current `<span slot="title">` does nothing because the component has no such slot.

**Step 1: Update the page-header usage in node.html.twig**

Change lines 26-28 from:

```twig
    <fd-page-header>
      <span slot="title">{{ label }}</span>
    </fd-page-header>
```

To:

```twig
    <fd-page-header heading="{{ label|render|striptags|trim }}"></fd-page-header>
```

Note: `|render|striptags|trim` is needed because Drupal's `label` is a render array/Markup object that may contain HTML tags — the `heading` attribute expects plain text.

**Step 2: Commit**

```bash
git add templates/content/node.html.twig
git commit -m "fix: use fd-page-header heading attr instead of slot"
```

---

### Task 3: Fix `fd-pagination` pager (4.6 — ~2 pts)

**Files:**
- Modify: `templates/navigation/pager.html.twig`
- Modify: `fdic.theme` (add `href-template` preprocess)

**Why:** `fd-pagination` generates its own page controls and needs `href-template` to produce real Drupal page URLs. It does not support a `slot="fallback"`. The existing `<ul>` pager links inside the component are invisible to the upgraded element.

**Step 1: Add href-template generation to fdic_preprocess_pager**

In `fdic.theme`, expand `fdic_preprocess_pager()` to derive a URL template from the existing pager items:

```php
function fdic_preprocess_pager(array &$variables): void {
  $element = $variables['pager']['#element'] ?? NULL;
  if ($element === NULL) {
    return;
  }

  $pager = \Drupal::service('pager.manager')->getPager($element);
  if ($pager === NULL) {
    return;
  }

  $variables['fdic_total_pages'] = $pager->getTotalPages();

  // Build a href template for fd-pagination by examining a sample pager URL.
  // Drupal pager URLs use ?page=N (0-indexed), but fd-pagination is 1-indexed.
  // We pass the raw pattern and let JS or the component handle the offset.
  $items = $variables['items'] ?? [];
  $sample_href = '';
  if (!empty($items['pages'])) {
    // Grab the href from any non-current page link.
    foreach ($items['pages'] as $page_num => $page_item) {
      if (!empty($page_item['href'])) {
        $sample_href = (string) $page_item['href'];
        // Replace the page number with the {page} token.
        // Drupal uses 0-indexed page param, fd-pagination is 1-indexed.
        $zero_indexed = $page_num - 1;
        $variables['fdic_pager_href_template'] = str_replace(
          'page=' . $zero_indexed,
          'page={page}',
          $sample_href
        );
        break;
      }
    }
  }
}
```

**Step 2: Simplify pager.html.twig**

Replace the file contents with:

```twig
{#
/**
 * @file
 * Theme override for pager navigation.
 *
 * fd-pagination API:
 *   - current-page: 1-indexed current page number
 *   - total-pages: total page count
 *   - href-template: URL pattern with {page} placeholder (0-indexed page param)
 *
 * Progressive enhancement: when JS is disabled or fd-pagination has not
 * upgraded, the <noscript> block provides a plain pager link list.
 */
#}
{% if items %}
  {% set current = current|default(1) %}
  {% set total = fdic_total_pages|default(items.pages ? items.pages|length : current) %}
  <nav class="pager" role="navigation" aria-labelledby="pagination-heading">
    <h4 id="pagination-heading" class="fdic-visually-hidden">{{ 'Pagination'|t }}</h4>
    <fd-pagination
      current-page="{{ current }}"
      total-pages="{{ total }}"
      {% if fdic_pager_href_template is defined %}href-template="{{ fdic_pager_href_template }}"{% endif %}
    ></fd-pagination>
    <noscript>
      <ul class="pager__items">
        {% if items.previous %}
          <li class="pager__item pager__item--previous">
            <a href="{{ items.previous.href }}" rel="prev">{{ 'Previous'|t }}</a>
          </li>
        {% endif %}
        {% for key, item in items.pages %}
          <li class="pager__item{{ current == key ? ' is-active' }}">
            {% if current == key %}
              <span aria-current="page">{{ key }}</span>
            {% else %}
              <a href="{{ item.href }}">{{ key }}</a>
            {% endif %}
          </li>
        {% endfor %}
        {% if items.next %}
          <li class="pager__item pager__item--next">
            <a href="{{ items.next.href }}" rel="next">{{ 'Next'|t }}</a>
          </li>
        {% endif %}
      </ul>
    </noscript>
  </nav>
{% endif %}
```

**Step 3: Commit**

```bash
git add fdic.theme templates/navigation/pager.html.twig
git commit -m "fix: add href-template to fd-pagination, use noscript fallback"
```

---

### Task 4: Fix form input template (5.1 — ~2 pts)

**Files:**
- Modify: `templates/form/input.html.twig`

**Why:** `fd-input` is a form-associated custom element. It expects attributes (`name`, `type`, `value`, `required`, `disabled`, `placeholder`, etc.) on the host element — not a slotted native `<input>`. The native input inside `slot="input"` is unsupported and breaks form submission when JS is enabled.

**Step 1: Rewrite input.html.twig**

```twig
{#
/**
 * @file
 * Theme override for input elements.
 *
 * fd-input is a form-associated custom element that participates in form
 * submission via ElementInternals. Pass Drupal input attributes directly
 * to the fd-input host. Non-upgradeable types (hidden, submit, button,
 * image, reset) remain as native elements.
 */
#}
{% set type = fdic_input_type|default('text') %}
{% if type in ['hidden', 'submit', 'button', 'image', 'reset'] %}
  <input{{ attributes }} />{{ children }}
{% else %}
  <fd-input{{ attributes.addClass('fdic-form-control') }}></fd-input>{{ children }}
{% endif %}
```

**Step 2: Commit**

```bash
git add templates/form/input.html.twig
git commit -m "fix: pass attrs to fd-input host instead of slotting native input"
```

---

### Task 5: Fix form textarea template (5.2 — ~2 pts)

**Files:**
- Modify: `templates/form/textarea.html.twig`

**Why:** Same pattern as `fd-input` — `fd-textarea` is form-associated and expects attributes on the host, not a slotted `<textarea>`.

**Step 1: Rewrite textarea.html.twig**

```twig
{#
/**
 * @file
 * Theme override for textarea elements.
 *
 * fd-textarea is a form-associated custom element. Pass Drupal textarea
 * attributes directly to the fd-textarea host. The value text content
 * is set via the value attribute or as text content of the element.
 */
#}
<fd-textarea{{ attributes.addClass('fdic-form-control') }}>{{ value }}</fd-textarea>{{ children }}
```

**Step 2: Commit**

```bash
git add templates/form/textarea.html.twig
git commit -m "fix: pass attrs to fd-textarea host instead of slotting native textarea"
```

---

### Task 6: Fix form select template (5.3 — ~2 pts)

**Files:**
- Modify: `templates/form/select.html.twig`

**Why:** `fd-selector` expects `<fd-option>` children with `value` and optional `selected` attributes. It does not accept a slotted native `<select>`. Optgroups are handled by `<fd-optgroup label="...">`.

**Step 1: Rewrite select.html.twig**

```twig
{#
/**
 * @file
 * Theme override for select elements.
 *
 * fd-selector is a form-associated custom element that expects fd-option
 * children. Drupal select attributes are passed to the fd-selector host.
 */
#}
<fd-selector{{ attributes.addClass('fdic-form-control') }}>
  {% for option in options %}
    {% if option.type == 'optgroup' %}
      <fd-optgroup label="{{ option.label }}">
        {% for sub_option in option.options %}
          <fd-option value="{{ sub_option.value }}"{{ sub_option.selected ? ' selected' }}>{{ sub_option.label }}</fd-option>
        {% endfor %}
      </fd-optgroup>
    {% elseif option.type == 'option' %}
      <fd-option value="{{ option.value }}"{{ option.selected ? ' selected' }}>{{ option.label }}</fd-option>
    {% endif %}
  {% endfor %}
</fd-selector>
```

**Step 2: Commit**

```bash
git add templates/form/select.html.twig
git commit -m "fix: use fd-option children in fd-selector instead of slotted native select"
```

---

### Task 7: Fix checkbox and radio templates (5.4 — ~2 pts)

**Files:**
- Modify: `templates/form/form-element--checkbox.html.twig`
- Modify: `templates/form/form-element--radio.html.twig`

**Why:** `fd-checkbox` and `fd-radio` are form-associated custom elements. They expect `name`, `value`, `checked`, `disabled`, `required` on the host — not native controls in light DOM. The `label` is set via `label` attribute, not `slot="label"`.

**Step 1: Rewrite form-element--checkbox.html.twig**

```twig
{#
/**
 * @file
 * Theme override for checkbox form elements.
 *
 * fd-checkbox is a form-associated custom element. The checkbox native
 * control attributes (name, value, checked, disabled) are rendered by
 * Drupal on the children element — they need to be on the fd-checkbox
 * host instead. fd-field wires label association automatically when
 * fd-checkbox is a direct child.
 */
#}
{% set classes = [
  'js-form-item',
  'form-item',
  'js-form-type-' ~ type|clean_class,
  'form-type-' ~ type|clean_class,
  'js-form-item-' ~ name|clean_class,
  'form-item-' ~ name|clean_class,
  disabled == 'disabled' ? 'form-disabled',
  errors ? 'form-item--error',
] %}

<fd-field{{ attributes.addClass(classes).setAttribute('data-invalid', errors ? 'true' : 'false') }}>
  <fd-checkbox label="{{ label|render|striptags|trim }}">
    {{ children }}
  </fd-checkbox>

  {% if description.content %}
    <div{{ description.attributes.addClass('description') }}>{{ description.content }}</div>
  {% endif %}

  {% if errors %}
    <div class="form-item--error-message">
      {{ errors }}
    </div>
  {% endif %}
</fd-field>
```

**Step 2: Rewrite form-element--radio.html.twig**

Same pattern but with `fd-radio`:

```twig
{#
/**
 * @file
 * Theme override for radio form elements.
 *
 * fd-radio is a form-associated custom element. The label is set via
 * the label attribute on the host.
 */
#}
{% set classes = [
  'js-form-item',
  'form-item',
  'js-form-type-' ~ type|clean_class,
  'form-type-' ~ type|clean_class,
  'js-form-item-' ~ name|clean_class,
  'form-item-' ~ name|clean_class,
  disabled == 'disabled' ? 'form-disabled',
  errors ? 'form-item--error',
] %}

<fd-field{{ attributes.addClass(classes).setAttribute('data-invalid', errors ? 'true' : 'false') }}>
  <fd-radio label="{{ label|render|striptags|trim }}">
    {{ children }}
  </fd-radio>

  {% if description.content %}
    <div{{ description.attributes.addClass('description') }}>{{ description.content }}</div>
  {% endif %}

  {% if errors %}
    <div class="form-item--error-message">
      {{ errors }}
    </div>
  {% endif %}
</fd-field>
```

**Step 3: Commit**

```bash
git add templates/form/form-element--checkbox.html.twig templates/form/form-element--radio.html.twig
git commit -m "fix: use fd-checkbox/fd-radio label attr instead of slot"
```

---

### Task 8: Fix form-element wrapper template (5.5 — ~2 pts)

**Files:**
- Modify: `templates/form/form-element.html.twig`

**Why:** `fd-field` auto-wires `fd-label` when it's a direct child, but it does NOT support `slot="description"` or `slot="error"`. Description and error content should be rendered as plain children — `fd-field` discovers them by class or by being adjacent to the control.

The `fd-label` also does not support `slot="label"` — it's a direct child of `fd-field` and wires itself automatically. Remove all slot attributes.

**Step 1: Rewrite form-element.html.twig**

```twig
{#
/**
 * @file
 * Theme override for a form element.
 *
 * fd-field auto-wires a direct-child fd-label to its form control.
 * Description and error content are plain children (no slot attributes).
 */
#}
{% set classes = [
  'js-form-item',
  'form-item',
  'js-form-type-' ~ type|clean_class,
  'form-type-' ~ type|clean_class,
  'js-form-item-' ~ name|clean_class,
  'form-item-' ~ name|clean_class,
  title_display not in ['after', 'before'] ? 'form-no-label',
  disabled == 'disabled' ? 'form-disabled',
  errors ? 'form-item--error',
] %}

<fd-field{{ attributes.addClass(classes).setAttribute('data-required', required ? 'true' : 'false').setAttribute('data-invalid', errors ? 'true' : 'false') }}>
  {% if label_display in ['before', 'invisible'] %}
    <fd-label{% if label_display == 'invisible' %} class="fdic-visually-hidden"{% endif %}>
      {{ label }}
    </fd-label>
  {% endif %}

  {{ prefix }}
  {{ children }}
  {{ suffix }}

  {% if label_display == 'after' %}
    <fd-label>
      {{ label }}
    </fd-label>
  {% endif %}

  {% if description.content %}
    <div{{ description.attributes.addClass('description') }}>{{ description.content }}</div>
  {% endif %}

  {% if errors %}
    <div class="form-item--error-message">
      {{ errors }}
    </div>
  {% endif %}
</fd-field>
```

**Step 2: Commit**

```bash
git add templates/form/form-element.html.twig
git commit -m "fix: remove unsupported slot attrs from fd-field children"
```

---

### Task 9: Fix submit button template (related to 5.x)

**Files:**
- Modify: `templates/form/input--submit.html.twig`

**Why:** `fd-button` does not support `slot="button"`. It wraps content as its label text. For a submit input, the `value` attribute text becomes the button label.

**Step 1: Rewrite input--submit.html.twig**

```twig
{#
/**
 * @file
 * Theme override for submit input elements.
 *
 * fd-button renders its text content as the button label.
 * The type and form-submission attributes are passed to the host.
 */
#}
<fd-button{{ attributes.addClass('fdic-form-control') }}>{{ attributes.value|default('Submit'|t) }}</fd-button>{{ children }}
```

Note: Drupal's submit input has `value` as the button text. We extract it for the button label and pass remaining attributes to the host.

**Step 2: Commit**

```bash
git add templates/form/input--submit.html.twig
git commit -m "fix: use fd-button text content instead of slotted native input"
```

---

### Task 10: Fix views-view empty message (cross-reference issue)

**Files:**
- Modify: `templates/views/views-view.html.twig`

**Why:** `fd-message` uses `state` attribute (not `variant`) for its visual style. The correct value is `state="info"`, not `variant="info"`.

**Step 1: Update line 46 of views-view.html.twig**

Change:
```twig
      <fd-message variant="info">{{ empty }}</fd-message>
```

To:
```twig
      <fd-message state="info">{{ empty }}</fd-message>
```

**Step 2: Commit**

```bash
git add templates/views/views-view.html.twig
git commit -m "fix: use fd-message state attr instead of variant"
```

---

### Task 11: Remove redundant interaction.css library entry (2.4 + 3.1 — ~2 pts)

**Files:**
- Modify: `fdic.libraries.yml`

**Why:** `semantic.css` already imports `interaction.css`. The explicit `interaction.css` entry is redundant and causes double-loading. The rubric docks points for this.

**Step 1: Remove the interaction.css line from fdic.libraries.yml**

Change the `tokens` library from:

```yaml
tokens:
  css:
    base:
      node_modules/@fdic-ds/tokens/semantic.css: {}
      # semantic.css imports interaction.css; this explicit entry keeps the
      # token dependency visible to Drupal library readers.
      node_modules/@fdic-ds/tokens/interaction.css: {}
```

To:

```yaml
tokens:
  css:
    base:
      # semantic.css imports interaction.css automatically.
      node_modules/@fdic-ds/tokens/semantic.css: {}
```

**Step 2: Commit**

```bash
git add fdic.libraries.yml
git commit -m "fix: remove redundant interaction.css (already imported by semantic.css)"
```

---

### Task 12: Add ARIA labels to sidebar landmarks (8.2 — ~1 pt)

**Files:**
- Modify: `templates/layout/page.html.twig`

**Why:** When both sidebars are present, there are duplicate `role="complementary"` landmarks without distinguishing labels. Screen readers cannot differentiate them.

**Step 1: Add aria-label to both sidebar asides**

Change:
```twig
        <aside class="fdic-sidebar fdic-sidebar-first" role="complementary">
```
To:
```twig
        <aside class="fdic-sidebar fdic-sidebar-first" role="complementary" aria-label="{{ 'First sidebar'|t }}">
```

And change:
```twig
        <aside class="fdic-sidebar fdic-sidebar-second" role="complementary">
```
To:
```twig
        <aside class="fdic-sidebar fdic-sidebar-second" role="complementary" aria-label="{{ 'Second sidebar'|t }}">
```

**Step 2: Commit**

```bash
git add templates/layout/page.html.twig
git commit -m "fix: add aria-label to sidebar landmarks for screen reader differentiation"
```

---

### Task 13: Expand menu serialization with optional DS fields (4.3 — ~1 pt)

**Files:**
- Modify: `fdic.theme`

**Why:** The DS global header adapter supports optional `id`, `keywords`, `overviewTitle`, and `overviewUrl` fields. The current serializer skips them even when the source menu link provides them via options or metadata.

**Step 1: Expand fdic_serialize_menu_tree() in fdic.theme**

In the `fdic_serialize_menu_tree()` function, after the `ariaLabel` block (around line 153), add:

```php
    // Optional fields supported by the DS global header adapter.
    $plugin_definition = $link->getPluginDefinition();
    $menu_link_id = $link->getPluginId();
    if ($menu_link_id) {
      $item['id'] = $menu_link_id;
    }

    if (!empty($plugin_definition['metadata']['keywords'])) {
      $item['keywords'] = $plugin_definition['metadata']['keywords'];
    }

    if (!empty($options['attributes']['data-overview-title'])) {
      $item['overviewTitle'] = $options['attributes']['data-overview-title'];
    }

    if (!empty($options['attributes']['data-overview-url'])) {
      $item['overviewUrl'] = $options['attributes']['data-overview-url'];
    }
```

**Step 2: Commit**

```bash
git add fdic.theme
git commit -m "fix: map optional DS header adapter fields (id, keywords, overview)"
```

---

### Task 14: Update theme.css to use more DS tokens (3.2 — ~1 pt)

**Files:**
- Modify: `css/theme.css`

**Why:** Several theme CSS custom properties are hardcoded aliases when they could reference DS tokens directly. The score card flags spacing, typography, and layout values that should map to the token package.

**Step 1: Update :root token aliases to reference DS tokens where available**

In the `:root` block, update these aliases:

```css
  --fdic-spacing-xs: var(--ds-spacing-2xs, 0.25rem);
  --fdic-spacing-sm: var(--ds-spacing-xs, 0.5rem);
  --fdic-spacing-md: var(--ds-spacing-md, 1.25rem);
  --fdic-spacing-lg: var(--ds-spacing-xl, 2rem);
  --fdic-font-family-body: var(--ds-font-family-body, system-ui, sans-serif);
  --fdic-font-size-body: var(--ds-font-size-body, 1rem);
  --fdic-font-size-small: var(--ds-font-size-sm, 0.875rem);
  --fdic-line-height-heading: var(--ds-line-height-heading, 1.2);
  --fdic-line-height-body: var(--ds-line-height-body, 1.6);
```

Note: The `--ds-*` names are the DS token package conventions. If the DS token names differ, adjust to match what `semantic.css` actually exports. The hardcoded fallback values are preserved.

**Step 2: Commit**

```bash
git add css/theme.css
git commit -m "fix: reference DS tokens in theme.css aliases where available"
```

---

### Task 15: Clean up theme.css form control styles

**Files:**
- Modify: `css/theme.css`

**Why:** The `.fdic-native-control[slot="input"]` rule (line 227-229) targets the old slotted-input pattern we just removed. The `.fdic-form-control :where(input, textarea, select)` rule (line 223-225) also targets native controls that no longer exist inside DS wrappers.

**Step 1: Remove or update the stale form CSS**

Remove these rules from `css/theme.css`:

```css
.fdic-form-control :where(input, textarea, select) {
  width: 100%;
}

.fdic-native-control[slot="input"] {
  display: contents;
}
```

Replace with a simpler rule for the DS form controls:

```css
.fdic-form-control {
  display: block;
  width: 100%;
}
```

**Step 2: Commit**

```bash
git add css/theme.css
git commit -m "fix: update form CSS for host-attribute DS components"
```

---

### Task 16: Update docs/_theming.md (9.2 — ~1 pt)

**Files:**
- Modify: `docs/_theming.md`

**Why:** The Component Contracts section documents the old slot-based API pattern that we just replaced. It needs to reflect the current host-attribute approach.

**Step 1: Rewrite the Component Contracts section**

Replace everything from "## Component Contracts" to the end of that section with:

```markdown
## Component Contracts

Form templates use FDIC Design System form-associated custom elements that
participate in native form submission via `ElementInternals`. Drupal form
attributes (name, value, required, disabled, etc.) are passed directly to the
DS host element — no slotted native controls.

When upgrading `@fdic-ds/components`, verify these host-attribute contracts:

- `fd-input` — receives all `<input>` attributes on the host
- `fd-textarea` — receives all `<textarea>` attributes; value as text content
- `fd-selector` — receives `<select>` attributes; children are `<fd-option>` elements
- `fd-checkbox` / `fd-radio` — receive `label`, `name`, `value`, `checked`, `disabled` on host
- `fd-field` — auto-wires direct-child `fd-label` to its form control
- `fd-alert` — uses `type`, `title`, `live` attributes
- `fd-pagination` — uses `current-page`, `total-pages`, `href-template` attributes
- `fd-page-header` — uses `heading` attribute for the page title
- `fd-message` — uses `state` attribute (not `variant`)
- `fd-button` — text content becomes the button label
- `fd-global-footer` — structured-property component; do not pass Drupal blocks into unsupported light DOM
```

**Step 2: Commit**

```bash
git add docs/_theming.md
git commit -m "docs: update component contracts to match host-attribute APIs"
```

---

### Task 17: Remove .DS_Store from repo (polish)

**Files:**
- Remove: `.DS_Store`
- Modify: `.gitignore`

**Step 1: Remove .DS_Store and add to .gitignore**

```bash
git rm --cached .DS_Store
```

Check if `.DS_Store` is already in `.gitignore`. If not, add it.

**Step 2: Commit**

```bash
git add .gitignore
git commit -m "chore: remove .DS_Store and keep it ignored"
```

---

## Expected Score Impact

| Task | Criteria affected | Est. point gain |
|------|-------------------|-----------------|
| 1. fd-alert fix | 4.5, 8.3 | +4 |
| 2. fd-page-header fix | 6.4 | +2 |
| 3. fd-pagination fix | 4.6 | +2 |
| 4. fd-input fix | 5.1 | +2 |
| 5. fd-textarea fix | 5.2 | +2 |
| 6. fd-selector fix | 5.3 | +2 |
| 7. Checkbox/radio fix | 5.4 | +2 |
| 8. fd-field fix | 5.5 | +2 |
| 9. fd-button fix | 5.x | +1 |
| 10. fd-message fix | cross-ref | +1 |
| 11. Remove interaction.css | 2.4, 3.1 | +2 |
| 12. Sidebar ARIA labels | 8.2 | +1 |
| 13. Menu optional fields | 4.3 | +1 |
| 14. DS token refs in CSS | 3.2 | +1 |
| 15. Form CSS cleanup | — | +0 (consistency) |
| 16. Update docs | 9.2 | +1 |
| 17. Remove .DS_Store | — | +0 (polish) |
| **Total estimated** | | **~26 pts** |

Projected new score: ~117/120 = **97.5% (Grade A)**
