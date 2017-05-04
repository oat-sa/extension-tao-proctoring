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

    var $container = $('.activity-dashboard');

    // Pause Action
    var $pauseActiveExecutionsButton = $('.js-pause');
    var $pauseActiveExecutionsContainer = $('.js-pause-active-executions-container', $container);
    var pauseReasonCategories = $pauseActiveExecutionsContainer.data('reasoncategories');
    var pauseMsg = __('Warning, you are about to pause all in progress tests. All test takers will be paused on or before the next heartbeat. Please provide a reason for this action.');

    // User Activity
    var $userActivityContainer = $('.user-activity', $container);

    // Current Assessment Activity
    var $assessmentActivityContainer = $('.assessment-activity', $container);

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

    function updateUserActivity(data) {
        // Active Test Takers
        $('.active-test-takers', $userActivityContainer).text(data.active_test_takers);

        // Active Proctors
        $('.active-proctors', $userActivityContainer).text(data.active_proctors);
    }

    function updateAssessmentActivity(data) {
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
            var autoRefreshInterval;
            var config = $('.activity-dashboard').data('config');
            var poll;

            poll = polling({
                action: function () {
                    request(url.route('assessmentActivityData', 'Tools', 'taoProctoring', { interval: $activityGraphInterval.val() }))
                    .then(function (data) {
                        updateUserActivity(data.assessment_activity);
                        updateAssessmentActivity(data.assessment_activity);
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
