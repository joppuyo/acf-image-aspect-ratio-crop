import assert from 'node:assert/strict';
import { fileName } from './crop-field.mjs';

/**
 * Cropped image sizes can be off by a pixel from rounding, so compare with a
 * tolerance.
 */
export function assertSizeClose(actual, expected, delta = 2) {
  const message = `expected ${actual.width}x${actual.height} to be within ${delta}px of ${expected.width}x${expected.height}`;

  assert.ok(Math.abs(actual.width - expected.width) <= delta, message);
  assert.ok(Math.abs(actual.height - expected.height) <= delta, message);
}

/**
 * Checks a formatted crop field value: it points at a cropped file of the
 * expected size and carries the original image it was cropped from.
 */
export async function assertCroppedFieldValue(
  value,
  { suffix, size, measure },
) {
  assert.ok(value, 'the field has no value');
  assert.ok(
    fileName(value.url).includes(suffix),
    `expected ${value.url} to contain ${suffix}`,
  );
  assertSizeClose({ width: value.width, height: value.height }, size);
  assertSizeClose(await measure(value.url), size);

  assert.ok(value.original_image, 'the original image is missing');
  assert.notEqual(value.original_image.id, value.id);
  assert.ok(
    !fileName(value.original_image.url).includes('-aspect-ratio-'),
    `expected the original ${value.original_image.url} not to be a crop`,
  );
}
