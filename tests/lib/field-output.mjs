import { postUrl } from './site.mjs';

/**
 * Reads the field values the aiarc-test-field-output fixture plugin prints on
 * the front end of a post. Returns { post_id, fields, raw } where fields are
 * the formatted values and raw the values as stored.
 */
export async function readFieldOutput(page, postId) {
  await page.goto(await postUrl(postId), { waitUntil: 'load' });

  const output = await page.$('#aiarc-test-fields');

  if (!output) {
    throw new Error(
      `No field output found on ${page.url()}. Is the aiarc-test-field-output plugin active?`,
    );
  }

  return output.evaluate((el) => JSON.parse(el.textContent));
}
