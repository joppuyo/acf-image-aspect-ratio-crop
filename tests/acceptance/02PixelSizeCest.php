<?php

class PixelSizeCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function activateAcf(AcceptanceTester $I)
    {
        $I->cleanUploadsDir();
        $I->importSqlDumpFile();
        $I->cli(['core', 'update-db']);
        $I->dontHavePostInDatabase([]);

        $I->cli([
            'plugin',
            'install',
            __DIR__ .
            '/../_data/plugins/disable-welcome-messages-and-tips/disable-welcome-messages-and-tips.1.0.8.zip',
            '--force',
        ]);

        $I->loginAsAdmin();
        $I->amOnPluginsPage();
        $I->activatePlugin('disable-welcome-messages-and-tips');
        $I->activatePlugin('advanced-custom-fields-pro');
        $I->saveSessionSnapshot('login');
    }

    /**
     * @depends activateAcf
     */
    public function activatePlugin(AcceptanceTester $I)
    {
        $I->loadSessionSnapshot('login');
        $I->amOnPluginsPage();
        $I->activatePlugin('acf-image-aspect-ratio-crop');
    }

    /**
     * @depends activatePlugin
     */
    public function createNewField(AcceptanceTester $I)
    {
        $I->loadSessionSnapshot('login');
        $I->createField($I, 'pixel_size', 640, 480);
    }

    public function createPost(AcceptanceTester $I)
    {
        global $wp_version;
        $I->loadSessionSnapshot('login');
        $I->amOnAdminPage('post-new.php');
        $I->fillField(
            version_compare($wp_version, '5.0', 'ge')
                ? '.editor-post-title__input'
                : 'Enter title here',
            'Test Post'
        );
        $I->scrollTo('.acf-field-image-aspect-ratio-crop');
        $I->click('Add Image');
        $I->attachFile(
            '.moxie-shim input',
            'zoltan-kovacs-285132-unsplash.jpg'
        );
        $I->waitForElementClickable(
            'div.media-toolbar-primary.search-form > button',
            60
        ); // secs
        $I->click('div.media-toolbar-primary.search-form > button');
        $I->waitForElementVisible('.js-acf-image-aspect-ratio-crop-modal', 60);
        $I->waitForElementVisible('.cropper-crop-box', 60);
        $I->click('.js-acf-image-aspect-ratio-crop-crop');
        $I->waitForElementNotVisible(
            '.js-acf-image-aspect-ratio-crop-modal',
            60
        );
        $I->verifyImage($I, 'cropped-pixel.jpg', 640, 480);
        $publish_text = 'Publish';
        if (version_compare($wp_version, '5', 'ge')) {
            $publish_text = 'Publish…';
        }
        if (version_compare($wp_version, '5.5', 'ge')) {
            $publish_text = 'Publish';
        }
        $I->click($publish_text);

        if (version_compare($wp_version, '5', 'ge')) {
            $I->waitForElementVisible('.editor-post-publish-button', 60);
            $I->click('.editor-post-publish-button');
        }

        $I->waitForText('Post published.', 120);
        $I->amOnAdminPage('edit.php');
        $I->see('Test Post');
    }

    public function checkImage(AcceptanceTester $I)
    {
        global $wp_version;
        $I->loadSessionSnapshot('login');
        $I->amOnAdminPage('edit.php');
        $I->click('Test Post');
        $I->verifyImage($I, 'cropped-pixel.jpg', 640, 480);
    }

    public function uploadTooSmallImage(AcceptanceTester $I)
    {
        global $wp_version;
        $I->loadSessionSnapshot('login');
        $I->amOnAdminPage('post-new.php');
        $I->fillField(
            version_compare($wp_version, '5.0', 'ge')
                ? '.editor-post-title__input'
                : 'Enter title here',
            'Test Post'
        );
        $I->scrollTo('.acf-field-image-aspect-ratio-crop');
        $I->click('Add Image');
        $I->attachFile('.moxie-shim input', 'small.jpg');
        $I->waitForText('Image width must be at least 640px.', 60);
        $I->see('Image height must be at least 480px.');
        $I->click('.media-modal-close');
        $publish_text = 'Publish';
        if (version_compare($wp_version, '5', 'ge')) {
            $publish_text = 'Publish…';
        }
        if (version_compare($wp_version, '5.5', 'ge')) {
            $publish_text = 'Publish';
        }

        $I->click($publish_text);

        if (version_compare($wp_version, '5', 'ge')) {
            $I->waitForElementVisible('.editor-post-publish-button', 60);
            $I->click('.editor-post-publish-button');
        }
        $I->waitForText('Post published.', 120);
    }
}
