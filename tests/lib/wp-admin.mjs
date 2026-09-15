import { admin } from './config.mjs';
import { adminUrl, loginUrl } from './site.mjs';
import { waitFor } from './browser.mjs';

export async function login(page) {
  await page.goto(await loginUrl(), { waitUntil: 'load' });
  // The login page focuses and selects the username field 200ms after load,
  // which would replace anything typed before that moment
  await page
    .waitForFunction(() => document.activeElement?.id === 'user_login', {
      timeout: 2000,
    })
    .catch(() => {});
  await page.type('#user_login', admin.username);
  await page.type('#user_pass', admin.password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }),
    page.click('#wp-submit'),
  ]);

  if (!(await page.$('#wpadminbar'))) {
    throw new Error(`Login as ${admin.username} failed, now on ${page.url()}`);
  }
}

export async function goToAdminPage(page, pathname) {
  await page.goto(await adminUrl(pathname), { waitUntil: 'load' });
}

/**
 * Creates a field group through the ACF field group editor with one crop
 * field in it, located on posts (the ACF default). Returns the field key.
 */
export async function createCropFieldGroup(
  page,
  {
    title = 'Post',
    label = 'Crop Image',
    name = 'crop_image',
    cropType = 'aspect_ratio',
    width,
    height,
    maxWidth,
  },
) {
  await goToAdminPage(page, 'post-new.php?post_type=acf-field-group');
  await page.type('#title', title);

  // ACF 6 adds and opens the first field on its own, older versions need the
  // button
  let field = await page
    .waitForSelector('.acf-field-object', { timeout: 3000 })
    .catch(() => null);

  if (!field) {
    await page.click('a.add-field');
    field = await page.waitForSelector('.acf-field-object');
  }

  const key = await field.evaluate((el) => el.dataset.key);
  const scope = `.acf-field-object[data-key="${key}"]`;

  await page.select(`${scope} select.field-type`, 'image_aspect_ratio_crop');
  // Changing the type re-renders the settings, the crop settings show up when
  // that is done
  await page.waitForSelector(`${scope} .js-aspect-ratio-width`, {
    visible: true,
  });

  await page.type(`${scope} input.field-label`, label);
  // ACF derives the name from the label as soon as the label loses focus
  await page.keyboard.press('Tab');
  await setInputValue(page, `${scope} input.field-name`, name);
  await page.select(`${scope} select.crop-type-select`, cropType);

  if (cropType !== 'free_crop') {
    await page.type(`${scope} .js-aspect-ratio-width`, String(width));
    await page.type(`${scope} .js-aspect-ratio-height`, String(height));
  }

  if (maxWidth) {
    await page.type(`${scope} .js-max-width`, String(maxWidth));
  }

  // WordPress rewrites the URL with history.replaceState when the publish
  // button is clicked, which waitForNavigation would mistake for the real
  // navigation, so wait for the saved field group page itself
  await page.click('.acf-publish, #publish');
  await page.waitForFunction(
    () =>
      document.readyState === 'complete' &&
      /\/post\.php\?post=\d+/.test(location.href),
    { timeout: 60000 },
  );

  const saved = await page.$(`${scope}`);

  if (!saved) {
    throw new Error('The field group did not save, the field is gone');
  }

  return key;
}

/**
 * Replaces the value of a text input the way a user would, so listeners on
 * input and change events run.
 */
export async function setInputValue(page, selector, value) {
  const input = await page.waitForSelector(selector, { visible: true });

  await input.evaluate((el) => {
    el.focus();
    el.select();
  });
  await page.keyboard.type(String(value));
  await page.keyboard.press('Tab');
}

/**
 * Returns the frame the block editor renders the post content in, which is an
 * iframe in newer WordPress versions and the page itself in older ones.
 */
export function editorCanvas(page) {
  return (
    page.frames().find((frame) => frame.name() === 'editor-canvas') || page
  );
}

async function dismissWelcomeGuide(page) {
  // WordPress 5.0 to 5.3 show tip popovers instead of the guide, and the
  // first one takes the focus
  await page.evaluate(() => {
    const nux = window.wp.data.select('core/nux');

    if (nux?.areTipsEnabled?.()) {
      window.wp.data.dispatch('core/nux').disableTips();
    }
  });

  const guide = await page
    .waitForSelector('.edit-post-welcome-guide, .editor-welcome-guide', {
      timeout: 3000,
    })
    .catch(() => null);

  if (guide) {
    await page.keyboard.press('Escape');
    await page.waitForSelector('.components-modal__screen-overlay', {
      hidden: true,
    });
  }
}

