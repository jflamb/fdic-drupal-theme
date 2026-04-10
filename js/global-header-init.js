import { createFdGlobalHeaderContentFromDrupal } from '@fdic-ds/components/fd-global-header-drupal';

const parseJsonSource = (source) => {
  if (!source?.trim()) {
    return null;
  }

  try {
    return JSON.parse(source);
  }
  catch (error) {
    console.error('FDIC global header menu JSON could not be parsed.', error);
    return null;
  }
};

const initGlobalHeader = (root = document) => {
  const header = root.querySelector('fd-global-header[data-fdic-global-header]');

  if (!header) {
    return;
  }

  const source = header.querySelector('script[data-fdic-global-header-source]');
  const content = parseJsonSource(source?.textContent ?? header.dataset.fdicGlobalHeaderSource);
  if (!content) {
    return;
  }

  const headerContent = createFdGlobalHeaderContentFromDrupal(content);
  header.navigation = headerContent.navigation;
  header.search = headerContent.search;
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => initGlobalHeader(), { once: true });
}
else {
  initGlobalHeader();
}
