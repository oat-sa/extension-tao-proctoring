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
    'd3',
    'i18n',
    'helpers',
    'util/url',
    'core/dataProvider/request',
    'core/polling',
    'ui/feedback',
    'ui/cascadingComboBox',
    'ui/bulkActionPopup',
    'taoProctoring/component/activityMonitoring/activityGraph',
    'ui/datatable'
], function(
    $,
    d3,
    __,
    helpers,
    url,
    request,
    polling,
    feedback,
    cascadingComboBox,
    bulkActionPopup,
    activityGraphFactory
){
    'use strict';

    var $container = $('.js-pause-active-executions-container');
    var categories = $container.data('reasoncategories');
    var activityGraphConfig;
    var msg = __("Warning, you are about to pause all in progress tests. All test takers will be paused on or before the next heartbeat. Please provide a reason for this action.");

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

    function updateUserActivity(data) {
        var $userActivityContainer = $('.user-activity');

        // Active Test Takers
        $('.active-test-takers', $userActivityContainer).text(data.active_test_takers);

        // Active Proctors
        $('.active-proctors', $userActivityContainer).text(data.active_proctors);
    }

    function updateAssessmentActivity(data) {
        var $assessmentActivityContainer = $('.assessment-activity');

        // Total Current Assessments
        $('.total-current-assessments', $assessmentActivityContainer).text(data.total_current_assessments);

        // In Progress
        $('.in-progress-assessments', $assessmentActivityContainer).text(data.in_progress_assessments);

        // Awaiting
        $('.awaiting-assessments', $assessmentActivityContainer).text(data.awaiting_assessments);

        // Authorized
        $('.authorized-but-not-started-assessments', $assessmentActivityContainer).text(data.authorized_but_not_started_assessments);

        // Paused
        $('.paused-assessments', $assessmentActivityContainer).text(data.paused_assessments);
    }

    function updateActivityGraph(data) {
        var $activityGraphContainer = $();

    }

    function updateDeliveryList(data) {
        var $deliveryListContainer = $('.js-delivery-list');
    }


    return {
        start : function(){
            var $deliveryList = $('.js-delivery-list');
            var activityGraph;
            var assessmentActivityAutoRefreshInterval;
            var assessmentActivityConfig;
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
            var poll;

            // Datatable
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

            // Assessment Activity Data
            assessmentActivityConfig = $('.activity-dashboard').data('config');
            assessmentActivityAutoRefreshInterval = parseInt(assessmentActivityConfig.auto_refresh_interval) * 1000;

            if (assessmentActivityAutoRefreshInterval) {
                poll = polling({
                    action: function () {
                        request(url.route('assessmentActivityData', 'Tools', 'taoProctoring'))
                        .then(function (data) {
                            updateUserActivity(data);
                            updateAssessmentActivity(data);
                            // updateActivityGraph(data);
                            // updateDeliveryList(data);
                            $deliveryList.datatable('refresh');
                        })
                        .catch(function (err) {
                            feedback().error(err.message);
                            poll.stop();
                        });
                    },
                    interval: 3 * 1000, //assessmentActivityAutoRefreshInterval,
                    autoStart: true
                });
            }

            // Pause
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

            // Activity Graph
            $('.js-activity-chart-interval').on('change', function () {
                var interval = $(this).val();
                activityGraph.refresh({
                    graphConfig : {
                        data : {
                            url: url.route('completedAssessmentsData', 'Tools', 'taoProctoring', {'interval' : interval})
                        },
                        axis : {
                            x : {
                                tick : {
                                    format: interval === 'day' ? '%H:%M' : '%m-%d'
                                },
                                label : {
                                    text:  interval === 'day' ? __('Hours') : __('Days')
                                }
                            }
                        }
                    }
                });
            });

            activityGraphConfig = $('.js-completed-assessments').data('config');
            activityGraph = activityGraphFactory({
                autoRefresh : parseInt(activityGraphConfig.completed_assessments_auto_refresh, 10) * 1000,
                autoRefreshBar : true,
                graphConfig : {
                    bindto : '.js-completed-assessments',
                    data: {
                        url: url.route('completedAssessmentsData', 'Tools', 'taoProctoring')
                    },
                    axis: {
                        x: {
                            label: {
                                text: __('Hours'),
                            }
                        },
                        y: {
                            label: {
                                text: __('Completed tests'),
                            },
                            tick: {format: d3.format("d")},
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
                }
            }).render();
        }
    };
});
