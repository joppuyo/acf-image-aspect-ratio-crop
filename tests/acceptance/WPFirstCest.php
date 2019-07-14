<?php 

class WPFirstCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function activateAcf(AcceptanceTester $I)
    {
        $I->loginAsAdmin();
        $I->amOnPluginsPage();
        $I->activatePlugin('advanced-custom-fields-pro');
    }

    /**
     * @depends activateAcf
     */
    public function activatePlugin(AcceptanceTester $I)
    {
        $I->loginAsAdmin();
        $I->amOnPluginsPage();
        $I->activatePlugin('acf-image-aspect-ratio-crop');
    }

    /**
     * @depends activatePlugin
     */
    public function createNewField(AcceptanceTester $I)
    {
        $I->loginAsAdmin();
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
        $I->loginAsAdmin();
        $I->amOnAdminPage('post-new.php');
        $I->fillField("#post-title-0", "Test Post");
        $I->click('Add Image');
        $I->executeInSelenium(function(\Facebook\WebDriver\Remote\RemoteWebDriver $webDriver)
        {
            $webDriver->findElement(WebDriverBy::cssSelector('.moxie-shim input'))->sendKeys('/Users/joppuyo/Downloads/zoltan-kovacs-285132-unsplash.jpg');
        });
        $I->waitForElementClickable('div.media-toolbar-primary.search-form > button', 10); // secs
        $I->click('div.media-toolbar-primary.search-form > button');
        $I->waitForElementVisible('.js-acf-image-aspect-ratio-crop-modal', 10);
        $I->wait(5);
        $I->click('.js-acf-image-aspect-ratio-crop-crop');
        $I->waitForElementNotVisible('.js-acf-image-aspect-ratio-crop-modal', 10);
        $I->click('Publish…');
        $I->waitForElementVisible('.editor-post-publish-button', 10);
        $I->click('.editor-post-publish-button');
        $I->wait(10);
    }
}
