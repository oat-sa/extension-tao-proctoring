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
    'lodash',
    'jquery',
    'i18n',
    'helpers',
    'layout/loading-bar',
    'ui/listbox',
    'util/encode',
    'ui/feedback',
    'ui/bulkActionPopup',
    'ui/cascadingComboBox',
    'taoProctoring/helper/status',
    'taoProctoring/component/breadcrumbs',
    'tpl!taoProctoring/templates/delivery/listBoxActions',
    'tpl!taoProctoring/templates/delivery/listBoxStats'
], function (_, $, __, helpers, loadingBar, listBoxFactory, encode, feedback, bulkActionPopup, cascadingComboBox, _status, breadcrumbsFactory, actionsTpl, statsTpl) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.delivery-index';

    /**
     * Controls the ProctorDelivery index page
     *
     * @type {Object}
     */
    var taoProctoringCtlr = {
        /**
         * Entry point of the page
         */
        start: function start() {
            var $container = $(cssScope);
            var list = $container.data('list');
            var deliveries = format(list);
            var crumbs = $container.data('breadcrumbs');
            var categories = $container.data('categories');
            var testCenterId = $container.data('testcenter');
            var serviceUrl = helpers._url('deliveries', 'Delivery', 'taoProctoring', {testCenter: testCenterId});
            var listBox = listBoxFactory({
                title: __("Sessions"),
                textEmpty: __("No sessions available"),
                textNumber: __("Available"),
                textLoading: __("Loading"),
                renderTo: $container.find('.content'),
                replace: true,
                list: list,
                width: 12,

                // discard the "all sessions" box from available count
                countRenderer: function (count) {
                    return count - 1;
                }
            });

            // format each delivery descriptor to be displayed in a box, build a map of deliveries
            function format(data) {
                return _.transform(data, function (result, delivery) {
                    var props = delivery.properties;
                    var tplData = {
                        locked: delivery.stats.awaitingApproval,
                        inProgress: delivery.stats.inProgress,
                        paused: delivery.stats.paused
                    };

                    if (props && props.periodStart && props.periodEnd) {
                        tplData.showProperties = true;
                        tplData.periodStart = props.periodStart;
                        tplData.periodEnd = props.periodEnd;

                        //add a special class for boxes that have more information to display
                        delivery.cls = 'has-properties-displayed';
                    }
                    delivery.html = actionsTpl({
                        id: delivery.id
                    });
                    delivery.content = statsTpl(tplData);

                    result[delivery.id] = delivery;
                }, {});
            }

            // get the label of a delivery from its ID
            function getDeliveryLabel(id) {
                return deliveries[id] && deliveries[id].label;
            }

            // update the index from a JSON array
            function update(data) {
                deliveries = format(data);
                listBox.update(data);
                loadingBar.stop();
            }

            // refresh the index
            function refresh() {
                loadingBar.start();
                listBox.setLoading(true);

                $.ajax({
                    url: serviceUrl,
                    cache: false,
                    dataType: 'json',
                    type: 'GET'
                }).done(function (data) {
                    update(data);
                });
            }

            // request a pause fo the selected delivery executions
            function pause(deliveryId, selection, deliveryExecutions) {

                var allowed = _.map(selection, function (data) {
                    return {
                        id: data.id,
                        label: data.testTaker.firstName + ' ' + data.testTaker.lastName
                    };
                });

                bulkActionPopup({
                    renderTo: $container,
                    actionName: __('Pause Session'),
                    reason: true,
                    resourceType: 'test taker',
                    categoriesSelector: cascadingComboBox({
                        categoriesDefinitions: categories.pause.categoriesDefinitions,
                        categories: categories.pause.categories
                    }),
                    allowedResources: allowed
                }).on('ok', function (reason) {
                    //execute callback
                    $.ajax({
                        url: helpers._url('pauseExecutions', 'Delivery', 'taoProctoring'),
                        data: {
                            delivery: deliveryId,
                            testCenter: testCenterId,
                            execution: _.pluck(selection, 'id'),
                            reason: reason
                        },
                        dataType: 'json',
                        type: 'POST',
                        error: function () {
                            loadingBar.stop();
                        }
                    }).done(function (response) {
                        var messageContext, unprocessed;

                        loadingBar.stop();

                        if (response && response.success) {
                            feedback().success('Selected deliveries successfully paused');
                            refresh();
                        } else {
                            messageContext = '';
                            if (response) {
                                unprocessed = {};
                                _.forEach(response.unprocessed, function (id) {
                                    var execution = deliveryExecutions[id];
                                    var uri = execution && execution.delivery && execution.delivery.uri;
                                    if (uri) {
                                        unprocessed[uri] = (unprocessed[uri] || 0) + 1;
                                    }
                                });

                                unprocessed = _.map(unprocessed, function (count, uri) {
                                    if (count > 1) {
                                        return __('%d sessions of the delivery %s have not been paused', count, getDeliveryLabel(uri));
                                    }
                                    return __('A session of the delivery %s have not been paused', getDeliveryLabel(uri));
                                });

                                if (unprocessed.length) {
                                    messageContext += '<br>' + unprocessed.join('<br>');
                                }
                                if (response.error) {
                                    messageContext += '<br>' + encode.html(response.error);
                                }
                            }
                            feedback().warning(__('Something went wrong ...') + '<br>' + messageContext, {encodeHtml: false});
                        }
                    });
                });
            }

            breadcrumbsFactory($container, crumbs);

            $container.on('click', '.pause', function (e) {

                var deliveryId = $(this).data('delivery');
                var pauseUrl =
                    helpers._url('deliveryExecutions', 'Monitor', 'taoProctoring', (deliveryId === 'all') ? {} : {delivery: deliveryId});

                //prevent clicking the parent link that goes to the monitoring screen
                e.stopPropagation();
                e.preventDefault();

                //get list of all test taker for the selected delivery
                $.get(pauseUrl, function (res) {
                    var deliveryExecutions = {};
                    var inProgressExecs;

                    if (_.isPlainObject(res) && _.isArray(res.data)) {
                        inProgressExecs = _.filter(res.data, function (data) {
                            deliveryExecutions[data.id] = data;
                            return (data.state && data.state.status === _status.getStatus('inprogress').code);
                        });

                        if (inProgressExecs.length) {
                            pause(deliveryId, inProgressExecs, deliveryExecutions);
                        } else {
                            feedback().info(__('There is no delivery in progress'));
                        }
                    }
                });
            });

            if (!list) {
                refresh();
            } else {
                loadingBar.stop();
            }
        }
    };

    // the page is always loading data when starting
    loadingBar.start();

    return taoProctoringCtlr;
});
