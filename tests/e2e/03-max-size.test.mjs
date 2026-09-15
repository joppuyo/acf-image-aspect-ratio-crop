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

// A 16:9 crop with a maximum width of 640 is scaled down to 640x360
const expectedSize = { width: 640, height: 360 };
const suffix = '-aspect-ratio-16-9';

scenario('Aspect ratio crop with a maximum size', ({ step, before }) => {
  let page;
  let postId;
  let cropId;

  before(async () => {
    await reset({ plugins: ['aiarc-test-field-output'] });
    page = await browser.newPage();
    await wpAdmin.login(page);
  });

  step('creates a 16:9 crop field with a maximum width of 640', async () => {
    await wpAdmin.createCropFieldGroup(page, {
      width: 16,
      height: 9,
      maxWidth: 640,
    });
  });

  step('scales the crop down to the maximum size', async () => {
    await wpAdmin.openNewPost(page);
    await wpAdmin.setPostTitle(page, 'Test Post');
    await crop.addImageFromMediaModal(
      page,
      'zoltan-kovacs-285132-unsplash.jpg',
    );
    await crop.cropInModal(page);

    const value = await crop.waitForCroppedValue(page);

    assert.ok(crop.fileName(value.imageUrl).includes(suffix), value.imageUrl);
    assertSizeClose(await crop.imageSize(page, value.imageUrl), expectedSize);

    cropId = Number(value.id);
    postId = await wpAdmin.publishPost(page);
  });

  step('returns the scaled down image as the field value', async () => {
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
});
