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

/**
 * Reads the field values the blocks of the aiarc-test-blocks fixture plugin
 * print on the front end of a post. Returns one { name, fields, raw } entry
 * per block, in the order the blocks appear in the post.
 */
export async function readBlockOutput(page, postId) {
  await page.goto(await postUrl(postId), { waitUntil: 'load' });

  return page.$$eval('.aiarc-test-block-fields', (elements) =>
    elements.map((el) => JSON.parse(el.textContent)),
  );
}
