<?php
class MaxSizeCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function activateAcf(AcceptanceTester $I, $scenario)
    {
        global $wp_version;

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
        //$I->wait(1);
        $I->click('a.page-title-action');
        $I->fillField('#title', 'Post');
        $I->click(
            '#acf-field-group-fields > div > div > ul.acf-hl.acf-tfoot > li > a'
        );
        $I->fillField('Field Label', 'Crop Image');
        $I->selectOption('Field Type', 'Image Aspect Ratio Crop');
        $I->waitForText('Width', 60);
        $I->fillField('Width', '16');
        $I->fillField('Height', '9');
        //$I->seeElement(['class' => 'js-min-width']);
        $I->fillField(['class' => 'js-max-width'], '640');
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
        $I->verifyImage($I, 'cropped-max-width.jpg', 16, 9);
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
        $I->verifyImage($I, 'cropped-max-width.jpg', 16, 9);
    }
}
