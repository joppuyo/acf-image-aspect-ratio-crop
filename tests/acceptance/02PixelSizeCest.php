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
        $I->cli(['core', 'update-db']);
        $I->dontHavePostInDatabase([]);
        $I->loginAsAdmin();
        $I->amOnPluginsPage();
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
        $I->amOnAdminPage('edit.php?post_type=acf-field-group');
        $I->wait(1);
        $I->click('a.page-title-action');
        $I->fillField('#title', 'Post');
        $I->click(
            '#acf-field-group-fields > div > div > ul.acf-hl.acf-tfoot > li > a'
        );
        $I->fillField('Field Label', 'Crop Image');
        $I->selectOption('Field Type', 'Image Aspect Ratio Crop');
        $I->waitForText('Width');
        $I->selectOption('Crop type', 'Pixel size');
        $I->fillField('Width', '640');
        $I->fillField('Height', '480');
        $I->scrollTo('#submitdiv');
        $I->click('Publish');
    }

    public function createPost(AcceptanceTester $I)
    {
        global $wp_version;
        $I->loadSessionSnapshot('login');
        $I->amOnAdminPage('post-new.php');
        $I->fillField(
            version_compare($wp_version, '5.0', 'ge')
                ? 'Add title'
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
            30
        ); // secs
        $I->click('div.media-toolbar-primary.search-form > button');
        $I->waitForElementVisible('.js-acf-image-aspect-ratio-crop-modal', 10);
        $I->waitForElementVisible('.cropper-crop-box', 10);
        $I->click('.js-acf-image-aspect-ratio-crop-crop');
        $I->waitForElementNotVisible(
            '.js-acf-image-aspect-ratio-crop-modal',
            10
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
            $I->waitForElementVisible('.editor-post-publish-button', 10);
            $I->click('.editor-post-publish-button');
        }

        $I->waitForText('Post published.');
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
                ? 'Add title'
                : 'Enter title here',
            'Test Post'
        );
        $I->scrollTo('.acf-field-image-aspect-ratio-crop');
        $I->click('Add Image');
        $I->attachFile('.moxie-shim input', 'small.jpg');
        $I->waitForText('Image width must be at least 640px.');
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
            $I->waitForElementVisible('.editor-post-publish-button', 10);
            $I->click('.editor-post-publish-button');
        }
        $I->waitForText('Post published.');
    }
}
