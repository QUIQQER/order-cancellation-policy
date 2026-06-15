<?php

/**
 * This file contains package_quiqqer_order-cancellation-policy_ajax_backend_deactivate
 */

/**
 * Deactivate a cancellation policy for an area
 *
 * @param integer $areaId
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_order-cancellation-policy_ajax_backend_deactivate',
    function (int|string $areaId) {
        QUI\ERP\Order\CancellationPolicy\OCP::deactivate($areaId);
    },
    array('areaId'),
    'Permission::checkAdminUser'
);
