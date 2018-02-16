<?php

/**
 * This file contains package_quiqqer_order-cancellation-policy_ajax_backend_getList
 */

/**
 * Activate a cancellation policy for an area
 *
 * @param integer $areaId
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_order-cancellation-policy_ajax_backend_activate',
    function ($areaId) {
        QUI\ERP\Order\CancellationPolicy\OCP::activate($areaId);
    },
    array('areaId'),
    'Permission::checkAdminUser'
);
