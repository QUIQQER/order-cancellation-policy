/**
 * @module package/quiqqer/order-cancellation-policy/bin/backend/classes/OCP
 */
define('package/quiqqer/order-cancellation-policy/bin/backend/classes/OCP', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax'

], function (QUI, QUIDOM, QUIAjax) {
    "use strict";

    return new Class({

        Extends: QUIDOM,
        Type   : 'package/quiqqer/order-cancellation-policy/bin/backend/classes/OCP',

        /**
         * Return the area list with the ocp flags
         */
        getList: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('package_quiqqer_order-cancellation-policy_ajax_backend_getList', resolve, {
                    'package': 'quiqqer/order-cancellation-policy',
                    onError  : reject
                });
            });
        },

        /**
         * Activate a cancellation policy for an area
         *
         * @param {Number} areaId
         */
        activate: function (areaId) {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('package_quiqqer_order-cancellation-policy_ajax_backend_activate', resolve, {
                    'package': 'quiqqer/order-cancellation-policy',
                    onError  : reject,
                    areaId   : areaId
                });
            });
        },

        /**
         * Deactivate a cancellation policy for an area
         *
         * @param {Number} areaId
         */
        deactivate: function (areaId) {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('package_quiqqer_order-cancellation-policy_ajax_backend_deactivate', resolve, {
                    'package': 'quiqqer/order-cancellation-policy',
                    onError  : reject,
                    areaId   : areaId
                });
            });
        }
    });
});