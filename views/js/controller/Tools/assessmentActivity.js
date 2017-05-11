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
    'lodash',
    'i18n',
    'helpers',
    'util/url',
    'core/dataProvider/request',
    'core/polling',
    'ui/feedback',
    'ui/cascadingComboBox',
    'ui/bulkActionPopup',
    'taoProctoring/component/activityMonitoring/userActivity/userActivity',
    'taoProctoring/component/activityMonitoring/currentAssessmentActivity/currentAssessmentActivity',
    'taoProctoring/component/activityMonitoring/activityGraph',
    'ui/datatable'
], function(
    $,
    d3,
    _,
    __,
    helpers,
    url,
    request,
    polling,
    feedback,
    cascadingComboBox,
    bulkActionPopup,
    userActivityFactory,
    currentAssessmentActivityFactory,
    activityGraphFactory
){
    'use strict';

    var $container = $('.activity-dashboard');

    // Pause Action
    var $pauseActiveExecutionsButton = $('.js-pause');
    var $pauseActiveExecutionsContainer = $('.js-pause-active-executions-container', $container);
    var pauseReasonCategories = $pauseActiveExecutionsContainer.data('reasoncategories');
    var pauseMsg = __('Warning, you are about to pause all in progress tests. All test takers will be paused on or before the next heartbeat. Please provide a reason for this action.');

    // Activity Graph
    var $activityGraphContainer = $('.js-completed-assessments', $container);
    var $activityGraphInterval = $('.js-activity-chart-interval', $container);
    var activityGraph;

    // Delivery List
    var $deliveryListContainer = $('.js-delivery-list', $container);
    var $deliveryListDatatable;
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

    function doPause(reason) {
        request(
            url.route('pauseActiveExecutions', 'Tools', 'taoProctoring'),
            { reason: reason },
            'POST'
        )
        .then(function (data) {
            helpers.loaded();
            feedback().success(data.message);
        })
        .catch(function (err) {
            helpers.loaded();
            feedback().error(err.message);
        });
    }


    function updateActivityGraph(data) {
        if (!activityGraph) {
            activityGraph = activityGraphFactory({
                autoRefreshBar: true,
                graphConfig: {
                    bindto: $activityGraphContainer.selector,
                    data: {
                        json: data
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
                            tick: { format: d3.format('d') },
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
            })
            .render();
        } else {
            activityGraph.refresh({
                graphConfig: {
                    data: {
                        json: data
                    },
                    axis: {
                        x: {
                            tick: {
                                format: $activityGraphInterval.val() === 'day' ? '%H:%M' : '%m-%d'
                            },
                            label: {
                                text: $activityGraphInterval.val() === 'day' ? __('Hours') : __('Days')
                            }
                        }
                    }
                }
            });
        }
    }

    function updateDeliveryList(data) {
        if (!$deliveryListDatatable) {
            $deliveryListDatatable = $deliveryListContainer.datatable({
                filter:                   false,
                model:                    deliveryListModel,
                paginationStrategyTop:    'none',
                paginationStrategyBottom: 'none',
                selectable:               true,
                sortorder:                'asc',
                sortby:                   'label'
            }, data);
        } else {
            $deliveryListDatatable.datatable('refresh', data);
        }
    }


    return {
        start: function () {
            // var $container = ...;
            var autoRefreshInterval;
            var config = $('.activity-dashboard').data('config');
            var currentAssessmentActivity;
            var poll;
            var userActivity;

            // User Activity
            userActivity = userActivityFactory()
            .render($('.user-activity', $container));

            // Current Assessment Activity
            currentAssessmentActivity = currentAssessmentActivityFactory()
            .render($('.assessment-activity', $container));

            // Completed assessment activity

            // Deliveries activity

            poll = polling({
                action: function () {
                    request(url.route('assessmentActivityData', 'Tools', 'taoProctoring', { interval: $activityGraphInterval.val() }))
                    .then(function (data) {
                        userActivity.update({
                            activeProctorsValue   : data.assessment_activity && data.assessment_activity.active_proctors,
                            activeTestTakersValue : data.assessment_activity && data.assessment_activity.active_test_takers_value
                        });
                        currentAssessmentActivity.update({
                            awaiting   : { value: data.assessment_activity && data.assessment_activity.awaiting_assessments },
                            authorized : { value: data.assessment_activity && data.assessment_activity.authorized_but_not_started_assessments },
                            current    : { value: data.assessment_activity && data.assessment_activity.total_current_assessments },
                            inProgress : { value: data.assessment_activity && data.assessment_activity.in_progress_assessments },
                            paused     : { value: data.assessment_activity && data.assessment_activity.paused_assessments }
                        });
                        updateActivityGraph(data.completed_assessments);
                        updateDeliveryList(data.deliveries_activity);
                    })
                    .catch(function (err) {
                        feedback().error(err.message);
                        poll.stop();
                    });
                }
            })
            .next()
            .stop();

            autoRefreshInterval = parseInt(config.auto_refresh_interval) * 1000;
            if (autoRefreshInterval) {
                poll.setInterval(autoRefreshInterval);
                poll.start();
            }

            $activityGraphInterval
            .on('change', function () {
                updateActivityGraph();
            });

            // Pause
            $pauseActiveExecutionsButton
            .on('click', function() {
                bulkActionPopup({
                    renderTo: $pauseActiveExecutionsContainer,
                    actionName: pauseMsg,
                    reason: true,
                    allowedResources: [],
                    reasonRequired: true,
                    categoriesSelector: cascadingComboBox(pauseReasonCategories['pause'])
                })
                .on('ok', function(reason) {
                    doPause(reason);
                });
            });
        }
    };
});
