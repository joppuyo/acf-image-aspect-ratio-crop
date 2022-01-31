<?php
class FrontEndCropPixelSizeCest
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
        $I->cli([
            'plugin',
            'install',
            __DIR__ . '/../_data/plugins/front-end-crop/front-end-crop.zip',
            '--force',
            '--activate',
        ]);
        $I->cli(['theme', 'install', 'twentytwenty', '--force', '--activate']);
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
                ? '.editor-post-title__input'
                : 'Enter title here',
            'Test Post'
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

    public function addImage(AcceptanceTester $I)
    {
        $I->amOnPage('test-post');
        $I->attachFile('.js-aiarc-upload', 'zoltan-kovacs-285132-unsplash.jpg');
        $I->waitForElementVisible('.js-acf-image-aspect-ratio-crop-modal', 60);
        $I->waitForElementVisible('.cropper-crop-box', 60);
        $I->click('.js-acf-image-aspect-ratio-crop-crop');
        $I->waitForElementNotVisible(
            '.js-acf-image-aspect-ratio-crop-modal',
            60
        );
        $I->executeJS(
            'document.querySelector(".acf-button").scrollIntoView({block: "center", inline: "center"});'
        );
        $I->waitForElementClickable('.acf-button');
        $I->click('Update');
        $I->waitForText('Post updated', 60);
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
        $I->amOnPage('test-post');
        $I->moveMouseOver(
            '.acf-field.acf-field-image-aspect-ratio-crop div img'
        );
        $I->click('.acf-icon.-cancel-custom ');
        $I->attachFile('.js-aiarc-upload', 'small.jpg');
        $I->retry(10, 1000);
        $I->retrySeeInPopup('Image too small');
        $I->acceptPopup();
    }
}
