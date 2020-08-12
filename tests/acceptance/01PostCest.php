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

        if (getenv('WP_VERSION')) {
            $I->cli(['core', 'update', '--version=' . getenv('WP_VERSION'), '--force']);
            $I->wait(10);
        }

        $I->cli(['core', 'update-db']);
        $I->cli(['plugin', 'install', getenv('ACF_ZIP_URL'), '--force']);
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
        $I->click('#acf-field-group-wrap > a');
        $I->fillField("#title", "Post");
        $I->click('#acf-field-group-fields > div > div > ul.acf-hl.acf-tfoot > li > a');
        $I->fillField("Field Label", "Crop Image");
        $I->selectOption('Field Type', 'Image Aspect Ratio Crop');
        $I->waitForText("Width");
        $I->fillField("Width", "16");
        $I->fillField("Height", "9");
        $I->scrollTo('#submitdiv');
        $I->click('Publish');
    }

    public function createPost(AcceptanceTester $I)
    {
        global $wp_version;
        $I->loadSessionSnapshot('login');
        $I->amOnAdminPage('post-new.php');
        $I->fillField(version_compare($wp_version, '5.0', 'ge') ? "Add title" : "Enter title here", "Test Post");
        $I->click('Add Image');
        $I->attachFile('.moxie-shim input', 'zoltan-kovacs-285132-unsplash.jpg');
        $I->waitForElementClickable('div.media-toolbar-primary.search-form > button', 30); // secs
        $I->click('div.media-toolbar-primary.search-form > button');
        $I->waitForElementVisible('.js-acf-image-aspect-ratio-crop-modal', 10);
        $I->waitForElementVisible('.cropper-crop-box', 10);
        $I->click('.js-acf-image-aspect-ratio-crop-crop');
        $I->waitForElementNotVisible('.js-acf-image-aspect-ratio-crop-modal', 10);
        $this->verifyImage($I, version_compare($wp_version, '5.3', 'ge') ? 'cropped-scaled.jpg' : 'cropped.jpg');
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
        $this->verifyImage($I, version_compare($wp_version, '5.3', 'ge') ? 'cropped-scaled.jpg' : 'cropped.jpg');
    }

    private function verifyImage(AcceptanceTester $I, $comparison_image)
    {
        $I->waitForElementVisible('.acf-image-uploader-aspect-ratio-crop div img', 10);
        $I->moveMouseOver('.acf-field.acf-field-image-aspect-ratio-crop div img');
        $I->click('.acf-icon.-pencil.dark');
        $I->waitForJqueryAjax();
        // This changed in WP 5.3
        try {
            $I->waitForElementVisible('#attachment-details-copy-link');
            $url = $I->grabValueFrom('#attachment-details-copy-link');
        } catch (Exception $exception) {
            $I->waitForElementVisible('label[data-setting="url"] input');
            $url = $I->grabValueFrom('label[data-setting="url"] input');
        }
        // Image path is sometimes thumbnail???

        $filename = $I->grabTextFrom('.filename');

        $url = explode('/', $url);
        array_pop($url);
        array_push($url, $filename);
        $url = implode('/', $url);

        codecept_debug($filename);
        PHPUnit_Framework_Assert::assertContains('-aspect-ratio-16-9', $url);
        PHPUnit_Framework_Assert::assertEquals(
            json_encode(getimagesize(__DIR__ . "../../_data/$comparison_image")),
            json_encode(getimagesize($url))
        );
        $I->click('button.media-modal-close');
    }

    public function updateImageFirst(AcceptanceTester $I) {
        global $wp_version;
        $I->loadSessionSnapshot('login');
        $this->updateImage($I,
            'sylwia-pietruszka-nPCiBaK8WPk-unsplash.jpg',
            version_compare($wp_version, '5.3', 'ge') ? 'cropped-2-scaled.jpg' : 'cropped-2.jpg'
        );
        $I->wait(10);
        $I->amOnAdminPage('upload.php?mode=list');

        $extra = version_compare($wp_version, '5.3', 'ge') ? '-scaled' : '';

        $I->see("zoltan-kovacs-285132-unsplash$extra.jpg");
        $I->see("zoltan-kovacs-285132-unsplash$extra-aspect-ratio-16-9$extra.jpg");
        $I->see("sylwia-pietruszka-nPCiBaK8WPk-unsplash$extra.jpg");
        $I->see("sylwia-pietruszka-nPCiBaK8WPk-unsplash$extra-aspect-ratio-16-9$extra.jpg");
    }

    public function enableUnusedImageDeletion(AcceptanceTester $I) {
        $I->loadSessionSnapshot('login');
        $I->amOnPluginsPage();
        $I->click('a[href="options-general.php?page=acf-image-aspect-ratio-crop"]');
        $I->see('Delete unused cropped images');
        $I->click('#delete_unused_true');
        $I->click('Save');
    }

    public function updateImageSecond(AcceptanceTester $I) {
        global $wp_version;
        $I->loadSessionSnapshot('login');
        $this->updateImage($I,
            'jonas-morgner-sNoWQv4ts3I-unsplash.jpg',
            version_compare($wp_version, '5.3', 'ge') ? 'cropped-3-scaled.jpg' : 'cropped-3.jpg'
        );
        $I->wait(10);
        $I->amOnAdminPage('upload.php?mode=list');

        $extra = version_compare($wp_version, '5.3', 'ge') ? '-scaled' : '';

        $I->see("jonas-morgner-sNoWQv4ts3I-unsplash$extra.jpg");
        $I->see("jonas-morgner-sNoWQv4ts3I-unsplash$extra-aspect-ratio-16-9$extra.jpg");
        $I->see("zoltan-kovacs-285132-unsplash$extra.jpg");
        $I->dontSee("zoltan-kovacs-285132-unsplash$extra-aspect-ratio-16-9$extra.jpg");
        $I->see("sylwia-pietruszka-nPCiBaK8WPk-unsplash$extra.jpg");
        $I->dontSee("sylwia-pietruszka-nPCiBaK8WPk-unsplash$extra-aspect-ratio-16-9$extra.jpg");
    }

    private function updateImage(AcceptanceTester $I, $image_path, $verify_path) {
        $I->amOnAdminPage('edit.php');
        $I->click('Test Post');
        $I->moveMouseOver('.acf-field.acf-field-image-aspect-ratio-crop div img');
        $I->click('.acf-icon.-cancel.dark');
        $I->click('Add Image');
        $I->attachFile('.moxie-shim input', $image_path);
        $I->waitForElementClickable('div.media-toolbar-primary.search-form > button', 10); // secs
        $I->click('div.media-toolbar-primary.search-form > button');
        $I->waitForElementVisible('.js-acf-image-aspect-ratio-crop-modal', 10);
        $I->waitForElementVisible('.cropper-crop-box', 10);
        $I->click('.js-acf-image-aspect-ratio-crop-crop');
        $I->waitForElementNotVisible('.js-acf-image-aspect-ratio-crop-modal', 10);
        $this->verifyImage($I, $verify_path);
        $I->click('Update');
        global $wp_version;
        if (version_compare($wp_version, '5', 'ge')) {
            $I->waitForElementVisible('.editor-post-publish-button', 10);
            $I->click('.editor-post-publish-button');
        }
        $I->waitForText('Post updated.');
    }
}
