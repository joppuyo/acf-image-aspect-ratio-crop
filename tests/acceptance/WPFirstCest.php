<?php 

class WPFirstCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function activateAcf(AcceptanceTester $I)
    {
        $I->cleanUploadsDir();
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
        $I->wait(1);
        $I->fillField("Aspect Ratio Width", "16");
        $I->fillField("Aspect Ratio Height", "9");
        $I->scrollTo('#submitdiv');
        $I->click('Publish');
    }

    public function createPost(AcceptanceTester $I)
    {
        $I->loadSessionSnapshot('login');
        $I->amOnAdminPage('post-new.php');
        $I->fillField("#post-title-0", "Test Post");
        $I->click('Add Image');
        $I->attachFile('.moxie-shim input', 'zoltan-kovacs-285132-unsplash.jpg');
        $I->waitForElementClickable('div.media-toolbar-primary.search-form > button', 10); // secs
        $I->click('div.media-toolbar-primary.search-form > button');
        $I->waitForElementVisible('.js-acf-image-aspect-ratio-crop-modal', 10);
        $I->waitForElementVisible('.cropper-crop-box', 10);
        $I->click('.js-acf-image-aspect-ratio-crop-crop');
        $I->waitForElementNotVisible('.js-acf-image-aspect-ratio-crop-modal', 10);
        $this->verifyImage($I, 'cropped.jpg');
        $I->click('Publish…');
        $I->waitForElementVisible('.editor-post-publish-button', 10);
        $I->click('.editor-post-publish-button');
        $I->waitForText('Post published.');
        $I->amOnAdminPage('edit.php');
        $I->see('Test Post');
    }

    public function checkImage(AcceptanceTester $I)
    {
        $I->loadSessionSnapshot('login');
        $I->amOnAdminPage('edit.php');
        $I->click('Test Post');
        $this->verifyImage($I, 'cropped.jpg');
    }

    private function verifyImage(AcceptanceTester $I, $comparison_image)
    {
        $I->waitForElementVisible('.acf-field.acf-field-image-aspect-ratio-crop', 10);
        $I->moveMouseOver('.acf-field.acf-field-image-aspect-ratio-crop div img');
        $I->click('.acf-icon.-pencil.dark');
        $I->waitForJqueryAjax();
        $I->waitForElementVisible('label[data-setting="url"] input');
        $filename = $I->grabValueFrom('label[data-setting="url"] input');
        // Image path is sometimes thumbnail???
        $filename = str_replace('-300x169', '', $filename);

        codecept_debug($filename);
        PHPUnit_Framework_Assert::assertContains('-aspect-ratio-16x9', $filename);
        PHPUnit_Framework_Assert::assertEquals(
            md5(file_get_contents(__DIR__ . "../../_data/$comparison_image")),
            md5(file_get_contents($filename))
        );
        $I->click('button.media-modal-close');
    }

    public function updateImageFirst(AcceptanceTester $I) {
        $I->loadSessionSnapshot('login');
        $this->updateImage($I,
            'sylwia-pietruszka-nPCiBaK8WPk-unsplash.jpg',
            'cropped-2.jpg'
        );
        $I->amOnAdminPage('upload.php?mode=list');
        $I->see('zoltan-kovacs-285132-unsplash.jpg');
        $I->see('zoltan-kovacs-285132-unsplash-aspect-ratio-16x9.jpg');
        $I->see('sylwia-pietruszka-nPCiBaK8WPk-unsplash.jpg');
        $I->see('sylwia-pietruszka-nPCiBaK8WPk-unsplash-aspect-ratio-16x9.jpg');
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
        $I->loadSessionSnapshot('login');
        $this->updateImage($I,
            'jonas-morgner-sNoWQv4ts3I-unsplash.jpg',
            'cropped-3.jpg'
        );
        $I->amOnAdminPage('upload.php?mode=list');
        $I->see('jonas-morgner-sNoWQv4ts3I-unsplash.jpg');
        $I->see('jonas-morgner-sNoWQv4ts3I-unsplash-aspect-ratio-16x9.jpg');
        $I->see('zoltan-kovacs-285132-unsplash.jpg');
        $I->dontSee('zoltan-kovacs-285132-unsplash-aspect-ratio-16x9.jpg');
        $I->see('sylwia-pietruszka-nPCiBaK8WPk-unsplash.jpg');
        $I->dontSee('sylwia-pietruszka-nPCiBaK8WPk-unsplash-aspect-ratio-16x9.jpg');
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
        $I->waitForElementVisible('.editor-post-publish-button', 10);
        $I->click('.editor-post-publish-button');
        $I->waitForText('Post updated.');
    }
}
