<?php

namespace QUITests\ERP\Order\CancellationPolicy;

use PHPUnit\Framework\TestCase;
use QUI\ERP\Order\CancellationPolicy\CancellationFormHelper;

class CancellationFormHelperUnitTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_GET['orderNo'], $_GET['orderDate']);
    }

    public function testGetPrefilledOrderNoFromRequestReturnsTrimmedValue(): void
    {
        $_GET['orderNo'] = ' 2025-17 ';

        $this->assertSame('2025-17', CancellationFormHelper::getPrefilledOrderNoFromRequest());
    }

    public function testGetPrefilledOrderNoFromRequestReturnsEmptyStringForMissingValue(): void
    {
        $this->assertSame('', CancellationFormHelper::getPrefilledOrderNoFromRequest());
    }

    public function testNormalizeOrderDateForRequestReturnsBrowserCompatibleDate(): void
    {
        $this->assertSame(
            '2026-06-15',
            CancellationFormHelper::normalizeOrderDateForRequest('2026-06-15 13:48:46')
        );
    }

    public function testNormalizeOrderDateForRequestReturnsEmptyStringForInvalidValue(): void
    {
        $this->assertSame('', CancellationFormHelper::normalizeOrderDateForRequest('invalid-date'));
    }

    public function testGetPrefilledOrderDateFromRequestReturnsNormalizedDate(): void
    {
        $_GET['orderDate'] = '2026-06-15 13:48:46';

        $this->assertSame('2026-06-15', CancellationFormHelper::getPrefilledOrderDateFromRequest());
    }

    public function testBuildCancellationFormUrlAppendsOrderData(): void
    {
        $this->assertSame(
            '/widerruf?orderNo=2025-17&orderDate=2026-06-15',
            CancellationFormHelper::buildCancellationFormUrl(
                '/widerruf',
                '2025-17',
                '2026-06-15 13:48:46'
            )
        );
    }

    public function testBuildCancellationFormUrlUsesAmpersandOnExistingQueryString(): void
    {
        $this->assertSame(
            '/widerruf?foo=bar&orderNo=2025-17',
            CancellationFormHelper::buildCancellationFormUrl(
                '/widerruf?foo=bar',
                '2025-17'
            )
        );
    }
}
