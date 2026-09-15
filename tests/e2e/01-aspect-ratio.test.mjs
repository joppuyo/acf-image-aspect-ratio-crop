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

// A 16:9 crop of the 6000x4000 test image, scaled down by WordPress
const expectedSize = { width: 2560, height: 1440 };
const suffix = '-aspect-ratio-16-9';

scenario('Aspect ratio crop', ({ step, before }) => {
  let page;
  let postId;
  let firstCropId;

  before(async () => {
    await reset({ plugins: ['aiarc-test-field-output'] });
    page = await browser.newPage();
    await wpAdmin.login(page);
  });

  step('creates a field group with a 16:9 crop field', async () => {
    await wpAdmin.createCropFieldGroup(page, { width: 16, height: 9 });
  });

  step('crops an uploaded image in a new post', async () => {
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

    firstCropId = Number(value.id);
    postId = await wpAdmin.publishPost(page);
  });

  step(
    'returns the cropped image and its original as the field value',
    async () => {
      const { fields, raw } = await readFieldOutput(page, postId);

      assert.equal(Number(raw.crop_image), firstCropId);
      await assertCroppedFieldValue(fields.crop_image, {
        suffix,
        size: expectedSize,
        measure: (url) => crop.imageSize(page, url),
      });
    },
  );

  step('shows the cropped image when the post is edited again', async () => {
    await wpAdmin.openPost(page, postId);

    const value = await crop.waitForCroppedValue(page);

    assert.equal(Number(value.id), firstCropId);
  });

  step('replaces the image with a new crop', async () => {
    await crop.removeImage(page);
    await crop.addImageFromMediaModal(
      page,
      'sylwia-pietruszka-nPCiBaK8WPk-unsplash.jpg',
    );
    await crop.cropInModal(page);

    const value = await crop.waitForCroppedValue(page);

    assert.notEqual(Number(value.id), firstCropId);
    assert.ok(crop.fileName(value.imageUrl).includes(suffix), value.imageUrl);
    assertSizeClose(await crop.imageSize(page, value.imageUrl), expectedSize);

    await wpAdmin.updatePost(page);

    const { fields } = await readFieldOutput(page, postId);

    assert.equal(fields.crop_image.id, Number(value.id));
    assert.ok(
      crop.fileName(fields.crop_image.url).includes('sylwia-pietruszka'),
    );
  });

  step('lists both originals and both crops in the media library', async () => {
    const fileNames = await wpAdmin.mediaLibraryFileNames(page);

    for (const expected of [
      'zoltan-kovacs-285132-unsplash-scaled.jpg',
      'zoltan-kovacs-285132-unsplash-scaled-aspect-ratio-16-9-scaled.jpg',
      'sylwia-pietruszka-nPCiBaK8WPk-unsplash-scaled.jpg',
      'sylwia-pietruszka-nPCiBaK8WPk-unsplash-scaled-aspect-ratio-16-9-scaled.jpg',
    ]) {
      assert.ok(
        fileNames.includes(expected),
        `expected ${expected} in ${JSON.stringify(fileNames)}`,
      );
    }
  });
});
