<?php

/**
 *
 */

namespace QUI\ERP\Order\CancellationPolicy;

use QUI;
use QUI\ERP\Areas\Area;
use QUI\ERP\Areas\Handler;
use QUI\Exception;

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
    public static function hasAreaCancellationPolicy(Area $Area): bool|int
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'from' => self::table(),
                'where' => [
                    'id' => $Area->getId()
                ],
                'limit' => 1
            ]);
        } catch (Exception) {
            return 0;
        }

        if (!isset($result[0]) || !isset($result[0]['ocp'])) {
            return 0;
        }

        return (int)$result[0]['ocp'];
    }

    /**
     * @return array
     */
    public static function getList(): array
    {
        $Areas = Handler::getInstance();
        $result = [];

        try {
            $list = QUI::getDataBase()->fetch([
                'from' => self::table()
            ]);
        } catch (Exception) {
            return [];
        }

        foreach ($list as $entry) {
            try {
                /* @var $Area Area */
                $Area = $Areas->getChild($entry['id']);

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
     * @throws QUI\Database\Exception
     */
    public static function activate(int|string $areaId): void
    {
        // @todo permissions

        QUI::getDataBase()->update(
            self::table(),
            ['ocp' => 1],
            ['id' => $areaId]
        );
    }

    /**
     * Deactivate order cancellation policy for the area
     *
     * @param string|int $areaId
     * @throws QUI\Database\Exception
     */
    public static function deactivate(string|int $areaId): void
    {
        // @todo permissions

        QUI::getDataBase()->update(
            self::table(),
            ['ocp' => 0],
            ['id' => $areaId]
        );
    }
}
