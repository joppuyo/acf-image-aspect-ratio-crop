import assert from 'node:assert/strict';
import { assertCroppedFieldValue, assertSizeClose } from './assertions.mjs';
import * as browser from './browser.mjs';
import { acfPlugin } from './config.mjs';
import * as crop from './crop-field.mjs';
import { readBlockOutput, readFieldOutput } from './field-output.mjs';
import { scenario } from './flow.mjs';
import { compareVersions, pluginInfo, reset } from './site.mjs';
import * as wpAdmin from './wp-admin.mjs';

// A 16:9 crop of the 6000x4000 test image, scaled down by WordPress
const expectedSize = { width: 2560, height: 1440 };
const suffix = '-aspect-ratio-16-9';
const image = 'zoltan-kovacs-285132-unsplash.jpg';

/**
 * Why the site cannot run a block scenario, or false when it can. Blocks are
 * an ACF PRO feature, and newer block versions need newer ACF versions.
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
 *
 * With expandedEditor the block is also cropped in the expanded editor of
 * ACF block version 3. With metaBox the post also has a crop field in a meta
 * box, which is cropped before the block and again after the block form has
 * been remounted, since a meta box field changes how the field's JavaScript
 * is set up on the page.
 */
export function blockScenario({
  title,
  blockName,
  skip,
  expandedEditor = false,
  metaBox = false,
}) {
  scenario(title, { skip }, ({ step, before }) => {
    let page;
    let crops;
    let clientId;
    let blockCropId;
    let metaBoxCropId;
    let postId;

    const measure = (url) => crop.imageSize(page, url);

    /**
     * Waits for ACF to take a new crop into the block. The block attributes
     * update about half a second after the field, and in preview mode ACF
     * then fetches a new preview together with fresh form HTML. A form
     * mounted before that, for example by selecting the block again, still
     * holds the previous value, so wait for the preview the fixture renders
     * to show the crop as well.
     */
    async function waitForBlockToTakeCrop(cropId) {
      await wpAdmin.waitForBlockData(page, clientId, cropId);

      const mode = await page.evaluate(
        (id) =>
          window.wp.data.select('core/block-editor').getBlock(id).attributes
            .mode,
        clientId,
      );

      if (mode !== 'preview') {
        return;
      }

      // The preview is inside the editor canvas, which may be an iframe
      await browser.waitFor(
        async () => {
          for (const frame of page.frames()) {
            const raw = await frame
              .$eval(
                `#block-${clientId} .aiarc-test-block`,
                (el) => el.dataset.cropImage,
              )
              .catch(() => undefined);

            if (String(raw) === String(cropId)) {
              return true;
            }
          }

          return false;
        },
        { message: `the block preview to show crop ${cropId}` },
      );
    }

    async function startPost() {
      await wpAdmin.openNewPost(page);
      await wpAdmin.setPostTitle(page, 'Test Post');
    }

    /**
     * Clicks Crop in the open crop modal and returns the id of the crop the
     * field under root ends up holding, checking it and that the click sent
     * exactly one crop request.
     */
    async function cropAndCheck(root, previousId) {
      crops.reset();
      await crop.cropInModal(page);

      const value = await browser.waitFor(
        async () => {
          const current = await crop.fieldValue(page, { root });

          return /^\d+$/.test(current.id) &&
            Number(current.id) !== previousId &&
            crop.fileName(current.imageUrl).includes('-aspect-ratio-')
            ? current
            : null;
        },
        { message: `the field in ${root} to hold a new crop` },
      );

      assert.ok(crop.fileName(value.imageUrl).includes(suffix), value.imageUrl);
      assertSizeClose(await measure(value.imageUrl), expectedSize);
      assert.equal(crops.count, 1, 'one Crop click sends one crop request');

      return Number(value.id);
    }

    before(async () => {
      // The fixture plugins register the blocks, the meta box and their crop
      // fields
      await reset({
        plugins: metaBox
          ? [
              'aiarc-test-blocks',
              'aiarc-test-meta-box',
              'aiarc-test-field-output',
            ]
          : ['aiarc-test-blocks'],
      });
      page = await browser.newPage();
      crops = crop.trackCropRequests(page);
      await wpAdmin.login(page);
    });

    if (metaBox) {
      step('crops an image in the meta box', async () => {
        await startPost();
        await crop.addImageFromMediaModal(page, image, {
          root: wpAdmin.metaBoxRoot,
        });
        metaBoxCropId = await cropAndCheck(wpAdmin.metaBoxRoot);
      });
    }

    step(
      'crops an image in the block form after inserting the block',
      async (t) => {
        if (!metaBox) {
          await startPost();
        }

        clientId = await wpAdmin.insertBlock(page, blockName);
        await wpAdmin.selectBlockInSidebar(page, clientId);

        const root = await wpAdmin.waitForBlockForm(page, clientId);

        t.diagnostic(
          root === wpAdmin.blockSidebarRoot
            ? 'the form rendered in the sidebar'
            : 'the form rendered in the canvas',
        );

        await crop.addImageFromMediaModal(page, image, { root });
        blockCropId = await cropAndCheck(root);
        await waitForBlockToTakeCrop(blockCropId);
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

        assert.equal(Number(value.id), blockCropId);

        await crop.openRecropModal(page, { root });
        await crop.cancelCropModal(page);
        await crop.openRecropModal(page, { root });
        await crop.escapeCropModal(page);
      },
    );

    step('crops the image again in the block sidebar', async () => {
      const root = wpAdmin.blockSidebarRoot;

      await crop.openRecropModal(page, { root });
      blockCropId = await cropAndCheck(root, blockCropId);
      await waitForBlockToTakeCrop(blockCropId);
    });

    if (expandedEditor) {
      step('crops the image again in the expanded editor', async () => {
        const root = wpAdmin.expandedEditorRoot;

        await wpAdmin.openExpandedEditor(page);

        const value = await crop.waitForCroppedValue(page, { root });

        assert.equal(Number(value.id), blockCropId);

        await crop.openRecropModal(page, { root });
        await crop.cancelCropModal(page);
        await crop.openRecropModal(page, { root });
        await crop.escapeCropModal(page);
        // The expanded editor would take a moment to animate out
        await new Promise((resolve) => setTimeout(resolve, 1000));
        assert.ok(
          await page.$(root),
          'Escape closed the expanded editor along with the crop modal',
        );

        await crop.openRecropModal(page, { root });
        blockCropId = await cropAndCheck(root, blockCropId);
        // Done closes the editor without saving a change ACF has not picked
        // up yet, which takes about half a second
        await wpAdmin.waitForBlockData(page, clientId, blockCropId);
        await wpAdmin.closeExpandedEditor(page);
        await waitForBlockToTakeCrop(blockCropId);
      });
    }

    if (metaBox) {
      step(
        'crops the meta box image again after the block form remounted',
        async () => {
          const root = wpAdmin.metaBoxRoot;
          const value = await crop.waitForCroppedValue(page, { root });

          assert.equal(Number(value.id), metaBoxCropId);

          await crop.openRecropModal(page, { root });
          await crop.cancelCropModal(page);
          await crop.openRecropModal(page, { root });
          metaBoxCropId = await cropAndCheck(root, metaBoxCropId);
        },
      );
    }

    step('publishes the latest crops', async () => {
      postId = await wpAdmin.publishPost(page);

      const blocks = await readBlockOutput(page, postId);
      const block = blocks.find((candidate) => candidate.name === blockName);

      assert.ok(block, `no ${blockName} block on the front end`);
      assert.equal(Number(block.raw.crop_image), blockCropId);
      await assertCroppedFieldValue(block.fields.crop_image, {
        suffix,
        size: expectedSize,
        measure,
      });

      if (metaBox) {
        const { fields, raw } = await readFieldOutput(page, postId);

        assert.equal(Number(raw.meta_crop_image), metaBoxCropId);
        await assertCroppedFieldValue(fields.meta_crop_image, {
          suffix,
          size: expectedSize,
          measure,
        });
      }
    });

    step('shows the saved crops when the post is edited again', async () => {
      await wpAdmin.openPost(page, postId);

      const id = await wpAdmin.findBlock(page, blockName);

      assert.ok(id, `no ${blockName} block in the editor`);
      await wpAdmin.selectBlockInSidebar(page, id);

      const value = await crop.waitForCroppedValue(page, {
        root: wpAdmin.blockSidebarRoot,
      });

      assert.equal(Number(value.id), blockCropId);

      if (metaBox) {
        const metaBoxValue = await crop.waitForCroppedValue(page, {
          root: wpAdmin.metaBoxRoot,
        });

        assert.equal(Number(metaBoxValue.id), metaBoxCropId);
      }
    });
  });
}
