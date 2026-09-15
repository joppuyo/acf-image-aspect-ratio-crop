import assert from 'node:assert/strict';
import {
  assertCroppedFieldValue,
  assertSizeClose,
} from '../lib/assertions.mjs';
import * as browser from '../lib/browser.mjs';
import * as crop from '../lib/crop-field.mjs';
import { readFieldOutput } from '../lib/field-output.mjs';
import { scenario } from '../lib/flow.mjs';
import {
  openFrontEndForm,
  submitFrontEndForm,
} from '../lib/front-end-form.mjs';
import { reset } from '../lib/site.mjs';
import * as wpAdmin from '../lib/wp-admin.mjs';

const expectedSize = { width: 640, height: 360 };
const suffix = '-aspect-ratio-16-9';

scenario('Front end crop with a maximum size', ({ step, before }) => {
  let adminPage;
  let visitorPage;
  let postId;
  let cropId;

  before(async () => {
    await reset({
      plugins: ['aiarc-test-field-output', 'aiarc-test-front-end-form'],
    });
    adminPage = await browser.newPage();
    await wpAdmin.login(adminPage);
    visitorPage = await browser.newPage({ isolated: true });
  });

  step('creates a 16:9 crop field with a maximum width of 640', async () => {
    await wpAdmin.createCropFieldGroup(adminPage, {
      width: 16,
      height: 9,
      maxWidth: 640,
    });
  });

  step('publishes a post without an image', async () => {
    await wpAdmin.openNewPost(adminPage);
    await wpAdmin.setPostTitle(adminPage, 'Test Post');
    postId = await wpAdmin.publishPost(adminPage);
  });

  step('scales a front end crop down to the maximum size', async () => {
    await openFrontEndForm(visitorPage, postId);
    await crop.uploadImageFromFrontEnd(
      visitorPage,
      'zoltan-kovacs-285132-unsplash.jpg',
    );
    await crop.cropInModal(visitorPage);

    const value = await crop.waitForCroppedValue(visitorPage);

    assert.ok(crop.fileName(value.imageUrl).includes(suffix), value.imageUrl);
    assertSizeClose(
      await crop.imageSize(visitorPage, value.imageUrl),
      expectedSize,
    );

    cropId = Number(value.id);
    await submitFrontEndForm(visitorPage);
  });

  step('saves the scaled down image as the field value', async () => {
    const { fields, raw } = await readFieldOutput(visitorPage, postId);

    assert.equal(Number(raw.crop_image), cropId);
    await assertCroppedFieldValue(fields.crop_image, {
      suffix,
      size: expectedSize,
      measure: (url) => crop.imageSize(visitorPage, url),
    });
  });

  step('shows the cropped image in the admin', async () => {
    await wpAdmin.openPost(adminPage, postId);

    const value = await crop.waitForCroppedValue(adminPage);

    assert.equal(Number(value.id), cropId);
  });
});
