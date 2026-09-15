import assert from 'node:assert/strict';
import {
  assertCroppedFieldValue,
  assertSizeClose,
} from '../lib/assertions.mjs';
import * as browser from '../lib/browser.mjs';
import * as crop from '../lib/crop-field.mjs';
import { readFieldOutput } from '../lib/field-output.mjs';
import { scenario } from '../lib/flow.mjs';
import { wp } from '../lib/lando.mjs';
import {
  compareVersions,
  pluginInfo,
  reset,
  wordPressVersion,
} from '../lib/site.mjs';
import * as wpAdmin from '../lib/wp-admin.mjs';

const expectedSize = { width: 2560, height: 1440 };
const suffix = '-aspect-ratio-16-9';

// Polylang is not part of the repository, so this only runs when one of the
// two editions is installed in the site and supports the WordPress version
// the site runs
const { polylang, skip } = await findPolylang();

async function findPolylang() {
  const wordPress = await wordPressVersion();
  const reasons = [];

  for (const slug of ['polylang-pro', 'polylang']) {
    const info = await pluginInfo(slug);

    if (!info) {
      continue;
    }

    if (info.requires && compareVersions(wordPress, info.requires) < 0) {
      reasons.push(
        `${info.name} ${info.version} requires WordPress ${info.requires}, the site runs ${wordPress}`,
      );
      continue;
    }

    return { polylang: slug, skip: false };
  }

  return {
    polylang: null,
    skip: reasons.join(', ') || 'Polylang is not installed in the site',
  };
}

scenario('Polylang compatibility', { skip }, ({ step, before }) => {
  let page;
  let postId;

  before(async () => {
    await reset({ plugins: ['aiarc-test-field-output', polylang] });
    // Polylang sends the first admin visit after activation to its setup
    // wizard, which would get in the way of logging in
    await wp('transient', 'delete', 'pll_activation_redirect').catch(() => {});
    page = await browser.newPage();
    await wpAdmin.login(page);
  });

  step('creates a field group with a 16:9 crop field', async () => {
    await wpAdmin.createCropFieldGroup(page, { width: 16, height: 9 });
  });

  step('crops an uploaded image with Polylang active', async () => {
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

    postId = await wpAdmin.publishPost(page);
  });

  step('returns the cropped image as the field value', async () => {
    const { fields } = await readFieldOutput(page, postId);

    await assertCroppedFieldValue(fields.crop_image, {
      suffix,
      size: expectedSize,
      measure: (url) => crop.imageSize(page, url),
    });
  });
});
