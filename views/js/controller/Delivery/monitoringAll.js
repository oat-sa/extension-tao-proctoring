/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA ;
 */
/**
 * @author Jean-Sébastien Conan <jean-sebastien.conan@vesperiagroup.com>
 */
define([
    'jquery',
    'i18n',
    'helpers',
    'layout/loading-bar',
    'util/encode',
    'ui/feedback',
    'ui/dialog',
    'taoProctoring/component/breadcrumbs',
    'tpl!taoProctoring/tpl/item-progress',
    'tpl!taoProctoring/tpl/delivery-link',
    'ui/datatable'
], function ($, __, helpers, loadingBar, encode, feedback, dialog, breadcrumbsFactory, itemProgressTpl, deliveryLinkTpl) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.delivery-monitoring';

    // the page is always loading data when starting
    loadingBar.start();

    /**
     * Formats a time value to string
     * @param {Number} time
     * @returns {String}
     * @private
     */
    var _timerFormat = function(time) {
        return __('%d min', Math.floor(time / 60));
    };

    var notYet = function() {
        dialog({
            message: __('Not yet implemented!'),
            autoRender: true,
            autoDestroy: true,
            buttons: 'ok'
        });
    };

    /**
     * Displays a confirm message
     * @param message
     * @param callback
     */
    var confirmMessage = function(message, callback) {
        dialog({
            message: message,
            autoRender: true,
            autoDestroy: true,
            onOkBtn: callback
        });
    };

    /**
     * Controls the taoProctoring delivery page
     *
     * @type {Object}
     */
    var proctorDeliveryIndexCtlr = {
        /**
         * Entry point of the page
         */
        start : function start() {
            var $container = $(cssScope);
            var $list = $container.find('.list');
            var crumbs = $container.data('breadcrumbs');
            var dataset = $container.data('set');
            var deliveryId = $container.data('delivery');
            var testCenterId = $container.data('testcenter');
            var terminateUrl = helpers._url('terminateExecutions', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var pauseUrl = helpers._url('pauseExecutions', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var authoriseUrl = helpers._url('authoriseExecutions', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var serviceUrl = helpers._url('allDeliveriesExecutions', 'Delivery', 'taoProctoring', {testCenter : testCenterId});

            var bc = breadcrumbsFactory($container, crumbs);

            // request the server with a selection of test takers
            var request = function(url, selection, message) {
                if (selection && selection.length) {
                    loadingBar.start();

                    $.ajax({
                        url: url,
                        data: {
                            testtaker: selection
                        },
                        dataType : 'json',
                        type: 'POST',
                        error: function() {
                            loadingBar.stop();
                        }
                    }).done(function(response) {
                        loadingBar.stop();

                        if (response && response.success) {
                            if (message) {
                                feedback().success(message);
                            }
                            $list.datatable('refresh');
                        } else {
                            feedback().error(__('Something went wrong ...') + '<br>' + encode.html(response.error), {encodeHtml: false});
                        }
                    });
                }
            };

            var notYet = function() {
                dialog({
                    message: __('Not yet implemented!'),
                    autoRender: true,
                    autoDestroy: true,
                    buttons: 'ok'
                });
            };

            // request the server to authorise the selected delivery executions
            var authorise = function(selection) {
                notYet();
                //request(authoriseUrl, selection, __('Delivery executions have been authorised'));
            };

            // request the server to pause the selected delivery executions
            var pause = function(selection) {
                notYet();
                //request(pauseUrl, selection, __('Delivery executions have been paused'));
            };

            // request the server to terminate the selected delivery executions
            var terminate = function(selection) {
                notYet();
                //request(terminateUrl, selection, __('Delivery executions have been terminated'));
            };

            // report irregularities on the selected delivery executions
            var report = function(selection) {
                notYet();
            };

            $list
                .on('query.datatable', function() {
                    loadingBar.start();
                })
                .on('load.datatable', function() {
                    loadingBar.stop();
                })
                .datatable({
                    url: serviceUrl,
                    status: {
                        empty: __('No delivery executions'),
                        available: __('Current delivery executions'),
                        loading: __('Loading')
                    },
                    tools: [{
                        id: 'refresh',
                        icon: 'reset',
                        title: __('Refresh the page'),
                        label: __('Refresh'),
                        action: function() {
                            $list.datatable('refresh');
                        }
                    }, {
                        id: 'authorise',
                        icon: 'play',
                        title: __('Authorise the selected delivery executions'),
                        label: __('Authorise'),
                        massAction: true,
                        action: function(selection) {
                            confirmMessage(
                                __('The selected delivery executions will be authorized. Continue ?'),
                                function() {
                                    authorise(selection);
                                }
                            );
                        }
                    }, {
                        id: 'pause',
                        icon: 'pause',
                        title: __('Pause delivery executions'),
                        label: __('Pause'),
                        massAction: true,
                        action: function(selection) {
                            confirmMessage(
                                __('The selected delivery executions will be paused. Continue ?'),
                                function() {
                                    pause(selection);
                                }
                            );
                        }
                    }, {
                        id: 'terminate',
                        icon: 'stop',
                        title: __('Terminate delivery executions'),
                        label: __('Terminate'),
                        massAction: true,
                        action: function(selection) {
                            confirmMessage(
                                __('The selected delivery executions will be terminated. Continue ?'),
                                function() {
                                    terminate(selection);
                                }
                            );
                        }
                    }, {
                        id: 'irregularity',
                        icon: 'delivery-small',
                        title: __('Report irregularities'),
                        label: __('Report'),
                        massAction: true,
                        action: function(selection) {
                            report(selection);
                        }
                    }],
                    actions: [{
                        id: 'authorise',
                        icon: 'play',
                        title: __('Authorise the delivery execution'),
                        hidden: function() {
                            return !this.state || !this.state.awaiting;
                        },
                        action: function(id) {
                            confirmMessage(
                                __('The delivery execution will be authorized. Continue ?'),
                                function() {
                                    authorise([id]);
                                }
                            );
                        }
                    }, {
                        id: 'pause',
                        icon: 'pause',
                        title: __('Pause the delivery execution'),
                        hidden: function() {
                            return !this.state || !this.state.authorised;
                        },
                        action: function(id) {
                            confirmMessage(
                                __('The delivery execution will be paused. Continue ?'),
                                function() {
                                    authorise([id]);
                                }
                            );
                        }
                    }, {
                        id: 'terminate',
                        icon: 'stop',
                        title: __('Terminate the delivery execution'),
                        action: function(id) {
                            confirmMessage(
                                __('The delivery execution will be terminated. Continue ?'),
                                function() {
                                    authorise([id]);
                                }
                            );
                        }
                    }, {
                        id: 'irregularity',
                        icon: 'delivery-small',
                        title: __('Report irregularities'),
                        action: function(id) {
                            report([id]);
                        }
                    }],
                    selectable: true,
                    model: [{
                        id: 'delivery',
                        label: __('Delivery'),
                        transform: function(value, row) {
                            var delivery = row && row.delivery;
                            if (delivery) {
                                delivery.url = helpers._url('monitoring', 'Delivery', 'taoProctoring', {delivery : delivery.uri, testCenter : testCenterId});
                                value = deliveryLinkTpl(delivery);
                            }
                            return value;

                        }
                    }, {
                        id: 'firstname',
                        label: __('First name'),
                        transform: function(value, row) {
                            return row && row.testTaker && row.testTaker.firstName || '';

                        }
                    }, {
                        id: 'lastname',
                        label: __('Last name'),
                        transform: function(value, row) {
                            return row && row.testTaker && row.testTaker.lastName || '';

                        }
                    }, {
                        id: 'company',
                        label: __('Company name'),
                        transform: function(value, row) {
                            return row && row.testTaker && row.testTaker.companyName || '';
                        }
                    }, {
                        id: 'status',
                        label: __('Status'),
                        transform: function(value, row) {
                            return row && row.state && row.state.status || '';
                        }
                    }, {
                        id: 'progress',
                        label: __('Progress'),
                        transform: function(value, row) {
                            var state = row && row.state;
                            var item = state && state.item;
                            var time = item && item.time;
                            if (time && time.elapsed) {
                                //if (time.total) {
                                //    time.remainingStr = _timerFormat(time.total - time.elapsed);
                                //}
                                time.elapsedStr = _timerFormat(time.elapsed);
                                time.totalStr = _timerFormat(time.total);
                                time.display = !!(time.elapsedStr || time.totalStr);
                            }
                            return itemProgressTpl(state);
                        }
                    }]
                }, dataset);
        }
    };

    return proctorDeliveryIndexCtlr;
});
