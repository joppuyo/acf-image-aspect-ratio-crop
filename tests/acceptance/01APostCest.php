<?php

class PostCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function activateAcf(AcceptanceTester $I)
    {
        // https://github.com/Codeception/Codeception/issues/4765
        $I->amOnPage('/');
        $I->cleanUploadsDir();
        $I->cli(['core', 'update-db']);
        $I->cli(['rewrite', 'flush']);

        if (getenv('ACF_VERSION')) {
            $acf_version = getenv('ACF_VERSION');
            $I->cli([
                'plugin',
                'install',
                __DIR__ .
                "/../_data/plugins/acf/advanced-custom-fields-pro.$acf_version.zip",
                '--force',
            ]);
        }
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
        $I->createField($I, 'aspect_ratio', 16, 9);
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
        //$I->wait(10);
        $I->amOnAdminPage('upload.php?mode=list');

        $extra = version_compare($wp_version, '5.3', 'ge') ? '-scaled' : '';

        $I->waitForJS('return document.readyState == "complete"', 60);

        $I->see("zoltan-kovacs-285132-unsplash$extra.jpg");
        $I->see(
            "zoltan-kovacs-285132-unsplash$extra-aspect-ratio-16-9$extra.jpg"
        );
        $I->see("sylwia-pietruszka-nPCiBaK8WPk-unsplash$extra.jpg");
        $I->see(
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
        $I->click('.acf-icon.-cancel-custom');
        $I->click('Add Image');
        $I->attachFile('.moxie-shim input', $image_path);
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
        $I->verifyImage($I, $verify_path, 16, 9);
        $I->click('Update');
        global $wp_version;
        if (version_compare($wp_version, '5', 'ge')) {
            $I->waitForElementVisible('.editor-post-publish-button', 60);
            $I->click('.editor-post-publish-button');
        }
        $I->waitForText('Post updated.', 120);
    }
}
