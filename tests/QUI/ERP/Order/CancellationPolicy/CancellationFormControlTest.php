<?php

namespace QUITests\ERP\Order\CancellationPolicy;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Order\CancellationPolicy\Controls\CancellationForm;

class CancellationFormControlTest extends TestCase
{
    private mixed $originalFrontendConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $Config = QUI::getPackage('quiqqer/order-cancellation-policy')->getConfig();
        self::assertNotNull($Config);
        $this->originalFrontendConfig = $Config->getSection('frontend');
        $Config->setValue('frontend', 'useCaptcha', 0);
        $Config->save();
    }

    protected function tearDown(): void
    {
        $Config = QUI::getPackage('quiqqer/order-cancellation-policy')->getConfig();

        if ($Config) {
            $Config->setSection(
                'frontend',
                is_array($this->originalFrontendConfig) ? $this->originalFrontendConfig : []
            );
            $Config->save();
        }

        unset($_GET['orderNo'], $_GET['orderDate']);
        parent::tearDown();
    }

    public function testRendersPrefilledCancellationForm(): void
    {
        $_GET['orderNo'] = 'ORDER-2026-42';
        $_GET['orderDate'] = '2026-07-15 10:30:00';
        $html = (new CancellationForm())->getBody();

        self::assertStringContainsString('name="orderNo"', $html);
        self::assertStringContainsString('value="ORDER-2026-42"', $html);
        self::assertStringContainsString('value="2026-07-15"', $html);
        self::assertStringContainsString('readonly', $html);
    }

    public function testRendersSuccessContentOnly(): void
    {
        $html = (new CancellationForm([
            'mode' => 'success',
            'successText' => '<p>PHPUnit cancellation accepted</p>'
        ]))->renderContentOnly();

        self::assertStringContainsString('PHPUnit cancellation accepted', $html);
        self::assertStringNotContainsString(
            'quiqqer-order-cancellation-policy-cancellationForm__successTitle',
            $html
        );
        self::assertStringNotContainsString(
            'quiqqer-order-cancellation-policy-cancellationForm__successHint',
            $html
        );
        self::assertStringNotContainsString('name="firstName"', $html);
    }

    public function testEscapesPrefilledOrderNumberInHtmlAttribute(): void
    {
        $_GET['orderNo'] = '"><script>alert(1)</script>';

        $html = (new CancellationForm())->getBody();

        self::assertStringContainsString(
            'value="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"',
            $html
        );
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
    }
}
