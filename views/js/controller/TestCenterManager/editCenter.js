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
define([
    'jquery',
    'lodash',
    'i18n',
    'context',
    'helpers',
    'moment',
    'layout/loading-bar',
    'util/encode',
    'ui/feedback',
    'ui/dialog',
    'ui/bulkActionPopup',
    'taoProctoring/component/breadcrumbs',
    'taoProctoring/helper/status',
    'tpl!taoProctoring/tpl/item-progress',
    'tpl!taoProctoring/tpl/delivery-link',
    'ui/datatable'
], function (
    $,
    _,
    __,
    context,
    helpers,
    moment,
    loadingBar,
    encode,
    feedback,
    dialog,
    bulkActionPopup,
    breadcrumbsFactory,
    _status,
    itemProgressTpl,
    deliveryLinkTpl
) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.eligible-deliveries';

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
            var dataset = $container.data('set');
            var testCenterId = $container.data('testcenter');
            var serviceUrl = helpers._url('getEligibilities', 'TestCenterManager', 'taoProctoring', {testCenter : testCenterId});
            var addUrl = helpers._url('addEligibility', 'TestCenterManager', 'taoProctoring', {testCenter : testCenterId});
            var editUrl = helpers._url('editEligibility', 'TestCenterManager', 'taoProctoring', {testCenter : testCenterId});
            var removeUrl = helpers._url('removeEligibility', 'TestCenterManager', 'taoProctoring', {testCenter : testCenterId});
            var tools = [];
            var actions = [];
            var model = [];

            // request the server with a selection of test takers
            function request(url, selection, reason, message) {
                if (selection && selection.length) {
                    loadingBar.start();

                    $.ajax({
                        url: url,
                        data: {
                            execution: selection,
                            reason: reason
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
            }

            // tool: page refresh
            tools.push({
                id: 'add',
                icon: 'add',
                title: __('Add'),
                label: __('Add'),
                action: function() {
                    
                    //open modal to select test taker
                    
                    //then refresh
                    $list.datatable('refresh');
                }
            });

            actions.push({
                id: 'edit',
                icon: 'edit',
                title: __('Edit Eligibile test takers'),
                action: function(){
                    console.log('do remove');
                    
                    //then refresh
                    $list.datatable('refresh');
                }
            });

            actions.push({
                id: 'remove',
                icon: 'trash',
                title: __('Remove Eligibility'),
                action: function(){
                    console.log('do remove');
                    
                    //then refresh
                    $list.datatable('refresh');
                }
            });


            // column: delivery
            model.push({
                id: 'delivery',
                label: __('Delivery'),
                transform: function(value, row) {
                    return row.delivery;

                }
            });

            // column: test taker
            model.push({
                id: 'ttakers',
                label: __('Eligible Test Takers'),
                transform: function(value, row) {
                    console.log(row);
                    return value;

                }
            });
            
            // renders the datatable
            $list
                .on('query.datatable', function() {
                    loadingBar.start();
                })
                .on('load.datatable', function(e, newDataset) {
                    //update dateset in memory
                    dataset = newDataset;
                    
                    loadingBar.stop();
                })
                .on('select.datatable', function(e, newDataset) {
                    
                })
                .datatable({
                    url: serviceUrl,
                    status: {
                        empty: __('No Eligible Delivery yet'),
                        available: __('Eligible Deliveries'),
                        loading: __('Loading')
                    },
                    tools: tools,
                    actions: actions,
                    model: model,
                    selectable: false
                }, dataset);
                
        }
    };

    return proctorDeliveryIndexCtlr;
});
