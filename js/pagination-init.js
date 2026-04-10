/**
 * @file
 * Adapts fd-pagination's 1-indexed pages to Drupal's 0-indexed ?page= URLs.
 */

const PAGE_PLACEHOLDER = '{page}';
const PAGE_EVENTS = [
  'fd-pagination-request',
  'fd-pagination-change',
  'fd-page-change',
];

function toDrupalPage(page) {
  const parsed = Number.parseInt(String(page), 10);

  if (!Number.isFinite(parsed)) {
    return 0;
  }

  return Math.max(0, parsed - 1);
}

function buildDrupalHref(template, page) {
  return template.replaceAll(PAGE_PLACEHOLDER, String(toDrupalPage(page)));
}

function normalizePagerHref(href) {
  const url = new URL(href, document.baseURI);
  const page = url.searchParams.get('page');

  if (page === null) {
    return href;
  }

  url.searchParams.set('page', String(toDrupalPage(page)));
  return url.pathname + url.search + url.hash;
}

function rewriteRenderedLinks(pager) {
  const roots = [pager];

  if (pager.shadowRoot) {
    roots.push(pager.shadowRoot);
  }

  roots.forEach((root) => {
    root.querySelectorAll('a[href]').forEach((link) => {
      if (link.dataset.fdicDrupalHrefNormalized === 'true') {
        return;
      }

      link.setAttribute('href', normalizePagerHref(link.getAttribute('href')));
      link.dataset.fdicDrupalHrefNormalized = 'true';
    });
  });
}

function pageFromEvent(event) {
  return event.detail?.page ?? event.detail?.currentPage ?? event.detail?.targetPage;
}

function observeRenderedLinks(pager) {
  if (
    !window.MutationObserver
    || !pager.shadowRoot
    || pager.dataset.fdicPaginationObserverAttached === 'true'
  ) {
    return;
  }

  pager.dataset.fdicPaginationObserverAttached = 'true';
  new MutationObserver(() => rewriteRenderedLinks(pager)).observe(pager.shadowRoot, {
    childList: true,
    subtree: true,
  });
}

function initPagination(root = document) {
  root.querySelectorAll('fd-pagination[data-drupal-href-template]').forEach((pager) => {
    if (pager.dataset.fdicPaginationInitialized === 'true') {
      return;
    }

    pager.dataset.fdicPaginationInitialized = 'true';
    const template = pager.dataset.drupalHrefTemplate;

    PAGE_EVENTS.forEach((eventName) => {
      pager.addEventListener(eventName, (event) => {
        const page = pageFromEvent(event);

        if (page === undefined || !template) {
          return;
        }

        event.preventDefault();
        window.location.href = buildDrupalHref(template, page);
      });
    });

    pager.addEventListener('click', (event) => {
      rewriteRenderedLinks(pager);
    }, { capture: true });

    observeRenderedLinks(pager);

    window.customElements?.whenDefined('fd-pagination')
      .then(() => {
        rewriteRenderedLinks(pager);
        observeRenderedLinks(pager);
      })
      .catch(() => {});
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => initPagination(), { once: true });
}
else {
  initPagination();
}
