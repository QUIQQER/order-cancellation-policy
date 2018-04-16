<?php

/**
 *
 */

namespace QUI\ERP\Order\CancellationPolicy;

use QUI;
use QUI\ERP\Areas\Area;
use QUI\ERP\Areas\Handler;

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
    protected static function table()
    {
        return QUI::getDBTableName('areas');
    }

    /**
     * Checks if the area has a cancellation policy or not
     *
     * @param Area $Area
     * @return bool
     */
    public static function hasAreaCancellationPolicy(Area $Area)
    {
        $result = QUI::getDataBase()->fetch([
            'from'  => self::table(),
            'where' => [
                'id' => $Area->getId()
            ],
            'limit' => 1
        ]);

        if (!isset($result[0])) {
            return 0;
        }

        return (int)$result[0]['ocp'];
    }

    /**
     * @return array
     */
    public static function getList()
    {
        $Areas  = Handler::getInstance();
        $result = [];

        $list = QUI::getDataBase()->fetch([
            'from' => self::table()
        ]);

        foreach ($list as $entry) {
            try {
                /* @var $Area Area */
                $Area = $Areas->getChild($entry['id']);

                $data = [
                    'id'    => $Area->getId(),
                    'title' => $Area->getTitle(),
                    'ocp'   => (int)$entry['ocp']
                ];

                $result[] = $data;
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $result;
    }

    /**
     * Activate order cancellation policy for the area
     *
     * @param $areaId
     */
    public static function activate($areaId)
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
     * @param $areaId
     */
    public static function deactivate($areaId)
    {
// @todo permissions

        QUI::getDataBase()->update(
            self::table(),
            ['ocp' => 0],
            ['id' => $areaId]
        );
    }
}
