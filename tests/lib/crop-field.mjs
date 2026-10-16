import path from 'node:path';
import { fixturesDir } from './config.mjs';
import { waitFor } from './browser.mjs';

const cropModalSelector = '.js-acf-image-aspect-ratio-crop-modal';

// Every helper that looks for the field takes an optional root selector, for
// pages where the field can be in more than one place, such as a block form
// in the editor canvas or in the sidebar
function fieldSelector(root = '') {
  return `${root} .acf-field-image-aspect-ratio-crop`.trim();
}

function uploaderSelector(root = '') {
  return `${fieldSelector(root)} .acf-image-uploader-aspect-ratio-crop`;
}

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
export async function addImageFromMediaModal(page, imageName, { root } = {}) {
  // Block forms are loaded with AJAX after the block is inserted
  const add = await page.waitForSelector(
    `${fieldSelector(root)} a[data-name="add"]`,
    { visible: true },
  );

  await add.click();
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
export async function uploadImageFromFrontEnd(page, imageName, { root } = {}) {
  const input = await page.waitForSelector(
    `${fieldSelector(root)} .js-aiarc-upload`,
  );

  await input.uploadFile(imagePath(imageName));
}

async function waitForCropModal(page) {
  await page.waitForSelector(cropModalSelector, {
    visible: true,
    timeout: 60000,
  });
  await page.waitForSelector(`${cropModalSelector} .cropper-crop-box`, {
    visible: true,
    timeout: 60000,
  });
}

async function waitForCropModalToClose(page, action) {
  try {
    await page.waitForSelector(cropModalSelector, {
      hidden: true,
      timeout: 60000,
    });
  } catch (error) {
    throw new Error(`The crop modal did not close after ${action}`, {
      cause: error,
    });
  }
}

/**
 * Waits for the crop modal, accepts the default crop area and waits for the
 * modal to close again.
 */
export async function cropInModal(page) {
  await waitForCropModal(page);
  await page.click(`${cropModalSelector} .js-acf-image-aspect-ratio-crop-crop`);
  await waitForCropModalToClose(page, 'clicking Crop');
}

/**
 * Opens the crop modal for the image the field already holds, using the crop
 * icon that shows when hovering the preview image.
 */
export async function openRecropModal(page, { root } = {}) {
  // Locators wait for the image and icon to stop moving, which matters inside
  // the expanded editor while its modal animates in
  await page
    .locator(`${uploaderSelector(root)} img[data-name="image"]`)
    .hover();
  await page.locator(`${uploaderSelector(root)} a[data-name="crop"]`).click();
  await waitForCropModal(page);
}

/**
 * Closes the crop modal with the Cancel button in its footer.
 */
export async function cancelCropModal(page) {
  await page.click(
    `${cropModalSelector} button.js-acf-image-aspect-ratio-crop-cancel`,
  );
  await waitForCropModalToClose(page, 'clicking Cancel');
}

/**
 * Closes the crop modal with the Escape key.
 */
export async function escapeCropModal(page) {
  // The crop icon keeps the focus for a few milliseconds after it is clicked,
  // until the modal covers it and it hides. An Escape that early would also
  // reach whatever the icon is in, such as the expanded editor of an ACF
  // block, which no person could press fast enough to do.
  await page
    .waitForFunction(() => !document.activeElement?.closest('.acf-actions'), {
      timeout: 5000,
    })
    .catch(() => {});
  await page.keyboard.press('Escape');
  await waitForCropModalToClose(page, 'pressing Escape');
}

/**
 * Counts the crop requests the page sends, through the REST API or through
 * admin-ajax in REST API compatibility mode. A doubled click handler still
 * produces a correct looking crop, so tests compare the count instead.
 */
export function trackCropRequests(page) {
  let count = 0;

  page.on('request', (request) => {
    if (request.method() !== 'POST') {
      return;
    }

    const url = decodeURIComponent(request.url());
    const body = request.postData() || '';

    if (
      url.includes('aiarc/v1/crop') ||
      body.includes('action=acf_image_aspect_ratio_crop_crop')
    ) {
      count += 1;
    }
  });

  return {
    get count() {
      return count;
    },
    reset() {
      count = 0;
    },
  };
}

/**
 * The attachment id in the hidden input and the preview image URL of the
 * field.
 */
export async function fieldValue(page, { root } = {}) {
  return page.$eval(uploaderSelector(root), (el) => ({
    id: el.querySelector('input[type="hidden"]').value,
    imageUrl: el.querySelector('img[data-name="image"]').getAttribute('src'),
    originalId: el.dataset.originalImageId || null,
  }));
}

/**
 * Waits until the field holds a cropped attachment and returns its value.
 */
export async function waitForCroppedValue(page, { root } = {}) {
  await page.waitForSelector(uploaderSelector(root));

  return waitFor(
    async () => {
      const value = await fieldValue(page, { root });

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
export async function removeImage(page, { root } = {}) {
  await page.hover(`${uploaderSelector(root)} img[data-name="image"]`);
  await page.click(`${uploaderSelector(root)} a[data-name="remove"]`);
  await page.waitForSelector(`${uploaderSelector(root)}:not(.has-value)`);
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
export async function addRejectedImageFromMediaModal(
  page,
  imageName,
  { root } = {},
) {
  await page.click(`${fieldSelector(root)} a[data-name="add"]`);
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
