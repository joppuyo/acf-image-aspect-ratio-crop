import assert from 'node:assert/strict';
import { assertCroppedFieldValue, assertSizeClose } from './assertions.mjs';
import * as browser from './browser.mjs';
import { acfPlugin } from './config.mjs';
import * as crop from './crop-field.mjs';
import { readBlockOutput } from './field-output.mjs';
import { scenario } from './flow.mjs';
import { compareVersions, pluginInfo, reset } from './site.mjs';
import * as wpAdmin from './wp-admin.mjs';

// A 16:9 crop of the 6000x4000 test image, scaled down by WordPress
const expectedSize = { width: 2560, height: 1440 };
const suffix = '-aspect-ratio-16-9';
const image = 'zoltan-kovacs-285132-unsplash.jpg';

/**
 * Why the site cannot run a block scenario, or false when it can. Blocks are
 * an ACF PRO feature, and block.json registration needs a minimum version.
 */
export async function blockSupport({ minimumAcfVersion }) {
  const acf = await pluginInfo(acfPlugin);

  if (!acf) {
    return `${acfPlugin} is not installed in the site`;
  }

  if (!/pro/i.test(acf.name)) {
    return `ACF blocks need ACF PRO, the site runs ${acf.name} ${acf.version}`;
  }

  if (compareVersions(acf.version, minimumAcfVersion) < 0) {
    return `this block needs ACF PRO ${minimumAcfVersion}, the site runs ${acf.version}`;
  }

  return false;
}

/**
 * Crops in a block's form where ACF first renders it (the canvas in edit
 * mode, or the sidebar where the canvas is an iframe), then again in the
 * block sidebar after the form has been remounted, checks Cancel and Escape
 * on the way, and reads the saved value on the front end. Remounting is where
 * ACF block bugs tend to show.
 */
export function blockScenario({ title, blockName, skip }) {
  scenario(title, { skip }, ({ step, before }) => {
    let page;
    let crops;
    let clientId;
    let canvasCropId;
    let sidebarCropId;
    let postId;

    const measure = (url) => crop.imageSize(page, url);

    before(async () => {
      // The fixture plugin registers the blocks and their crop field
      await reset({ plugins: ['aiarc-test-blocks'] });
      page = await browser.newPage();
      crops = crop.trackCropRequests(page);
      await wpAdmin.login(page);
    });

    step(
      'crops an image in the block form after inserting the block',
      async (t) => {
        await wpAdmin.openNewPost(page);
        await wpAdmin.setPostTitle(page, 'Test Post');
        clientId = await wpAdmin.insertBlock(page, blockName);
        await wpAdmin.selectBlockInSidebar(page, clientId);

        const root = await wpAdmin.waitForBlockForm(page, clientId);

        t.diagnostic(
          root === wpAdmin.blockSidebarRoot
            ? 'the form rendered in the sidebar'
            : 'the form rendered in the canvas',
        );

        await crop.addImageFromMediaModal(page, image, { root });
        crops.reset();
        await crop.cropInModal(page);

        const value = await crop.waitForCroppedValue(page, { root });

        assert.ok(
          crop.fileName(value.imageUrl).includes(suffix),
          value.imageUrl,
        );
        assertSizeClose(await measure(value.imageUrl), expectedSize);
        assert.equal(crops.count, 1, 'one Crop click sends one crop request');

        canvasCropId = Number(value.id);
        await wpAdmin.waitForBlockData(page, clientId, canvasCropId);
      },
    );

    step(
      'closes the crop modal with Cancel and Escape in the sidebar',
      async () => {
        // Deselecting and selecting the block again remounts the sidebar
        // form, also on sites where it was in the sidebar from the start
        await wpAdmin.setAcfBlockMode(page, clientId, 'preview');
        await wpAdmin.clearBlockSelection(page);
        await wpAdmin.selectBlockInSidebar(page, clientId);

        const root = wpAdmin.blockSidebarRoot;
        const value = await crop.waitForCroppedValue(page, { root });

        assert.equal(Number(value.id), canvasCropId);

        await crop.openRecropModal(page, { root });
        await crop.cancelCropModal(page);
        await crop.openRecropModal(page, { root });
        await crop.escapeCropModal(page);
      },
    );

    step('crops the image again in the block sidebar', async () => {
      const root = wpAdmin.blockSidebarRoot;

      await crop.openRecropModal(page, { root });
      crops.reset();
      await crop.cropInModal(page);

      const value = await browser.waitFor(
        async () => {
          const current = await crop.fieldValue(page, { root });

          return Number(current.id) !== canvasCropId ? current : null;
        },
        { message: 'the sidebar crop to replace the canvas crop' },
      );

      assert.ok(crop.fileName(value.imageUrl).includes(suffix), value.imageUrl);
      assertSizeClose(await measure(value.imageUrl), expectedSize);
      assert.equal(crops.count, 1, 'one Crop click sends one crop request');

      sidebarCropId = Number(value.id);
      await wpAdmin.waitForBlockData(page, clientId, sidebarCropId);
      postId = await wpAdmin.publishPost(page);
    });

    step('saves the sidebar crop as the block value', async () => {
      const blocks = await readBlockOutput(page, postId);
      const block = blocks.find((candidate) => candidate.name === blockName);

      assert.ok(block, `no ${blockName} block on the front end`);
      assert.equal(Number(block.raw.crop_image), sidebarCropId);
      await assertCroppedFieldValue(block.fields.crop_image, {
        suffix,
        size: expectedSize,
        measure,
      });
    });

    step('shows the saved crop when the post is edited again', async () => {
      await wpAdmin.openPost(page, postId);

      const id = await wpAdmin.findBlock(page, blockName);

      assert.ok(id, `no ${blockName} block in the editor`);
      await wpAdmin.selectBlockInSidebar(page, id);

      const value = await crop.waitForCroppedValue(page, {
        root: wpAdmin.blockSidebarRoot,
      });

      assert.equal(Number(value.id), sidebarCropId);
    });
  });
}
