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
    'ui/datatable'
], function ($, __, helpers, loadingBar, encode, feedback, dialog, breadcrumbsFactory, itemProgressTpl) {
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
            var manageUrl = helpers._url('manage', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var terminateUrl = helpers._url('terminate', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var pauseUrl = helpers._url('pause', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var authoriseUrl = helpers._url('authorise', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var serviceUrl = helpers._url('deliveryExecutions', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});

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

            // request the server to authorise the selected test takers
            var authorise = function(selection) {
                dialog({
                    message: __('Not yet implemented!'),
                    autoRender: true,
                    autoDestroy: true,
                    buttons: 'ok'
                });
                //request(authoriseUrl, selection, __('Test takers have been authorised'));
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
                        empty: __('No assigned test takers'),
                        available: __('Assigned test takers'),
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
                        id: 'manage',
                        icon: 'property-advanced',
                        title: __('Manage the deliveries'),
                        label: __('Manage'),
                        action: function() {
                            location.href = manageUrl;
                        }
                    }, {
                        id: 'authorise',
                        icon: 'checkbox-checked',
                        title: __('Authorise the selected test takers to run the delivery'),
                        label: __('Authorise'),
                        massAction: true,
                        action: function(selection) {
                            dialog({
                                message: __('The test takers will be authorized to start this delivery. Continue ?'),
                                autoRender: true,
                                autoDestroy: true,
                                onOkBtn: function() {
                                    authorise(selection);
                                }
                            });
                        }
                    }],
                    actions: [{
                        id: 'authorise',
                        icon: 'checkbox-checked',
                        title: __('Authorise the test taker to run the delivery'),
                        hidden: function() {
                            return !!this.authorised;
                        },
                        action: function(id) {
                            dialog({
                                message: __('The test taker will be authorized to start this delivery. Continue ?'),
                                autoRender: true,
                                autoDestroy: true,
                                onOkBtn: function() {
                                    authorise([id]);
                                }
                            });
                        }
                    }],
                    selectable: true,
                    model: [{
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
                        id: 'identifier',
                        label: __('Identifier'),
                        transform: function(value, row) {
                            return row && row.testTaker && row.testTaker.id || '';
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
