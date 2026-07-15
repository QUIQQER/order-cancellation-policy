<?php

namespace QUITests\ERP\Order\CancellationPolicy;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Areas\Handler;
use QUI\ERP\Order\CancellationPolicy\OCP;
use QUI\Utils\Doctrine;

class OCPDatabaseTest extends TestCase
{
    private int $areaId;
    private mixed $originalOcp;

    protected function setUp(): void
    {
        parent::setUp();

        $row = QUI::getDataBaseConnection()->createQueryBuilder()
            ->select(
                Doctrine::quoteIdentifier('id'),
                Doctrine::quoteIdentifier('ocp')
            )
            ->from($this->getTable())
            ->orderBy(Doctrine::quoteIdentifier('id'), 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);
        $this->areaId = (int)$row['id'];
        $this->originalOcp = $row['ocp'];
    }

    protected function tearDown(): void
    {
        QUI::getDataBaseConnection()->update(
            $this->getTable(),
            ['ocp' => $this->originalOcp],
            ['id' => $this->areaId]
        );

        parent::tearDown();
    }

    public function testActivatesAndDeactivatesCancellationPolicyForArea(): void
    {
        $Area = Handler::getInstance()->getChild($this->areaId);

        OCP::deactivate($this->areaId);
        self::assertSame(0, OCP::hasAreaCancellationPolicy($Area));

        OCP::activate($this->areaId);
        self::assertSame(1, OCP::hasAreaCancellationPolicy($Area));
    }

    public function testListsPersistedCancellationPolicyStatus(): void
    {
        OCP::activate($this->areaId);
        $entry = null;

        foreach (OCP::getList() as $candidate) {
            if ((int)$candidate['id'] === $this->areaId) {
                $entry = $candidate;
                break;
            }
        }

        self::assertIsArray($entry);
        self::assertSame(1, $entry['ocp']);
        self::assertNotSame('', $entry['title']);
    }

    private function getTable(): string
    {
        return Doctrine::quoteIdentifier(QUI::getDBTableName('areas'));
    }
}
