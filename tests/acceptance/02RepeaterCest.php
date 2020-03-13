<?php

use Codeception\Module\WebDriver;

class RepeaterCest
{


    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function activateAcf(AcceptanceTester $I)
    {
        $I->cleanUploadsDir();
        $I->loginAsAdmin();
        //$this->setUpWpCli();
        $I->cli(['plugin', 'install', getenv('ACF_ZIP_URL'), '--force']);
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
        $I->fillField("Field Label", "Repeater");
        $I->selectOption('Field Type', 'Repeater');
        $I->wait(2);
        //$I->click('.acf-field.acf-field-setting-sub_fields .add-field');
        $I->performOn('.acf-field.acf-field-setting-sub_fields', function (WebDriver $I) {
            $I->click('.add-field');
            $I->fillField("//*[contains(@class, 'acf-field-setting-sub_fields')]//label[contains(.,'Field Label')]/following::input[1]", "Image Crop");
            $I->selectOption("//*[contains(@class, 'acf-field-setting-sub_fields')]//label[contains(.,'Field Type')]/following::select[1]", "Image Aspect Ratio Crop");
            //$I->selectOption('Field Type', 'Image Aspect Ratio Crop');
            $I->wait(1);
            $I->fillField("Aspect Ratio Width", "16");
            $I->fillField("Aspect Ratio Height", "9");
        });

        $I->scrollTo('#submitdiv');
        $I->click('Publish');
    }
}
