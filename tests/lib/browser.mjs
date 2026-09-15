import fs from 'node:fs';
import path from 'node:path';
import puppeteer from 'puppeteer';
import { headless, outputDir, slowMo } from './config.mjs';

let browser = null;
const pages = new Set();

export async function launch() {
  browser = await puppeteer.launch({
    headless,
    slowMo,
    // Lando serves the site with a certificate signed by its own CA
    acceptInsecureCerts: true,
    defaultViewport: { width: 1280, height: 1024 },
    args: ['--window-size=1280,1100'],
  });

  return browser;
}

export async function close() {
  if (browser) {
    await browser.close();
    browser = null;
    pages.clear();
  }
}

/**
 * Opens a page. An isolated page gets its own browser context with no cookies,
 * which is how the front end tests act as a logged out visitor while the
 * admin session stays logged in on another page.
 */
export async function newPage({ isolated = false } = {}) {
  const context = isolated
    ? await browser.createBrowserContext()
    : browser.defaultBrowserContext();
  const page = await context.newPage();

  page.setDefaultTimeout(30000);

  // Puppeteer blocks on unhandled dialogs, so accept everything and keep the
  // messages for tests that expect an alert.
  page.dialogs = [];
  page.on('dialog', async (dialog) => {
    page.dialogs.push(dialog.message());
    await dialog.accept();
  });

  pages.add(page);

  return page;
}

/**
 * Saves a full page screenshot of every open page, named after the test that
 * failed.
 */
export async function screenshotOnFailure(name) {
  fs.mkdirSync(outputDir, { recursive: true });

  let index = 0;

  for (const page of pages) {
    if (page.isClosed()) {
      continue;
    }

    const suffix = index === 0 ? '' : `-${index}`;
    const file = path.join(
      outputDir,
      `${name.replace(/[^a-z0-9]+/gi, '-').toLowerCase()}${suffix}.png`,
    );

    try {
      await page.screenshot({ path: file, fullPage: true });
      console.error(`Screenshot saved to ${file}`);
    } catch {
      // The page may be mid navigation or gone, which is not worth failing over
    }

    index += 1;
  }
}

/**
 * Polls until the callback returns a truthy value.
 */
export async function waitFor(
  callback,
  { timeout = 30000, interval = 250, message = 'condition' } = {},
) {
  const deadline = Date.now() + timeout;

  while (true) {
    const result = await callback();

    if (result) {
      return result;
    }

    if (Date.now() > deadline) {
      throw new Error(`Timed out after ${timeout}ms waiting for ${message}`);
    }

    await new Promise((resolve) => setTimeout(resolve, interval));
  }
}
