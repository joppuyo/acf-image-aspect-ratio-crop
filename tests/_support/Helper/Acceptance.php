<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use AcceptanceTester;

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
        if (version_compare(PHP_VERSION, '7.1.0', '>=')) {
            $this->assertStringContainsString(
                "-aspect-ratio-$width-$height",
                $url
            );
        } else {
            $this->assertTrue(
                strpos($url, "-aspect-ratio-$width-$height") !== false
            );
        }

        $image_1_size = getimagesize(
            __DIR__ . "../../../_data/$comparison_image"
        );
        $image_2_size = getimagesize($url);

        $this->assertEqualsWithDeltaCompat(
            $image_1_size[0],
            $image_2_size[0],
            2
        );
        $this->assertEqualsWithDeltaCompat(
            $image_1_size[1],
            $image_2_size[1],
            2
        );
        $I->click('button.media-modal-close');
    }

    public function assertEqualsWithDeltaCompat($expected, $actual, $delta)
    {
        if (version_compare(PHP_VERSION, '7.1.0', '>=')) {
            $this->assertEqualsWithDelta($expected, $actual, $delta);
        } elseif (
            version_compare(PHP_VERSION, '7.0.0', '>=') &&
            version_compare(PHP_VERSION, '7.1.0', '<')
        ) {
            \PHPUnit\Framework\Assert::assertEquals(
                $expected,
                $actual,
                '',
                $delta
            );
        } else {
            \PHPUnit_Framework_Assert::assertEquals(
                $expected,
                $actual,
                '',
                $delta
            );
        }
    }
}
