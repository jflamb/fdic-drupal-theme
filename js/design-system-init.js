import './custom-icons.js';

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

const initDesignSystemComponents = () => {
  initGlobalFooters();
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initDesignSystemComponents, { once: true });
}
else {
  initDesignSystemComponents();
}
