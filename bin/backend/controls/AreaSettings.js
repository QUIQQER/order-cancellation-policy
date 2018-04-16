/**
 * @module package/quiqqer/order-cancellation-policy/controls/AreaSettings
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/order-cancellation-policy/bin/backend/controls/AreaSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/grid/Grid',
    'Ajax',
    'Locale',
    'package/quiqqer/areas/bin/classes/Handler',
    'package/quiqqer/order-cancellation-policy/bin/backend/OCP'

], function (QUI, QUIControl, Grid, QUIAjax, QUILocale, AreaHandler, OCP) {
    "use strict";

    var Areas = new AreaHandler();

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/order-cancellation-policy/bin/backend/controls/AreaSettings',

        Binds: [
            'refresh'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input     = null;
            this.$Elm       = null;
            this.$Container = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * refresh the data
         */
        refresh: function () {
            var self = this;

            Promise.all([
                Areas.getList(),
                OCP.getList()
            ]).then(function (result) {
                var i, id, len, checked;

                var areas = result[0];
                var ocp   = [];

                for (i = 0, len = result[1].length; i < len; i++) {
                    ocp[result[1][i].id] = parseInt(result[1][i].ocp);
                }

                for (i = 0, len = areas.data.length; i < len; i++) {
                    id      = areas.data[i].id;
                    checked = typeof ocp[id] !== 'undefined' && ocp[id] ? 1 : 0;

                    areas.data[i].statusElm = new Element('span', {
                        'class': checked ? 'fa fa-check' : 'fa fa-close'
                    });

                    areas.data[i].status = checked;
                }

                self.$Grid.setData(areas);
            });
        },

        /**
         * render
         */

        /**
         * event : on import
         */
        $onImport: function () {
            var self = this;

            this.$Input = this.getElm();

            this.$Elm = new Element('div', {
                'class': 'field-container-field'
            }).wraps(this.$Input);

            var size = this.getElm().getSize();

            this.$Container = new Element('div', {
                styles: {
                    height: 300,
                    width : size.x
                }
            }).inject(this.$Elm);


            var statusClick = function (Btn) {
                var sel = self.$Grid.getSelectedData()[0];

                Btn.disable();
                Btn.setAttribute('icon', 'fa fa-spinner fa-spin');

                // if activated, then deactivate
                if (sel.status) {
                    OCP.deactivate(sel.id).then(function () {
                        return self.refresh()
                    }).then(function () {
                        Btn.setAttribute('icon', '');
                    });
                    return;
                }

                OCP.activate(sel.id).then(function () {
                    return self.refresh()
                }).then(function () {
                    Btn.setAttribute('icon', '');
                });
            };

            this.$Grid = new Grid(this.$Container, {
                buttons    : [{
                    name    : 'status',
                    text    : QUILocale.get('quiqqer/quiqqer', 'activate'),
                    disabled: true,
                    events  : {
                        click: statusClick
                    }
                }],
                columnModel: [{
                    header   : '&nbsp;',
                    dataIndex: 'statusElm',
                    dataType : 'node',
                    width    : 40
                }, {
                    header   : QUILocale.get('quiqqer/system', 'id'),
                    dataIndex: 'id',
                    dataType : 'number',
                    width    : 50
                }, {
                    header   : QUILocale.get('quiqqer/areas', 'area.grid.areaname.title'),
                    dataIndex: 'title',
                    dataType : 'string',
                    width    : 200
                }, {
                    dataIndex: 'ocp',
                    dataType : 'number',
                    hidden   : true
                }],
                onrefresh  : this.refresh,
                height     : 300,
                width      : size.x
            });

            // grid click button refresh
            this.$Grid.addEvent('click', function () {
                var sel = self.$Grid.getSelectedData()[0];

                var Status = self.$Grid.getButtons().filter(function (Btn) {
                    return Btn.getAttribute('name') === 'status';
                })[0];

                // is activated, then show button deactivate
                if (sel.status) {
                    Status.setAttribute('text', QUILocale.get('quiqqer/quiqqer', 'deactivate'));
                    Status.enable();
                    return;
                }

                // is deactivated, then show button activate
                Status.setAttribute('text', QUILocale.get('quiqqer/quiqqer', 'activate'));
                Status.enable();
            });

            this.refresh();
        },

        /**
         * event : on resize
         */
        $onResize: function () {
            if (!this.$Grid) {
                return;
            }

            if (!this.getElm()) {
                return;
            }

            var size = this.getElm().getSize();

            this.$Grid.setHeight(size.y - 40);
            this.$Grid.setWidth(size.x - 40);
        }
    });
});