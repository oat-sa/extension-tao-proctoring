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
    'taoProctoring/component/breadcrumbs',
    'ui/datatable'
], function ($, __, helpers, loadingBar, encode, feedback, breadcrumbsFactory) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.delivery-testtakers';

    // the page is always loading data when starting
    loadingBar.start();

    /**
     * Controls the ProctorDelivery test takers assign page
     *
     * @type {Object}
     */
    var proctorDeliveryAssignCtlr = {
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
            var serviceUrl = helpers._url('availableTestTakers', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter: testCenterId});
            var assignUrl = helpers._url('assignTestTakers', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter: testCenterId});
            var managerUrl = helpers._url('manage', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter: testCenterId});

            var bc = breadcrumbsFactory($container, crumbs);

            // send the selection to the server and redirect to the index page
            var assign = function(selection) {
                if (selection && selection.length) {
                    loadingBar.start();

                    $.ajax({
                        url: assignUrl,
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
                            feedback().success(__('Test takers have been added'));
                            window.location.href = managerUrl;
                        } else {
                            feedback().error(__('Something went wrong ...') + '<br>' + encode.html(response.error), {encodeHtml: false});
                        }
                    });
                }
            };

            $list
                .on('query.datatable', function() {
                    loadingBar.start();
                })
                .on('load.datatable', function(event, response) {
                    loadingBar.stop();
                })
                .datatable({
                    url: serviceUrl,
                    status: {
                        empty: __('No available test takers to assign'),
                        available: __('Available test takers'),
                        loading: __('Loading')
                    },
                    tools: [{
                        id: 'back',
                        icon: 'left',
                        title: __('Return to the session manager'),
                        label: __('Back'),
                        action: function() {
                            window.location.href = managerUrl;
                        }
                    }, {
                        id: 'refresh',
                        icon: 'reset',
                        title: __('Refresh the page'),
                        label: __('Refresh'),
                        action: function() {
                            $list.datatable('refresh');
                        }
                    }, {
                        id: 'assign',
                        icon: 'add',
                        title: __('Assign the selected test takers to the session'),
                        label: __('Assign the selected test takers'),
                        massAction: true,
                        action: function(selection) {
                            assign(selection);
                        }
                    }],
                    actions: [{
                        id: 'assign',
                        icon: 'add',
                        title: __('Assign the test taker to the session'),
                        action: function(id) {
                            assign([id]);
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
                    }]
                }, dataset);
        }
    };

    return proctorDeliveryAssignCtlr;
});
