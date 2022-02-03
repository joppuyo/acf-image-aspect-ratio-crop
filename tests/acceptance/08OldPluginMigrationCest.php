<?php

/*

Database contains site with following stuff:

* ACF 5.7.13
* Advanced Custom Fields: Image Crop Add-on 1.4.12
* Field group named Page with the crop field
* Field named image_crop created with Image Crop Add-on with the following attributes
    * Crop type: hard crop
    * Target size: custom size
    * Width: 854
    * Height: 480
    * Force crop: No
    * Preview size: medium
    * Save cropped image to media library: Yes
    * Retina/@2x mode: No
    * Return Value: image id
    * Library: all
* Image with the following attributes:
    * Name: example.jpg
    * Id: 8
    * Dimensions: 1080 × 810
* Image with the following attributes:
    * Name: example_854x480_acf_cropped.jpg
    * Id: 15
    * Dimensions: 854 × 480
* Page with the following attributes:
    * Name: Sample Page
    * Id: 2
    * The page has the cropped image attached to it

 */

class OldPluginMigrationCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function activateAcf(AcceptanceTester $I, $scenario)
    {
        $I->cleanUploadsDir();

        $I->cli([
            'db',
            'import',
            __DIR__ . '/../_data/old-plugin-migration/old-plugin-dump.sql',
            '--force',
        ]);

        $I->cli(['option', 'update', 'siteurl', $I->getConfigUrl()]);
        $I->cli(['option', 'update', 'home', $I->getConfigUrl()]);

        $I->cli([
            'plugin',
            'install',
            realpath(
                __DIR__ .
                    '/../_data/plugins/aiarc-acf-post-fields-debug/aiarc-acf-post-fields-debug.zip'
            ),
            '--force',
        ]);

        $from = realpath(__DIR__ . '/../_data/old-plugin-migration/uploads/');
        $to = realpath(__DIR__ . '/../../../../../wp-content');

        $I->runShellCommand("rsync -a $from $to");

        $I->cli(['core', 'update-db']);

        $I->loginAsAdmin();
        $I->amOnPluginsPage();
        $I->cli([
            'plugin',
            'install',
            __DIR__ .
            '/../_data/plugins/disable-welcome-messages-and-tips/disable-welcome-messages-and-tips.1.0.8.zip',
            '--force',
        ]);

        $I->activatePlugin('disable-welcome-messages-and-tips');
        $I->activatePlugin('advanced-custom-fields-pro');
        $I->activatePlugin('post-acf-fields-debug');
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

    public function changeFieldOptions(AcceptanceTester $I)
    {
        $I->loadSessionSnapshot('login');
        $I->amOnAdminPage('post.php?post=6&action=edit');
        $I->click('Image Crop');
        $I->waitForText('Field Type', 30);
        $I->scrollTo("//label[text()='Field Type']");
        $I->selectOption('Field Type', 'Image Aspect Ratio Crop');
        $I->waitForText('Width', 30);
        $I->fillField('Width', 16);
        $I->fillField('Height', 9);
        $I->scrollTo('#submitdiv');
        $I->waitForText('Update');
        $I->click('Update');
    }

    public function getFieldDataProgrammatically(AcceptanceTester $I)
    {
        $I->amOnPage('/sample-page/');
        $json_data = $I->grabValueFrom('#jsondata');
        $json_data = json_decode($json_data, true);

        \PHPUnit\Framework\Assert::assertIsArray($json_data['image_crop']);

        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['id'],
            15
        );
        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['title'],
            'example_854x480_acf_cropped'
        );
        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['width'],
            854
        );
        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['height'],
            480
        );

        \PHPUnit\Framework\Assert::assertNull(
            $json_data['image_crop']['original_image']
        );
    }

    public function resavePost(AcceptanceTester $I)
    {
        $I->loadSessionSnapshot('login');
        $I->amOnAdminPage('/post.php?post=2&action=edit');
        $I->waitForText('Update');
        $I->click('Update');
        $I->waitForText('Page updated', 120);
        $I->amOnPage('/sample-page/');
        $json_data = $I->grabValueFrom('#jsondata');
        $json_data = json_decode($json_data, true);

        \PHPUnit\Framework\Assert::assertIsArray($json_data['image_crop']);

        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['id'],
            15
        );
        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['title'],
            'example_854x480_acf_cropped'
        );
        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['width'],
            854
        );
        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['height'],
            480
        );

        \PHPUnit\Framework\Assert::assertIsArray(
            $json_data['image_crop']['original_image']
        );

        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['original_image']['id'],
            8
        );
        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['original_image']['title'],
            'example'
        );
        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['original_image']['width'],
            1080
        );
        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['original_image']['height'],
            810
        );
    }

    public function reCrop(AcceptanceTester $I)
    {
        $I->loadSessionSnapshot('login');
        $I->amOnAdminPage('/post.php?post=2&action=edit');
        $I->scrollTo('.acf-postbox');
        $I->waitForElementVisible(
            '.acf-image-uploader-aspect-ratio-crop div img',
            10
        );
        $I->moveMouseOver(
            '.acf-field.acf-field-image-aspect-ratio-crop div img'
        );
        $I->click('.acf-icon.-crop.dark');
        $I->waitForText('Crop image', 30);
        $I->executeJS(
            'window._acf_image_aspect_ratio_cropper.setData({x:0,y:0});'
        );
        $I->click('.js-acf-image-aspect-ratio-crop-crop');
        $I->waitForElementNotVisible(
            '.js-acf-image-aspect-ratio-crop-modal',
            60
        );
        $I->click('Update');
        $I->waitForText('Page updated', 120);
    }

    public function verifyReCroppedImage(AcceptanceTester $I)
    {
        $I->amOnPage('/sample-page/');
        $json_data = $I->grabValueFrom('#jsondata');
        $json_data = json_decode($json_data, true);

        \PHPUnit\Framework\Assert::assertIsArray($json_data['image_crop']);

        // Check cropped image

        \PHPUnit\Framework\Assert::assertNotEquals(
            $json_data['image_crop']['id'],
            15
        );
        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['title'],
            'example-aspect-ratio-16-9'
        );
        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['filename'],
            'example-aspect-ratio-16-9.jpg'
        );

        $I->assertEqualsWithDeltaCompat(
            $json_data['image_crop']['width'],
            1080,
            2
        );

        $I->assertEqualsWithDeltaCompat(
            $json_data['image_crop']['height'],
            608,
            2
        );

        // Check original image

        \PHPUnit\Framework\Assert::assertIsArray(
            $json_data['image_crop']['original_image']
        );

        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['original_image']['id'],
            8
        );
        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['original_image']['title'],
            'example'
        );
        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['original_image']['width'],
            1080
        );
        \PHPUnit\Framework\Assert::assertEquals(
            $json_data['image_crop']['original_image']['height'],
            810
        );
    }
}
