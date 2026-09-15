import path from 'node:path';
import { fixturesDir } from './config.mjs';
import { waitFor } from './browser.mjs';

const fieldSelector = '.acf-field-image-aspect-ratio-crop';
const uploaderSelector = `${fieldSelector} .acf-image-uploader-aspect-ratio-crop`;
const cropModalSelector = '.js-acf-image-aspect-ratio-crop-modal';

export function imagePath(name) {
  return path.join(fixturesDir, 'images', name);
}

/**
 * The file name part of an attachment URL. The site URL can contain the same
 * words as a cropped file name, so checks have to look at the name alone.
 */
export function fileName(url) {
  return new URL(url).pathname.split('/').pop();
}

/**
 * Opens the media modal from the field, uploads an image from
 * tests/fixtures/images and selects it, which opens the crop modal.
 */
export async function addImageFromMediaModal(page, imageName) {
  await page.click(`${fieldSelector} a[data-name="add"]`);
  await page.waitForSelector('.media-modal', { visible: true });

  // The plupload file input is always in the modal, whichever tab is open
  const input = await page.waitForSelector(
    '.media-modal .moxie-shim input[type="file"]',
  );

  await input.uploadFile(imagePath(imageName));

  const select = await page.waitForSelector(
    '.media-modal .media-toolbar-primary button.media-button-select:not([disabled])',
    { timeout: 60000 },
  );

  await select.click();
}

/**
 * Uploads an image through the basic uploader of a front end form, which
 * opens the crop modal on success.
 */
export async function uploadImageFromFrontEnd(page, imageName) {
  const input = await page.waitForSelector(`${fieldSelector} .js-aiarc-upload`);

  await input.uploadFile(imagePath(imageName));
}

/**
 * Waits for the crop modal, accepts the default crop area and waits for the
 * modal to close again.
 */
export async function cropInModal(page) {
  await page.waitForSelector(cropModalSelector, {
    visible: true,
    timeout: 60000,
  });
  await page.waitForSelector(`${cropModalSelector} .cropper-crop-box`, {
    visible: true,
    timeout: 60000,
  });
  await page.click(`${cropModalSelector} .js-acf-image-aspect-ratio-crop-crop`);
  await page.waitForSelector(cropModalSelector, {
    hidden: true,
    timeout: 60000,
  });
}

/**
 * The attachment id in the hidden input and the preview image URL of the
 * field.
 */
export async function fieldValue(page) {
  return page.$eval(uploaderSelector, (el) => ({
    id: el.querySelector('input[type="hidden"]').value,
    imageUrl: el.querySelector('img[data-name="image"]').getAttribute('src'),
    originalId: el.dataset.originalImageId || null,
  }));
}

/**
 * Waits until the field holds a cropped attachment and returns its value.
 */
export async function waitForCroppedValue(page) {
  return waitFor(
    async () => {
      const value = await fieldValue(page);

      return /^\d+$/.test(value.id) &&
        fileName(value.imageUrl).includes('-aspect-ratio-')
        ? value
        : null;
    },
    { message: 'the field to hold a cropped image' },
  );
}

/**
 * Clicks the remove icon that shows when hovering the preview image.
 */
export async function removeImage(page) {
  await page.hover(`${uploaderSelector} img[data-name="image"]`);
  await page.click(`${uploaderSelector} a[data-name="remove"]`);
  await page.waitForSelector(`${uploaderSelector}:not(.has-value)`);
}

/**
 * Loads an image URL inside the page and returns its natural size, so the
 * check runs against the file the site actually serves.
 */
export async function imageSize(page, url) {
  return page.evaluate(
    (src) =>
      new Promise((resolve, reject) => {
        const image = new Image();

        image.onload = () =>
          resolve({ width: image.naturalWidth, height: image.naturalHeight });
        image.onerror = () => reject(new Error(`Could not load ${src}`));
        image.src = src;
      }),
    url,
  );
}

/**
 * Uploads an image the field should reject and returns the error the media
 * modal shows for it, then closes the modal.
 */
export async function addRejectedImageFromMediaModal(page, imageName) {
  await page.click(`${fieldSelector} a[data-name="add"]`);
  await page.waitForSelector('.media-modal', { visible: true });

  const input = await page.waitForSelector(
    '.media-modal .moxie-shim input[type="file"]',
  );

  await input.uploadFile(imagePath(imageName));

  const error = await page.waitForSelector(
    '.media-modal .upload-error, .media-modal .media-uploader-status-error',
    { visible: true, timeout: 60000 },
  );
  const message = await error.evaluate((el) => el.textContent.trim());

  await page.click('.media-modal button.media-modal-close');
  await page.waitForSelector('.media-modal', { hidden: true });

  return message;
}
