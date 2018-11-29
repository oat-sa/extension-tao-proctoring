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
    'taoProctoring/component/activityMonitoring/deliveriesList/deliveriesList'
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
    activityGraphFactory,
    deliveriesListFactory
){
    'use strict';

    var pauseMsg = __('Warning, you are about to pause all in progress tests. All test takers will be paused on or before the next heartbeat. Please provide a reason for this action.');

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

    return {
        start: function () {
            var $container = $('.activity-dashboard');
            var activityGraph;
            var assessmentActivityAutoRefreshInterval;
            var completedAssessmentsAutoRefreshInterval;
            var config;
            var currentAssessmentActivity;
            var deliveriesList;
            var pauseReasonCategories;
            var poll;
            var userActivity;

            config = $container.data('config');
            assessmentActivityAutoRefreshInterval = parseInt(config.assessment_activity_auto_refresh) * 1000;
            completedAssessmentsAutoRefreshInterval = parseInt(config.completed_assessments_auto_refresh) * 1000;

            // User Activity
            userActivity = userActivityFactory(config.userActivityWidgets)
            .render($('.user-activity', $container));

            // Current Assessment Activity
            currentAssessmentActivity = currentAssessmentActivityFactory()
            .render($('.assessment-activity', $container));

            // Completed Assessment Activity
            activityGraph = activityGraphFactory({
                autoRefresh: Math.max(completedAssessmentsAutoRefreshInterval, assessmentActivityAutoRefreshInterval),
                autoRefreshBar: true,
                graphConfig: {
                    bindto: $('.js-completed-assessments', $container).selector,
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

            $('.js-activity-chart-interval', $container)
            .on('change', function () {
                var interval = $(this).val();
                activityGraph.refresh({
                    graphConfig : {
                        data : {
                            url: url.route('completedAssessmentsData', 'Tools', 'taoProctoring', { 'interval' : interval })
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

            // Deliveries List
            deliveriesList = deliveriesListFactory()
            .render($('.js-delivery-list'));

            // Refresh
            poll = polling({
                action: function () {
                    request(url.route('assessmentActivityData', 'Tools', 'taoProctoring'))
                    .then(function (data) {
                        var uaData = {};
                        if (data && data.group_user_activity) {
                            _.forEach(data.group_user_activity, function (v, k) {
                                    uaData[$.camelCase(k.replace(/_/g, '-'))] = {value: v};
                                }
                            );
                        }
                        userActivity.update(uaData);

                        currentAssessmentActivity.update({
                            awaiting   : { value: data && data.awaiting_assessments },
                            authorized : { value: data && data.authorized_but_not_started_assessments },
                            current    : { value: data && data.total_current_assessments },
                            inProgress : { value: data && data.in_progress_assessments },
                            paused     : { value: data && data.paused_assessments }
                        });
                        deliveriesList.update();
                    })
                    .catch(function (err) {
                        feedback().error(err.message);
                        poll.stop();
                    });
                }
            })
            .next()
            .stop();

            if (assessmentActivityAutoRefreshInterval) {
                poll.setInterval(assessmentActivityAutoRefreshInterval);
                poll.start();
            }


            // Pause
            pauseReasonCategories = $('.js-pause-active-executions-container').data('reasoncategories');

            $('.js-pause', $container)
            .on('click', function() {
                bulkActionPopup({
                    renderTo: $('.js-pause-active-executions-container', $container),
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
