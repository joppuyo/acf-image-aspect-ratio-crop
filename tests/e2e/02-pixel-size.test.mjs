import assert from 'node:assert/strict';
import {
  assertCroppedFieldValue,
  assertSizeClose,
} from '../lib/assertions.mjs';
import * as browser from '../lib/browser.mjs';
import * as crop from '../lib/crop-field.mjs';
import { readFieldOutput } from '../lib/field-output.mjs';
import { scenario } from '../lib/flow.mjs';
import { reset } from '../lib/site.mjs';
import * as wpAdmin from '../lib/wp-admin.mjs';

// Pixel size crops are scaled to exactly the configured size
const expectedSize = { width: 640, height: 480 };
const suffix = '-aspect-ratio-640-480';

scenario('Pixel size crop', ({ step, before }) => {
  let page;
  let postId;
  let cropId;

  before(async () => {
    await reset({ plugins: ['aiarc-test-field-output'] });
    page = await browser.newPage();
    await wpAdmin.login(page);
  });

  step(
    'creates a field group with a 640x480 pixel size crop field',
    async () => {
      await wpAdmin.createCropFieldGroup(page, {
        cropType: 'pixel_size',
        width: 640,
        height: 480,
      });
    },
  );

  step('crops an uploaded image to 640x480 in a new post', async () => {
    await wpAdmin.openNewPost(page);
    await wpAdmin.setPostTitle(page, 'Test Post');
    await crop.addImageFromMediaModal(
      page,
      'zoltan-kovacs-285132-unsplash.jpg',
    );
    await crop.cropInModal(page);

    const value = await crop.waitForCroppedValue(page);

    assert.ok(crop.fileName(value.imageUrl).includes(suffix), value.imageUrl);
    assertSizeClose(
      await crop.imageSize(page, value.imageUrl),
      expectedSize,
      0,
    );

    cropId = Number(value.id);
    postId = await wpAdmin.publishPost(page);
  });

  step('returns the 640x480 image as the field value', async () => {
    const { fields, raw } = await readFieldOutput(page, postId);

    assert.equal(Number(raw.crop_image), cropId);
    await assertCroppedFieldValue(fields.crop_image, {
      suffix,
      size: expectedSize,
      measure: (url) => crop.imageSize(page, url),
    });
  });

  step('shows the cropped image when the post is edited again', async () => {
    await wpAdmin.openPost(page, postId);

    const value = await crop.waitForCroppedValue(page);

    assert.equal(Number(value.id), cropId);
  });

  step('rejects an image smaller than the pixel size', async () => {
    await wpAdmin.openNewPost(page);
    await wpAdmin.setPostTitle(page, 'Post with a too small image');

    const message = await crop.addRejectedImageFromMediaModal(
      page,
      'small.jpg',
    );

    assert.ok(message.includes('Image width must be at least 640px.'), message);
    assert.ok(
      message.includes('Image height must be at least 480px.'),
      message,
    );

    // The post still publishes, just without an image
    const secondPostId = await wpAdmin.publishPost(page);
    const { fields } = await readFieldOutput(page, secondPostId);

    assert.ok(!fields.crop_image, 'the rejected image must not be saved');
  });
});
