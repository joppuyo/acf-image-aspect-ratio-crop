<?php

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
        $I->cli(['core', 'update-db']);
        $I->dontHavePostInDatabase([]);
        $I->loginAsAdmin();
        $I->amOnPluginsPage();
        $I->cli([
            'plugin',
            'install',
            __DIR__ . '/../_data/polylang-pro.2.9.1.zip',
            '--force',
        ]);
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
    public function setUpPolylang(AcceptanceTester $I)
    {
        $I->loadSessionSnapshot('login');
        $I->amOnPluginsPage();
        $I->click('Run the Setup Wizard');
        $I->click('Continue');
        $I->click('.ui-selectmenu-button');
        $I->pressKey('.ui-selectmenu-button', 'e');
        $I->click('#ui-id-27');
        $I->click('Add new language');
        $I->wait(1);
        $I->click('.ui-selectmenu-button');
        $I->wait(1);
        $I->pressKey('.ui-selectmenu-button', 's');
        $I->wait(1);
        $I->click('#ui-id-172');
        $I->click('Add new language');
        $I->click('Continue');
        $I->click('.pll-wizard-service-toggle');
        $I->click('Continue');
        $I->click('Return to the Dashboard');
        $I->wait(1);
    }

    /**
     * @depends setUpPolylang
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
}
