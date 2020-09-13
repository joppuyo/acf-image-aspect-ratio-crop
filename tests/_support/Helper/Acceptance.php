<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use AcceptanceTester;
use PHPUnit_Framework_Assert;

class Acceptance extends \Codeception\Module
{
    function verifyImage(
        AcceptanceTester $I,
        $comparison_image,
        $width,
        $height
    ) {
        global $wp_version;
        $I->waitForElementVisible(
            '.acf-image-uploader-aspect-ratio-crop div img',
            10
        );
        $I->moveMouseOver(
            '.acf-field.acf-field-image-aspect-ratio-crop div img'
        );
        $I->click('.acf-icon.-pencil.dark');
        $I->waitForJqueryAjax();
        $url = null;
        if (version_compare($wp_version, '5.3', 'ge')) {
            $I->waitForElementVisible('#attachment-details-copy-link');
            $url = $I->grabValueFrom('#attachment-details-copy-link');
        } else {
            $I->waitForElementVisible('label[data-setting="url"] input');
            $url = $I->grabValueFrom('label[data-setting="url"] input');
        }
        // Image path is sometimes thumbnail???

        $filename = $I->grabTextFrom('.filename');

        $url = explode('/', $url);
        array_pop($url);
        array_push($url, $filename);
        $url = implode('/', $url);

        codecept_debug($filename);
        PHPUnit_Framework_Assert::assertContains(
            "-aspect-ratio-$width-$height",
            $url
        );
        PHPUnit_Framework_Assert::assertEquals(
            json_encode(
                getimagesize(__DIR__ . "../../../_data/$comparison_image")
            ),
            json_encode(getimagesize($url))
        );
        $I->click('button.media-modal-close');
    }
}
