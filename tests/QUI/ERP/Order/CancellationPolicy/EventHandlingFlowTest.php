<?php

namespace QUITests\ERP\Order\CancellationPolicy;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Countries\Country;
use QUI\ERP\Address;
use QUI\ERP\Areas\Area;
use QUI\ERP\Areas\Handler as AreasHandler;
use QUI\ERP\Order\AbstractOrder;
use QUI\ERP\Order\CancellationPolicy\EventHandling;
use QUI\ERP\Order\CancellationPolicy\OCP;
use QUI\ERP\Order\Controls\AbstractOrderingStep;
use QUI\ERP\Order\OrderInProcess;
use QUI\ERP\Order\OrderView;
use QUI\ERP\Order\SimpleCheckout\Checkout;
use QUI\ERP\User;
use QUI\Projects\Project;
use QUI\Projects\Site;
use QUI\Rewrite;
use QUI\Smarty\Collector;
use QUI\Utils\Doctrine;

class EventHandlingFlowTest extends TestCase
{
    private Area $Area;
    private Country $Country;
    private mixed $originalOcp;
    private Rewrite $originalRewrite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalRewrite = QUI::$Rewrite ?? QUI::getRewrite();
        [$this->Area, $this->Country] = $this->findAreaWithCountry();
        $this->originalOcp = QUI::getDataBaseConnection()->createQueryBuilder()
            ->select(Doctrine::quoteIdentifier('ocp'))
            ->from($this->getAreasTable())
            ->where(Doctrine::quoteIdentifier('id') . ' = :areaId')
            ->setParameter('areaId', $this->Area->getId())
            ->executeQuery()
            ->fetchOne();
        OCP::activate($this->Area->getId());
    }

    protected function tearDown(): void
    {
        QUI::$Rewrite = $this->originalRewrite;
        QUI::getDataBaseConnection()->update(
            $this->getAreasTable(),
            ['ocp' => $this->originalOcp],
            ['id' => $this->Area->getId()]
        );
        parent::tearDown();
    }

    public function testBuildsCheckoutCancellationTextForPrivateCustomerArea(): void
    {
        $Project = QUI::getProjectManager()->getStandard();
        self::assertNotNull($Project);
        $Order = $this->createOrderInProcess();
        $Handler = new class () extends EventHandling {
            public static function getCancellationText(
                AbstractOrder $Order,
                Project $Project
            ): ?string {
                return parent::getText($Order, $Project);
            }
        };

        $text = $Handler::getCancellationText($Order, $Project);

        self::assertIsString($text);
        self::assertNotSame('', $text);
    }

    public function testAppendsPrefilledCancellationLinkToFrontendOrder(): void
    {
        $CancellationSite = $this->createMock(Site::class);
        $CancellationSite->method('getUrlRewritten')->willReturn('/widerruf');
        $Project = $this->createMock(Project::class);
        $Project->method('getSites')->willReturn([$CancellationSite]);
        $Rewrite = $this->createMock(Rewrite::class);
        $Rewrite->method('getProject')->willReturn($Project);
        QUI::$Rewrite = $Rewrite;
        $Address = $this->createMock(Address::class);
        $Address->method('getCountry')->willReturn($this->Country);
        $Customer = $this->createMock(User::class);
        $Customer->method('getUUID')->willReturn(QUI\Utils\Uuid::get());
        $Order = $this->createMock(OrderView::class);
        $Order->method('getCustomer')->willReturn($Customer);
        $Order->method('getInvoiceAddress')->willReturn($Address);
        $Order->method('getPrefixedId')->willReturn('ORDER-2026-42');
        $Order->method('toArray')->willReturn(['cDate' => '2026-07-15 10:30:00']);
        $Collector = new Collector();

        EventHandling::templateFrontendUserOrderFooterEnd($Collector, $Order);

        $html = $Collector->getContent();
        self::assertStringContainsString('/widerruf?orderNo=ORDER-2026-42', $html);
        self::assertStringContainsString('orderDate=2026-07-15', $html);
    }

    public function testCheckoutEventsReplaceTextWithCancellationPolicy(): void
    {
        $Project = QUI::getProjectManager()->getStandard();
        self::assertNotNull($Project);
        $Order = $this->createOrderInProcess();
        $Step = $this->createMock(AbstractOrderingStep::class);
        $Step->method('getAttribute')->with('Project')->willReturn($Project);
        $Step->method('getOrder')->willReturn($Order);
        $orderProcessText = 'original order process text';

        EventHandling::onQuiqqerOrderOrderProcessCheckoutOutput($Step, $orderProcessText);

        self::assertNotSame('original order process text', $orderProcessText);
        $Checkout = $this->createMock(Checkout::class);
        $Checkout->method('getOrder')->willReturn($Order);
        $simpleCheckoutText = 'original simple checkout text';

        EventHandling::onQuiqqerOrderSimpleCheckoutOutput($Checkout, $simpleCheckoutText);

        self::assertNotSame('original simple checkout text', $simpleCheckoutText);
    }

    private function createOrderInProcess(): OrderInProcess
    {
        $Address = $this->createMock(Address::class);
        $Address->method('getCountry')->willReturn($this->Country);
        $Customer = $this->createMock(User::class);
        $Customer->method('getUUID')->willReturn(QUI\Utils\Uuid::get());
        $Order = $this->createMock(OrderInProcess::class);
        $Order->method('getInvoiceAddress')->willReturn($Address);
        $Order->method('getCustomer')->willReturn($Customer);

        return $Order;
    }

    /**
     * @return array{Area, Country}
     */
    private function findAreaWithCountry(): array
    {
        foreach (AreasHandler::getInstance()->getChildren() as $Area) {
            $countries = $Area->getCountries();

            if (isset($countries[0])) {
                return [$Area, $countries[0]];
            }
        }

        self::fail('The standard area fixtures must contain at least one country.');
    }

    private function getAreasTable(): string
    {
        return Doctrine::quoteIdentifier(QUI::getDBTableName('areas'));
    }
}
