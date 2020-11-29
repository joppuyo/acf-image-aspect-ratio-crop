<?php

class PostCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function activateAcf(AcceptanceTester $I)
    {
        $I->cleanUploadsDir();
        $I->cli(['core', 'update-db']);
        $I->cli(['rewrite', 'flush']);

        if (getenv('ACF_VERSION')) {
            $acf_version = getenv('ACF_VERSION');
            $I->cli([
                'plugin',
                'install',
                __DIR__ .
                "/../_data/advanced-custom-fields-pro.$acf_version.zip",
                '--force',
            ]);
        }

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
        $I->fillField('Width', '16');
        $I->fillField('Height', '9');
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
        $I->verifyImage(
            $I,
            version_compare($wp_version, '5.3', 'ge')
                ? 'cropped-scaled.jpg'
                : 'cropped.jpg',
            16,
            9
        );
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
        $I->verifyImage(
            $I,
            version_compare($wp_version, '5.3', 'ge')
                ? 'cropped-scaled.jpg'
                : 'cropped.jpg',
            16,
            9
        );
    }

    public function updateImageFirst(AcceptanceTester $I)
    {
        global $wp_version;
        $I->loadSessionSnapshot('login');
        $this->updateImage(
            $I,
            'sylwia-pietruszka-nPCiBaK8WPk-unsplash.jpg',
            version_compare($wp_version, '5.3', 'ge')
                ? 'cropped-2-scaled.jpg'
                : 'cropped-2.jpg'
        );
        $I->wait(10);
        $I->amOnAdminPage('upload.php?mode=list');

        $extra = version_compare($wp_version, '5.3', 'ge') ? '-scaled' : '';

        $I->see("zoltan-kovacs-285132-unsplash$extra.jpg");
        $I->see(
            "zoltan-kovacs-285132-unsplash$extra-aspect-ratio-16-9$extra.jpg"
        );
        $I->see("sylwia-pietruszka-nPCiBaK8WPk-unsplash$extra.jpg");
        $I->see(
            "sylwia-pietruszka-nPCiBaK8WPk-unsplash$extra-aspect-ratio-16-9$extra.jpg"
        );
    }

    public function enableUnusedImageDeletion(AcceptanceTester $I)
    {
        $I->loadSessionSnapshot('login');
        $I->amOnPluginsPage();
        $I->click(
            'a[href="options-general.php?page=acf-image-aspect-ratio-crop"]'
        );
        $I->see('Delete unused cropped images');
        $I->click('#delete_unused_true');
        $I->click('Save');
    }

    public function updateImageSecond(AcceptanceTester $I)
    {
        global $wp_version;
        $I->loadSessionSnapshot('login');
        $this->updateImage(
            $I,
            'jonas-morgner-sNoWQv4ts3I-unsplash.jpg',
            version_compare($wp_version, '5.3', 'ge')
                ? 'cropped-3-scaled.jpg'
                : 'cropped-3.jpg'
        );
        $I->wait(10);
        $I->amOnAdminPage('upload.php?mode=list');

        $extra = version_compare($wp_version, '5.3', 'ge') ? '-scaled' : '';

        $I->see("jonas-morgner-sNoWQv4ts3I-unsplash$extra.jpg");
        $I->see(
            "jonas-morgner-sNoWQv4ts3I-unsplash$extra-aspect-ratio-16-9$extra.jpg"
        );
        $I->see("zoltan-kovacs-285132-unsplash$extra.jpg");
        $I->dontSee(
            "zoltan-kovacs-285132-unsplash$extra-aspect-ratio-16-9$extra.jpg"
        );
        $I->see("sylwia-pietruszka-nPCiBaK8WPk-unsplash$extra.jpg");
        $I->dontSee(
            "sylwia-pietruszka-nPCiBaK8WPk-unsplash$extra-aspect-ratio-16-9$extra.jpg"
        );
    }

    private function updateImage(AcceptanceTester $I, $image_path, $verify_path)
    {
        $I->amOnAdminPage('edit.php');
        $I->click('Test Post');
        $I->moveMouseOver(
            '.acf-field.acf-field-image-aspect-ratio-crop div img'
        );
        $I->click('.acf-icon.-cancel.dark');
        $I->click('Add Image');
        $I->attachFile('.moxie-shim input', $image_path);
        $I->waitForElementClickable(
            'div.media-toolbar-primary.search-form > button',
            10
        ); // secs
        $I->click('div.media-toolbar-primary.search-form > button');
        $I->waitForElementVisible('.js-acf-image-aspect-ratio-crop-modal', 10);
        $I->waitForElementVisible('.cropper-crop-box', 10);
        $I->click('.js-acf-image-aspect-ratio-crop-crop');
        $I->waitForElementNotVisible(
            '.js-acf-image-aspect-ratio-crop-modal',
            10
        );
        $I->verifyImage($I, $verify_path, 16, 9);
        $I->click('Update');
        global $wp_version;
        if (version_compare($wp_version, '5', 'ge')) {
            $I->waitForElementVisible('.editor-post-publish-button', 10);
            $I->click('.editor-post-publish-button');
        }
        $I->waitForText('Post updated.');
    }
}
