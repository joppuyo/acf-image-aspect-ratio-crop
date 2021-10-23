<?php

use Facebook\WebDriver\WebDriverKeys;

class PolylangCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function activateAcf(AcceptanceTester $I, $scenario)
    {
        global $wp_version;
        if (version_compare($wp_version, '5.2', 'lt')) {
            $scenario->skip('Polylang 2.9 only supports WordPress 5.2 and up');
        }

        $I->cleanUploadsDir();

        $I->cli([
            'db',
            'import',
            __DIR__ . '/../_data/dump-polylang.sql',
            '--force',
        ]);

        $I->cli(['core', 'update-db']);
        $I->dontHavePostInDatabase([]);
        $I->loginAsAdmin();
        $I->amOnPluginsPage();
        $I->cli([
            'plugin',
            'install',
            __DIR__ .
            '/../_data/plugins/disable-welcome-messages-and-tips/disable-welcome-messages-and-tips.1.0.8.zip',
            '--force',
        ]);
        $I->cli([
            'plugin',
            'install',
            __DIR__ . '/../_data/polylang-pro.2.9.1.zip',
            '--force',
        ]);
        $I->activatePlugin('disable-welcome-messages-and-tips');
        $I->activatePlugin('advanced-custom-fields-pro');
        $I->activatePlugin('polylang-pro');
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
    public function enableTranslation(AcceptanceTester $I)
    {
        $I->loadSessionSnapshot('login');
        $I->amOnAdminPage('admin.php?page=mlang_settings');
        //$I->waitForElementVisible('#pll-module-advanced_media .configure a');
        $I->click('#pll-module-advanced_media .configure a');
        $I->click('#duplicate-media');
        $I->click('#pll-configure-advanced_media .button.button-primary.save');
        $I->waitForText('Settings saved');
    }

    /**
     * @depends enableTranslation
     */
    public function createAttachment(AcceptanceTester $I)
    {
        $I->loadSessionSnapshot('login');
        $I->amOnAdminPage('media-new.php?lang=en');
        $I->click('browser uploader');
        $I->attachFile('#async-upload', 'zoltan-kovacs-285132-unsplash.jpg');
        $I->click('Upload');
        $I->waitForText('Media Library', 60);

        // EN
        $I->waitForElementVisible('.attachment-preview', 30);
        $I->click('.attachment-preview');
        $I->waitForElementVisible('label[data-setting="caption"] textarea', 30);
        $I->fillField(
            'label[data-setting="caption"] textarea',
            'Caption English'
        );
        $I->pressKey(
            'label[data-setting="caption"] textarea',
            WebDriverKeys::TAB
        );
        $I->waitForText('Saved');

        //FI
        $I->amOnAdminPage('upload.php?lang=fi');
        $I->waitForElementVisible('.attachment-preview', 30);
        $I->click('.attachment-preview');
        $I->waitForElementVisible('label[data-setting="caption"] textarea', 30);
        $I->fillField(
            'label[data-setting="caption"] textarea',
            'Caption Finnish'
        );
        $I->pressKey(
            'label[data-setting="caption"] textarea',
            WebDriverKeys::TAB
        );
        $I->waitForText('Saved');

        //SV
        $I->amOnAdminPage('upload.php?lang=sv');
        $I->waitForElementVisible('.attachment-preview', 30);
        $I->click('.attachment-preview');
        $I->waitForElementVisible('label[data-setting="caption"] textarea', 30);
        $I->fillField(
            'label[data-setting="caption"] textarea',
            'Caption Swedish'
        );
        $I->pressKey(
            'label[data-setting="caption"] textarea',
            WebDriverKeys::TAB
        );
        $I->waitForText('Saved');
    }

    public function createField(AcceptanceTester $I)
    {
        $I->loadSessionSnapshot('login');
        $I->createField($I, 'aspect_ratio', 16, 9);
        $I->wait(10);
    }
}
