/**
 * @file
 * Disables native form fallbacks after matching DS controls upgrade.
 */

function setFallbackDisabled(fallback, disabled) {
  fallback.toggleAttribute('hidden', disabled);

  fallback.querySelectorAll('input, select, textarea, button').forEach((control) => {
    control.disabled = disabled;
  });
}

function findFallbackLabel(root, controlId) {
  if (!controlId) {
    return null;
  }

  return Array.from(root.querySelectorAll('[data-fdic-control-fallback-label-for]')).find((label) => {
    return label.dataset.fdicControlFallbackLabelFor === controlId;
  }) || null;
}

function findEnhancedLabel(root, controlId) {
  if (!controlId) {
    return null;
  }

  return Array.from(root.querySelectorAll('[data-fdic-enhanced-label-for]')).find((label) => {
    return label.dataset.fdicEnhancedLabelFor === controlId;
  }) || null;
}

function initControlFallbacks(root = document) {
  root.querySelectorAll('[data-fdic-enhanced-label-for]').forEach((label) => {
    const control = document.getElementById(label.dataset.fdicEnhancedLabelFor);

    if (!control?.localName?.startsWith('fd-')) {
      label.setAttribute('hidden', '');
    }
  });

  root.querySelectorAll('[data-fdic-control-fallback-for]').forEach((fallback) => {
    const controlId = fallback.dataset.fdicControlFallbackFor;
    const control = controlId ? document.getElementById(controlId) : fallback.previousElementSibling;
    const fallbackLabel = findFallbackLabel(root, controlId);
    const enhancedLabel = findEnhancedLabel(root, controlId);

    if (!control?.localName?.startsWith('fd-') || !window.customElements) {
      setFallbackDisabled(fallback, false);
      fallbackLabel?.removeAttribute('hidden');
      enhancedLabel?.setAttribute('hidden', '');
      return;
    }

    customElements.whenDefined(control.localName).then(() => {
      setFallbackDisabled(fallback, true);
      fallbackLabel?.setAttribute('hidden', '');
      enhancedLabel?.removeAttribute('hidden');
      control.setAttribute('data-fdic-upgraded', 'true');
    }).catch(() => {
      setFallbackDisabled(fallback, false);
      fallbackLabel?.removeAttribute('hidden');
      enhancedLabel?.setAttribute('hidden', '');
    });
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => initControlFallbacks(), { once: true });
}
else {
  initControlFallbacks();
}
