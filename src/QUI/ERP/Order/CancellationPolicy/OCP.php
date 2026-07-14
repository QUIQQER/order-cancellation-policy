<?php

/**
 *
 */

namespace QUI\ERP\Order\CancellationPolicy;

use QUI;
use QUI\ERP\Areas\Area;
use QUI\ERP\Areas\Handler;
use QUI\Exception;
use QUI\Utils\Doctrine;

/**
 * Class OCP
 *
 * @package QUI\ERP\Order\CancellationPolicy
 */
class OCP
{
    /**
     * Return the areas table name
     *
     * @return string
     */
    protected static function table(): string
    {
        return QUI::getDBTableName('areas');
    }

    /**
     * Checks if the area has a cancellation policy or not
     *
     * @param Area $Area
     * @return bool|int
     */
    public static function hasAreaCancellationPolicy(Area $Area): bool | int
    {
        try {
            $QueryBuilder = QUI::getQueryBuilder();
            $result = $QueryBuilder
                ->select('ocp')
                ->from(Doctrine::quoteIdentifier(self::table()))
                ->where($QueryBuilder->expr()->eq('id', ':id'))
                ->setParameter('id', $Area->getId())
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();
        } catch (\Exception) {
            return 0;
        }

        if (!is_array($result) || !isset($result['ocp'])) {
            return 0;
        }

        return (int)$result['ocp'];
    }

    /**
     * @return array<int, array{id: int|string, title: string, ocp: int}>
     */
    public static function getList(): array
    {
        $Areas = Handler::getInstance();
        $result = [];

        try {
            $list = QUI::getQueryBuilder()
                ->select('id', 'ocp')
                ->from(Doctrine::quoteIdentifier(self::table()))
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (\Exception) {
            return [];
        }

        foreach ($list as $entry) {
            try {
                $Area = $Areas->getChild((int)$entry['id']);

                if (!isset($entry['ocp'])) {
                    $entry['ocp'] = 0;
                }

                $data = [
                    'id' => $Area->getId(),
                    'title' => $Area->getTitle(),
                    'ocp' => (int)$entry['ocp']
                ];

                $result[] = $data;
            } catch (Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $result;
    }

    /**
     * Activate order cancellation policy for the area
     *
     * @param int|string $areaId
     * @throws \Doctrine\DBAL\Exception
     */
    public static function activate(int | string $areaId): void
    {
        // @todo permissions

        QUI::getDataBaseConnection()->update(
            Doctrine::quoteIdentifier(self::table()),
            ['ocp' => 1],
            ['id' => $areaId]
        );
    }

    /**
     * Deactivate order cancellation policy for the area
     *
     * @param string|int $areaId
     * @throws \Doctrine\DBAL\Exception
     */
    public static function deactivate(string | int $areaId): void
    {
        // @todo permissions

        QUI::getDataBaseConnection()->update(
            Doctrine::quoteIdentifier(self::table()),
            ['ocp' => 0],
            ['id' => $areaId]
        );
    }
}
