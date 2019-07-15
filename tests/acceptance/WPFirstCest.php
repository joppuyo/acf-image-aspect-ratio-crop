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
        $I->executeInSelenium(function(\Facebook\WebDriver\Remote\RemoteWebDriver $webDriver)
        {
            $webDriver->findElement(WebDriverBy::cssSelector('.moxie-shim input'))->sendKeys('/Users/joppuyo/Downloads/zoltan-kovacs-285132-unsplash.jpg');
        });
        $I->waitForElementClickable('div.media-toolbar-primary.search-form > button', 10); // secs
        $I->click('div.media-toolbar-primary.search-form > button');
        $I->waitForElementVisible('.js-acf-image-aspect-ratio-crop-modal', 10);
        $I->waitForElementVisible('.cropper-crop-box', 10);
        $I->click('.js-acf-image-aspect-ratio-crop-crop');
        $I->waitForElementNotVisible('.js-acf-image-aspect-ratio-crop-modal', 10);
        $this->verifyImage($I);
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
        $this->verifyImage($I);
    }

    private function verifyImage(AcceptanceTester $I)
    {
        $I->waitForElementVisible('.acf-field.acf-field-image-aspect-ratio-crop', 10);
        $I->moveMouseOver('.acf-field.acf-field-image-aspect-ratio-crop div img');
        $I->click('.acf-icon.-pencil.dark');
        $I->waitForJqueryAjax();
        $I->waitForElementVisible('label[data-setting="url"] input');
        $filename = $I->grabValueFrom('label[data-setting="url"] input');
        PHPUnit_Framework_Assert::assertContains('-aspect-ratio-16x9', $filename);
        PHPUnit_Framework_Assert::assertEquals(
            md5(file_get_contents(__DIR__ . '../../_data/cropped.jpg')),
            md5(file_get_contents($filename))
        );
        $I->click('button.media-modal-close');
    }
}
