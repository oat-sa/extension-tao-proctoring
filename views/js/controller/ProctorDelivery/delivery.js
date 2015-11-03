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
    'ui/breadcrumbs',
    'ui/datatable'
], function ($, __, helpers, loadingBar, encode, feedback, dialog, breadcrumbs) {
    'use strict';

    /**
     * The polling delay used to refresh the list
     * @type {Number}
     */
    var refreshPolling = 60 * 1000; // once per minute

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
            var deliveryId = $container.data('id');
            var testSiteId = $container.data('testsite');
            var assignUrl = helpers._url('testTakers', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testSiteId});
            var removeUrl = helpers._url('remove', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testSiteId});
            var authoriseUrl = helpers._url('authorise', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testSiteId});
            var serviceUrl = helpers._url('deliveryTestTakers', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testSiteId});
            
            var bc = breadcrumbs({
                breadcrumbs : crumbs,
                renderTo: $container.find('.header'),
                replace: true
            });
            
            //@TODO format the incoming data before displaying in the datatable
            
            // request the server with a selection of test takers
            var request = function(url, selection, message) {
                if (selection && selection.length) {
                    loadingBar.start();

                    $.ajax({
                        url: url,
                        data: {
                            tt: selection
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
                        id: 'assign',
                        icon: 'add',
                        title: __('Assign test takers to this delivery'),
                        label: __('Add test takers'),
                        action: function() {
                            location.href = assignUrl;
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
                    }, {
                        id: 'remove',
                        icon: 'remove',
                        title: __('Remove the selected test takers from the delivery'),
                        label: __('Remove'),
                        massAction: true,
                        action: function(selection) {
                            dialog({
                                message: __('The test takers will be removed from this delivery. Continue ?'),
                                autoRender: true,
                                autoDestroy: true,
                                onOkBtn: function() {
                                    remove(selection);
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
                    }, {
                        id: 'remove',
                        icon: 'remove',
                        title: __('Remove the test taker from the delivery'),
                        action: function(id) {
                            dialog({
                                autoRender: true,
                                autoDestroy: true,
                                message: __('The test taker will be removed from this delivery. Continue ?'),
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
                        id: 'company',
                        label: __('Company name')
                    }, {
                        id: 'status',
                        label: __('Status')
                    }]
                }, dataset);
        }
    };

    return proctorDeliveryIndexCtlr;
});
