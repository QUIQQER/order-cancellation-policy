<?php

/**
 * This file contains package_quiqqer_order-cancellation-policy_ajax_backend_getList
 */

/**
 * Return the areas with the cancellation policy flag
 *
 * @return string
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_order-cancellation-policy_ajax_backend_getList',
    function () {
        return QUI\ERP\Order\CancellationPolicy\OCP::getList();
    },
    false,
    'Permission::checkAdminUser'
);
