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
    'lodash',
    'i18n',
    'core/promise',
    'core/dataProvider/proxy',
    'controller/app',
    'util/url',
    'layout/loading-bar',
    'ui/listbox',
    'util/encode',
    'ui/feedback',
    'ui/bulkActionPopup',
    'ui/cascadingComboBox',
    'ui/container',
    'taoProctoring/helper/status',
    'taoProctoring/component/dataBroker',
    'tpl!taoProctoring/templates/delivery/index',
    'tpl!taoProctoring/templates/delivery/listBoxActions',
    'tpl!taoProctoring/templates/delivery/listBoxStats'
], function (
    $,
    _,
    __,
    Promise,
    proxyFactory,
    appController,
    urlHelper,
    loadingBar,
    listBoxFactory,
    encode,
    feedback,
    bulkActionPopup,
    cascadingComboBox,
    containerFactory,
    _status,
    dataBrokerFactory,
    indexTpl,
    actionsTpl,
    statsTpl
) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.delivery-index';

    var serviceUrl = urlHelper.route('deliveries', 'DeliverySelection', 'taoProctoring');
    var pauseUrl = urlHelper.route('pauseExecutions', 'Monitor', 'taoProctoring');
    var sessionsUrl = urlHelper.route('deliveryExecutions', 'Monitor', 'taoProctoring');

    /**
     * Gets a list of named sessions
     * @param {Array} sessions
     * @returns {Array}
     */
    function getSessionsNames(sessions) {
        return _.map(sessions, function (session) {
            var testTaker = session.testTaker;
            return {
                id: session.id,
                label: testTaker.firstName + ' ' + testTaker.lastName
            };
        });
    }

    /**
     * Gets the list of sessions for a particular delivery
     * @param {String} deliveryId
     * @returns {Promise}
     */
    function getDeliverySessions(deliveryId) {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: sessionsUrl,
                data: {
                    delivery: deliveryId
                },
                dataType: 'json',
                type: 'GET'
            }).done(function (response) {
                if (_.isPlainObject(response) && _.isArray(response.data)) {
                    resolve(response.data);
                } else {
                    reject(response);
                }
            }).fail(reject);
        });
    }

    /**
     * Request a pause on the listed delivery sessions
     * @param {String} deliveryId
     * @param {Array} sessions
     * @param {Object} reason
     * @returns {Promise}
     * @returns {*}
     */
    function requestPause(deliveryId, sessions, reason) {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: pauseUrl,
                data: {
                    delivery: deliveryId,
                    execution: _.pluck(sessions, 'id'),
                    reason: reason
                },
                dataType: 'json',
                type: 'POST'
            }).done(resolve).fail(reject);
        });
    }

    // the page is always loading data when starting
    loadingBar.start();

    /**
     * Controls the ProctorDelivery index page
     *
     * @type {Object}
     */
    return {
        /**
         * Entry point of the page
         */
        start: function start() {
            var title = __("Sessions");
            var deliveries, categories;
            var container = containerFactory('.container').changeScope(cssScope).write(indexTpl({title: title}));
            var listBox = listBoxFactory({
                title: title,
                textEmpty: __("No sessions available"),
                textNumber: __("Available"),
                textLoading: __("Loading"),
                renderTo: container.find('.content'),
                replace: true,
                list: [],
                width: 12,

                // discard the "all sessions" box from available count
                countRenderer: function (count) {
                    return count - 1;
                }
            });

            appController.on('change.deliveryIndex', function() {
                appController.off('change.deliveryIndex');
                listBox.destroy();
                container.destroy();
            });

            dataBrokerFactory().on('error', function(err) {
                if (err.code === 403) {
                    //we just leave if any 403 occurs
                    window.location.reload(true);
                }
            }).loadProviders({
                deliveries: proxyFactory('ajax').init({
                    actions: {
                        read: serviceUrl
                    }
                }),
                executions: proxyFactory('ajax').init({
                    actions: {
                        read: {
                            url: sessionsUrl,
                            validate: function(params) {
                                return _.isPlainObject(params) && !_.isEmpty(params.delivery);
                            }
                        },
                        pause: {
                            url: pauseUrl,
                            validate: function(params) {
                                return _.isPlainObject(params) && !_.isEmpty(params.delivery) && !_.isEmpty(params.execution);
                            }
                        }
                    }
                })
            }).then(function(dataBroker) {
                // get the label of a delivery from its ID
                function getDeliveryLabel(id) {
                    return deliveries[id] && deliveries[id].label;
                }

                //
                function formatPauseWarning(response, deliveryExecutions) {
                    var messageContext, unprocessed;
                    var responseData;

                    messageContext = '';
                    if (response) {
                        responseData = response.data;
                        unprocessed = {};
                        if (responseData) {
                            _.forEach(responseData.unprocessed, function (id) {
                                var execution = deliveryExecutions[id];
                                var uri = execution && execution.delivery && execution.delivery.uri;
                                if (uri) {
                                    unprocessed[uri] = (unprocessed[uri] || 0) + 1;
                                }
                            });
                        }

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

                    return messageContext;
                }

                // refresh the index
                function refresh() {
                    loadingBar.start();
                    listBox.setLoading(true);

                    return dataBroker.readProvider('deliveries').then(function (data) {
                        categories = data.categories;

                        deliveries = data.deliveries;
                        listBox.update(data.list);
                        loadingBar.stop();

                    }).catch(function (err) {
                        appController.onError(err);
                    });
                }

                // request a pause fo the selected delivery executions
                function pause(deliveryId, selection, deliveryExecutions) {
                    return new Promise(function(resolve, reject) {
                        bulkActionPopup({
                            renderTo: container.getElement(),
                            actionName: __('Pause Session'),
                            reason: true,
                            resourceType: 'test taker',
                            categoriesSelector: cascadingComboBox({
                                categoriesDefinitions: categories.pause.categoriesDefinitions,
                                categories: categories.pause.categories
                            }),
                            allowedResources: getSessionsNames(selection)
                        })
                            .on('cancel', resolve)
                            .on('ok', function (reason) {
                                requestPause(deliveryId, selection, reason).then(function(response) {
                                    if (response && response.success) {
                                        feedback().success('Selected deliveries successfully paused');
                                        refresh().then(resolve).catch(reject);
                                    } else {
                                        feedback().warning(__('Something went wrong ...') + '<br>' + formatPauseWarning(response, deliveryExecutions), {encodeHtml: false});
                                        resolve();
                                    }
                                }).catch(reject);
                            });
                    });
                }

                appController.on('change.deliveryIndex', function() {
                    dataBroker.destroy();
                });

                dataBroker.getMiddlewares().use('read', function(request, response, next) {
                    if (response && response.list) {
                        response.deliveries = _.transform(response.list, function (result, delivery) {
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
                            delivery.cls = ((delivery.cls || '') + ' router').trim();

                            result[delivery.id] = delivery;
                        }, {});
                    }
                    next();
                });

                listBox.getElement().on('click', '.pause', function (e) {
                    var deliveryId = $(this).data('delivery');

                    loadingBar.start();

                    //prevent clicking the parent link that goes to the monitoring screen
                    e.stopPropagation();
                    e.preventDefault();

                    //get list of all test taker for the selected delivery
                    getDeliverySessions(deliveryId).then(function(sessions) {
                        var deliveryExecutions = {};
                        var inProgressExecs;
                        inProgressExecs = _.filter(sessions, function (session) {
                            deliveryExecutions[session.id] = session;
                            return (session.state && session.state.status === _status.getStatus('inprogress').code);
                        });

                        if (inProgressExecs.length) {
                            return pause(deliveryId, inProgressExecs, deliveryExecutions);
                        } else {
                            feedback().info(__('There is no delivery in progress'));
                        }
                    }).catch(function (err) {
                        appController.onError(err);
                    }).then(function() {
                        loadingBar.stop();
                    });
                });

                refresh();

            }).catch(function(err) {
                appController.onError(err);
                loadingBar.stop();
            });
        }
    };
});
