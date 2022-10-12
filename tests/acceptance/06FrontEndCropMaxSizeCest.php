<?php
class FrontEndCropMaxSizeCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function activateAcf(AcceptanceTester $I, $scenario)
    {
        $scenario->skip('Temporarily skipping this.');
        global $wp_version;

        $I->cleanUploadsDir();
        $I->importSqlDumpFile();
        $I->cli(['core', 'update-db']);
        $I->runShellCommand(
            'cd tests/_data/plugins/front-end-crop && zip -r front-end-crop.zip . -x "*.DS_Store"'
        );
        $I->cli([
            'plugin',
            'install',
            __DIR__ . '/../_data/plugins/front-end-crop/front-end-crop.zip',
            '--force',
            '--activate',
        ]);

        $I->cli([
            'plugin',
            'install',
            __DIR__ .
            '/../_data/plugins/disable-welcome-messages-and-tips/disable-welcome-messages-and-tips.1.0.8.zip',
            '--force',
        ]);

        $I->cli(['theme', 'install', 'twentytwenty', '--force', '--activate']);
        $I->dontHavePostInDatabase([]);
        $I->loginAsAdmin(20, 10);
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
        $I->createField($I, 'aspect_ratio', 16, 9, 640);
    }

    /**
     * @depends createNewField
     */
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

    /**
     * @depends createPost
     */
    public function addImage(AcceptanceTester $I)
    {
        $I->amOnPage('test-post');
        $I->attachFile('.js-aiarc-upload', 'zoltan-kovacs-285132-unsplash.jpg');
        $I->waitForElementVisible('.js-acf-image-aspect-ratio-crop-modal', 120);
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

    /**
     * @depends addImage
     */
    public function checkImage(AcceptanceTester $I)
    {
        global $wp_version;
        $I->loadSessionSnapshot('login');
        $I->amOnAdminPage('edit.php');
        $I->click('Test Post');
        $I->verifyImage($I, 'cropped-max-width.jpg', 16, 9);
    }
}
