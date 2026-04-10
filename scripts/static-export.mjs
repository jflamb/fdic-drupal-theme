#!/usr/bin/env node

import { mkdir, rm, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const args = new Map();
for (let index = 2; index < process.argv.length; index += 2) {
  args.set(process.argv[index], process.argv[index + 1]);
}

const base = args.get('--base');
const outDir = args.get('--out');
const pathsJson = args.get('--paths-json');

if (!base || !outDir || !pathsJson) {
  console.error('Usage: static-export.mjs --base <url> --out <dir> --paths-json <json>');
  process.exit(1);
}

const baseUrl = new URL(base.endsWith('/') ? base : `${base}/`);
const outputRoot = path.resolve(outDir);
const pagePaths = JSON.parse(pathsJson);
const pageMap = new Map();
const assetQueue = [];
const assetSeen = new Map();

function pageKey(url) {
  return `${url.pathname}${url.search}`;
}

function cleanPathname(pathname) {
  const decoded = decodeURIComponent(pathname).replace(/^\/+/, '');
  const normalized = path.posix.normalize(decoded);

  if (normalized === '.' || normalized.startsWith('..')) {
    throw new Error(`Unsafe output path for ${pathname}`);
  }

  return normalized;
}

function pageOutputForPath(pagePath) {
  const url = new URL(pagePath, baseUrl);

  if (url.pathname === '/') {
    return 'index.html';
  }

  const clean = cleanPathname(url.pathname);

  if (url.search) {
    const safeQuery = url.search
      .slice(1)
      .replace(/[^a-zA-Z0-9._-]+/g, '-')
      .replace(/^-|-$/g, '') || 'query';
    return path.posix.join(clean, `${safeQuery}.html`);
  }

  return path.posix.join(clean, 'index.html');
}

function assetOutputForUrl(url) {
  const clean = cleanPathname(url.pathname);
  return clean || 'index.asset';
}

function relativeReference(fromOutput, toOutput) {
  const fromDir = path.posix.dirname(fromOutput);
  const relative = path.posix.relative(fromDir, toOutput);
  return relative || path.posix.basename(toOutput);
}

function isSkippableUrl(value) {
  return /^(#|mailto:|tel:|javascript:|data:)/i.test(value.trim());
}

function sameOrigin(url) {
  return url.origin === baseUrl.origin;
}

function isLikelyAsset(url) {
  return /\.(avif|css|gif|ico|jpe?g|js|json|map|mjs|png|svg|webp|woff2?|ttf|eot|otf|txt|xml)$/i.test(url.pathname)
    || /^\/(core|libraries|modules|profiles|sites|themes)\//.test(url.pathname);
}

function enqueueAsset(url) {
  if (!sameOrigin(url)) {
    return null;
  }

  const output = assetOutputForUrl(url);
  const key = url.pathname;

  if (!assetSeen.has(key)) {
    assetSeen.set(key, output);
    assetQueue.push({ url, output });
  }

  return assetSeen.get(key);
}

function discoverSrcset(value, currentUrl, currentOutput) {
  return value.split(',')
    .map((part) => {
      const trimmed = part.trim();
      const match = trimmed.match(/^(\S+)(.*)$/);

      if (!match || isSkippableUrl(match[1])) {
        return part;
      }

      const assetUrl = new URL(match[1], currentUrl);
      const assetOutput = enqueueAsset(assetUrl);

      if (!assetOutput) {
        return part;
      }

      return `${relativeReference(currentOutput, assetOutput)}${match[2]}`;
    })
    .join(', ');
}

function rewriteHtml(html, currentUrl, currentOutput) {
  let rewritten = html.replace(/\s(srcset)=["']([^"']+)["']/gi, (match, attr, value) => {
    return ` ${attr}="${discoverSrcset(value, currentUrl, currentOutput)}"`;
  });

  rewritten = rewritten.replace(/\s(href|src|action)=["']([^"']+)["']/gi, (match, attr, value) => {
    if (isSkippableUrl(value)) {
      return match;
    }

    const targetUrl = new URL(value, currentUrl);

    if (!sameOrigin(targetUrl)) {
      return match;
    }

    const mappedPage = pageMap.get(pageKey(targetUrl));
    if ((attr.toLowerCase() === 'href' || attr.toLowerCase() === 'action') && mappedPage) {
      return ` ${attr}="${relativeReference(currentOutput, mappedPage)}${targetUrl.hash}"`;
    }

    if (attr.toLowerCase() === 'href') {
      if (!isLikelyAsset(targetUrl)) {
        return match;
      }

      const assetOutput = enqueueAsset(targetUrl);
      if (!assetOutput) {
        return match;
      }

      return ` ${attr}="${relativeReference(currentOutput, assetOutput)}"`;
    }

    if (attr.toLowerCase() === 'src') {
      const assetOutput = enqueueAsset(targetUrl);
      if (!assetOutput) {
        return match;
      }

      return ` ${attr}="${relativeReference(currentOutput, assetOutput)}"`;
    }

    return match;
  });

  rewritten = rewritten.replace(/"([^"]+)":"(\\\/[^"]+)"/g, (match, specifier, escapedValue) => {
    const value = escapedValue.replace(/\\\//g, '/');
    const targetUrl = new URL(value, currentUrl);

    if (!sameOrigin(targetUrl) || !isLikelyAsset(targetUrl)) {
      return match;
    }

    const assetOutput = enqueueAsset(targetUrl);
    if (!assetOutput) {
      return match;
    }

    return `"${specifier}":"${relativeReference(currentOutput, assetOutput)}"`;
  });

  return rewritten;
}

function rewriteCss(css, currentUrl, currentOutput) {
  return css.replace(/url\((['"]?)([^'")]+)\1\)/gi, (match, quote, value) => {
    if (isSkippableUrl(value)) {
      return match;
    }

    const assetUrl = new URL(value, currentUrl);
    const assetOutput = enqueueAsset(assetUrl);

    if (!assetOutput) {
      return match;
    }

    return `url(${quote}${relativeReference(currentOutput, assetOutput)}${quote})`;
  }).replace(/@import\s+(?:url\()?(['"])([^'"]+)\1\)?/gi, (match, quote, value) => {
    if (isSkippableUrl(value)) {
      return match;
    }

    const assetUrl = new URL(value, currentUrl);
    const assetOutput = enqueueAsset(assetUrl);

    if (!assetOutput) {
      return match;
    }

    return `@import ${quote}${relativeReference(currentOutput, assetOutput)}${quote}`;
  });
}

async function fetchRequired(url) {
  const response = await fetch(url, { redirect: 'follow' });

  if (!response.ok) {
    throw new Error(`Expected HTTP 2xx for ${url.href}, got ${response.status}`);
  }

  return response;
}

async function writeOutput(relativeOutput, data) {
  const target = path.join(outputRoot, relativeOutput);
  await mkdir(path.dirname(target), { recursive: true });
  await writeFile(target, data);
}

for (const pagePath of pagePaths) {
  const url = new URL(pagePath, baseUrl);
  pageMap.set(pageKey(url), pageOutputForPath(pagePath));
}

await rm(outputRoot, { force: true, recursive: true });
await mkdir(outputRoot, { recursive: true });

for (const pagePath of pagePaths) {
  const url = new URL(pagePath, baseUrl);
  const output = pageMap.get(pageKey(url));
  const response = await fetchRequired(url);
  const html = await response.text();
  await writeOutput(output, rewriteHtml(html, url, output));
}

for (let index = 0; index < assetQueue.length; index += 1) {
  const { url, output } = assetQueue[index];
  const response = await fetchRequired(url);
  const contentType = response.headers.get('content-type') || '';

  if (contentType.includes('text/css') || url.pathname.endsWith('.css')) {
    const css = await response.text();
    await writeOutput(output, rewriteCss(css, url, output));
    continue;
  }

  const buffer = Buffer.from(await response.arrayBuffer());
  await writeOutput(output, buffer);
}

const manifest = {
  generatedBy: path.basename(fileURLToPath(import.meta.url)),
  baseUrl: baseUrl.href,
  pages: pagePaths,
  assets: [...assetSeen.values()].sort(),
};

await writeOutput('snapshot-manifest.json', `${JSON.stringify(manifest, null, 2)}\n`);
console.log(`Exported ${pagePaths.length} pages and ${assetSeen.size} assets to ${outputRoot}`);