/**
 * WordPress 6.7 and newer keep meta boxes in a panel below the canvas that is
 * collapsed until the user opens it, which would leave the ACF field
 * unclickable.
 */
async function openMetaBoxesPanel(page) {
  const panel = await page
    .waitForSelector('.edit-post-meta-boxes-main', { timeout: 3000 })
    .catch(() => null);

  if (!panel) {
    return;
  }

  await page.evaluate(() => {
    const preferences = window.wp.data.dispatch('core/preferences');

    preferences.set('core/edit-post', 'metaBoxesMainIsOpen', true);
    preferences.set('core/edit-post', 'metaBoxesMainOpenHeight', 600);
  });
  await page.waitForSelector('.edit-post-meta-boxes-main .acf-field', {
    visible: true,
  });
}

async function waitForEditor(page) {
  await page.waitForFunction(
    () => Boolean(window.wp?.data?.select('core/editor')?.getCurrentPost()),
    { timeout: 60000 },
  );
  await page.waitForSelector(
    '.editor-post-publish-button, .editor-post-publish-panel__toggle',
  );
  await dismissWelcomeGuide(page);
  await openMetaBoxesPanel(page);
}

export async function openNewPost(page) {
  await goToAdminPage(page, 'post-new.php');
  await waitForEditor(page);
}

export async function openPost(page, postId) {
  await goToAdminPage(page, `post.php?post=${postId}&action=edit`);
  await waitForEditor(page);
}

export async function setPostTitle(page, title) {
  const canvas = editorCanvas(page);
  // Older editors wrap the title textarea in a .editor-post-title element,
  // newer ones use a single element carrying both classes
  const input =
    (await canvas.$('.editor-post-title__input')) ||
    (await canvas.waitForSelector('.editor-post-title'));

  await input.click();
  await page.keyboard.type(title);
  await page.waitForFunction(
    (expected) =>
      window.wp.data.select('core/editor').getEditedPostAttribute('title') ===
      expected,
    { timeout: 10000 },
    title,
  );
}

export async function currentPostId(page) {
  return page.evaluate(() =>
    window.wp.data.select('core/editor').getCurrentPostId(),
  );
}

async function waitForSaveToFinish(page) {
  await waitFor(
    () =>
      page.evaluate(() => {
        const editor = window.wp.data.select('core/editor');
        const editPost = window.wp.data.select('core/edit-post');
        const savingMetaBoxes = editPost?.isSavingMetaBoxes
          ? editPost.isSavingMetaBoxes()
          : false;

        return (
          !editor.isSavingPost() &&
          !editor.isAutosavingPost() &&
          !savingMetaBoxes &&
          !editor.isEditedPostDirty()
        );
      }),
    { timeout: 60000, message: 'the post and meta boxes to finish saving' },
  );
}

async function waitForSnackbar(page, text) {
  await page.waitForFunction(
    (expected) =>
      Array.from(
        document.querySelectorAll('.components-snackbar__content'),
      ).some((el) => el.textContent.includes(expected)),
    { timeout: 60000 },
    text,
  );
}

/**
 * Publishes the post in the block editor and returns its id.
 */
export async function publishPost(page) {
  // Locators wait until the button is visible, enabled and has stopped
  // moving, which matters for the publish panel that slides in
  await page.locator('.editor-post-publish-panel__toggle').click();
  await page
    .locator('.editor-post-publish-panel .editor-post-publish-button')
    .click();
  await waitForSnackbar(page, 'Post published.');
  await waitForSaveToFinish(page);

  return currentPostId(page);
}

export async function updatePost(page) {
  await page.locator('.editor-post-publish-button').click();
  await waitForSnackbar(page, 'Post updated.');
  await waitForSaveToFinish(page);
}

/**
 * File names shown in the list view of the media library, which is the only
 * place cropped images are listed.
 */
export async function mediaLibraryFileNames(page) {
  await goToAdminPage(page, 'upload.php?mode=list');

  return page.$$eval('.wp-list-table .filename', (els) =>
    els.map((el) => el.textContent.replace('File name:', '').trim()),
  );
}
