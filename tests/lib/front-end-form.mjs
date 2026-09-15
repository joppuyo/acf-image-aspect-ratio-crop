import { postUrl } from './site.mjs';

const formSelector = '#aiarc-test-form';

/**
 * Opens the front end form the aiarc-test-front-end-form fixture plugin adds
 * to single posts.
 */
export async function openFrontEndForm(page, postId) {
  await page.goto(await postUrl(postId), { waitUntil: 'load' });

  const form = await page.$(formSelector);

  if (!form) {
    throw new Error(
      `No front end form found on ${page.url()}. Is the aiarc-test-front-end-form plugin active?`,
    );
  }
}

/**
 * Submits the form and waits for ACF to confirm the post was updated.
 */
export async function submitFrontEndForm(page) {
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }),
    page.click(`${formSelector} .acf-form-submit [type="submit"]`),
  ]);
  await page.waitForFunction(
    () =>
      Array.from(document.querySelectorAll('#message, .acf-notice')).some(
        (el) => el.textContent.includes('Post updated'),
      ),
    { timeout: 60000 },
  );
}
