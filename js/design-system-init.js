const parseJsonSource = (source, label) => {
  if (!source?.trim()) {
    return null;
  }

  try {
    return JSON.parse(source);
  }
  catch (error) {
    console.error(`${label} JSON could not be parsed.`, error);
    return null;
  }
};

const initGlobalFooters = (root = document) => {
  const footers = root.querySelectorAll('fd-global-footer[data-fdic-global-footer]');

  for (const footer of footers) {
    const source = footer.querySelector('script[data-fdic-global-footer-source]');
    const data = parseJsonSource(source?.textContent, 'FDIC global footer');

    if (!data) {
      continue;
    }

    footer.utilityLinks = data.utilityLinks || [];
    footer.socialLinks = data.socialLinks || [];
  }
};

const initPageHeaders = (root = document) => {
  for (const header of root.querySelectorAll('fd-page-header')) {
    const source = header.querySelector('script[data-fdic-page-header-source]');
    const data = parseJsonSource(source?.textContent, 'FDIC page header');

    if (!Array.isArray(data)) {
      continue;
    }

    header.breadcrumbs = data;
  }
};

const initEvents = (root = document) => {
  for (const event of root.querySelectorAll('fd-event[data-fdic-event-metadata]')) {
    const source = event.getAttribute('data-fdic-event-metadata');

    if (!source?.trim()) {
      continue;
    }

    try {
      const metadata = JSON.parse(source);
      if (Array.isArray(metadata)) {
        event.metadata = metadata;
      }
    }
    catch (error) {
      console.error('FDIC event metadata JSON could not be parsed.', error);
    }
  }
};

const refreshCustomIconConsumers = (root = document) => {
  for (const element of root.querySelectorAll('fd-tile, fd-icon')) {
    if (typeof element.requestUpdate === 'function') {
      element.requestUpdate();
    }
  }
};

const initDesignSystemComponents = () => {
  initGlobalFooters();
  initPageHeaders();
  initEvents();
  refreshCustomIconConsumers();
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initDesignSystemComponents, { once: true });
}
else {
  initDesignSystemComponents();
}
