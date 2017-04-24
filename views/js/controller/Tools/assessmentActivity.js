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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA ;
 */

/**
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
define([
    'jquery',
    'i18n',
    'helpers',
    'util/url',
    'ui/feedback',
    'ui/cascadingComboBox',
    'ui/bulkActionPopup',
    'ui/datatable',
    'd3',
    'c3',
], function($, __, helpers, url, feedback, cascadingComboBox, bulkActionPopup, datatable, d3, c3){
    'use strict';

    var $container = $('.js-pause-active-executions-container');
    var categories = $container.data('reasoncategories');
    var msg = __("Warning, you are about to pause all in progress tests. All test takers will be paused on or before the next heartbeat. Please provide a reason for this action.")

    function doPause(reason) {
        $.ajax({
            type: "POST",
            data: {
                reason : reason
            },
            url: url.route('pauseActiveExecutions', 'Tools', 'taoProctoring'),
            dataType: 'json',
            success: function(data) {
                helpers.loaded();
                if (data.success) {
                    feedback().success(data.message);
                } else {
                    feedback().error(data.message);
                }
            }
        });
    }


    return {
        start : function(){
            var $deliveryList = $('.js-delivery-list');
            var deliveryListModel = [
                {
                    id: 'label',
                    label: __('Delivery'),
                    sortable : true
                },
                {
                    id: 'Awaiting',
                    label: __('Awaiting'),
                    sortable : true,
                    transform: function(value) {return value.toString();}
                },
                {
                    id: 'Authorized',
                    label: __('Authorized'),
                    sortable : true,
                    transform: function(value) {return value.toString();}
                },
                {
                    id: 'Paused',
                    label: __('Paused'),
                    sortable : true,
                    transform: function(value) {return value.toString();}
                },
                {
                    id: 'Active',
                    label: __('Active'),
                    sortable : true,
                    transform: function(value) {return value.toString();}
                },
                {
                    id: 'Terminated',
                    label: __('Terminated'),
                    sortable : true,
                    transform: function(value) {return value.toString();}
                },
                {
                    id: 'Canceled',
                    label: __('Canceled'),
                    sortable : true,
                    transform: function(value) {return value.toString();}
                },
                {
                    id: 'Finished',
                    label: __('Finished'),
                    sortable : true,
                    transform: function(value) {return value.toString();}
                },
            ];

            c3.generate({
                bindto: '.js-completed-assessments',
                data: {
                    x: 'time',
                    xFormat: '%Y-%m-%d %H:%M:%S',
                    mimeType: 'json',
                    url: url.route('completedAssessmentsData', 'Tools', 'taoProctoring'),
                    type: 'bar'
                },
                axis: {
                    x: {
                        type: 'timeseries',
                        tick: {
                            format: '%H:%M',
                        },
                        label: {
                            text: __('Hours'),
                            position: 'bottom center'
                        }
                    },
                    y: {
                        label: {
                            text: __('Completed tests'),
                            position: 'top'
                        }
                    }
                },

                tooltip: {
                    format: {
                        name: function () {
                            return __('Completed');
                        }
                    }
                },
                legend: {
                    show: false
                }
            });

            $deliveryList.datatable({
                url: url.route('deliveriesActivityData', 'Tools', 'taoProctoring'),
                filter: false,
                model: deliveryListModel,
                paginationStrategyTop : 'none',
                paginationStrategyBottom : 'none',
                selectable : true,
                sortorder : 'asc',
                sortby : 'label'
            }, deliveryListModel);
            $deliveryList.datatable('refresh');


            $('.js-pause').on('click', function() {
                var config;

                config = {
                    renderTo : $container,
                    actionName : msg,
                    reason : true,
                    allowedResources: [],
                    reasonRequired: true,
                    categoriesSelector: cascadingComboBox(categories['pause'])
                };

                bulkActionPopup(config).on('ok', function(reason){
                    doPause(reason);
                });
            });
        }
    };
});
