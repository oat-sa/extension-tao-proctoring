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
    'ui/datatable'
], function ($, __, helpers, loadingBar, encode, feedback, dialog, breadcrumbsFactory) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.delivery-manager';

    // the page is always loading data when starting
    loadingBar.start();

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
            var assignUrl = helpers._url('testTakers', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var removeUrl = helpers._url('removeTestTakers', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var serviceUrl = helpers._url('deliveryTestTakers', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var monitoringUrl = helpers._url('monitoring', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter: testCenterId});

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

            // request the server to remove the selected test takers
            var remove = function(selection) {
                request(removeUrl, selection, __('Test takers have been removed'));
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
                        id: 'back',
                        icon: 'preview',
                        title: __('Return to the session monitoring'),
                        label: __('Monitoring'),
                        action: function() {
                            window.location.href = monitoringUrl;
                        }
                    }, {
                        id: 'assign',
                        icon: 'add',
                        title: __('Assign more test takers to this session'),
                        label: __('Add test takers'),
                        action: function() {
                            window.location.href = assignUrl;
                        }
                    }, {
                        id: 'remove',
                        icon: 'remove',
                        title: __('Remove the selected test takers from the session'),
                        label: __('Remove'),
                        massAction: true,
                        action: function(selection) {
                            dialog({
                                message: __('The test takers will be removed from this session. Continue ?'),
                                autoRender: true,
                                autoDestroy: true,
                                onOkBtn: function() {
                                    remove(selection);
                                }
                            });
                        }
                    }],
                    actions: [{
                        id: 'remove',
                        icon: 'remove',
                        title: __('Remove the test taker from the session'),
                        action: function(id) {
                            dialog({
                                autoRender: true,
                                autoDestroy: true,
                                message: __('The test taker will be removed from this session. Continue ?'),
                                onOkBtn: function() {
                                    remove([id]);
                                }
                            });
                        }
                    }],
                    selectable: true,
                    model: [{
                        id: 'firstname',
                        label: __('First name')
                    }, {
                        id: 'lastname',
                        label: __('Last name')
                    }, {
                        id: 'identifier',
                        label: __('Identifier')
                    }, {
                        id: 'status',
                        label: __('Status')
                    }]
                }, dataset);
        }
    };

    return proctorDeliveryIndexCtlr;
});
