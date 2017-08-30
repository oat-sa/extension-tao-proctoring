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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA ;
 */
/**
 * @author Jean-Sébastien Conan <jean-sebastien.conan@vesperiagroup.com>
 */
define([
    'lodash',
    'i18n',
    'util/url',
    'controller/app',
    'layout/loading-bar',
    'ui/container',
    'ui/button',
    'util/encode',
    'taoProctoring/component/proxy',
    'taoProctoring/component/dateRange',
    'taoProctoring/component/history/historyTable',
    'tpl!taoProctoring/templates/reporting/index',
    'ui/datatable'
], function (
    _,
    __,
    urlHelper,
    appController,
    loadingBar,
    containerFactory,
    buttonFactory,
    encode,
    proxyFactory,
    dateRangeFactory,
    historyTableFactory,
    indexTpl
) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.session-history';

    var serviceUrl = urlHelper.route('sessionHistory', 'Reporting', 'taoProctoring');
    var sessionsUrl = urlHelper.route('history', 'Reporting', 'taoProctoring');

    // the page is always loading data when starting
    loadingBar.start();

    /**
     * Controls the taoProctoring session history page
     *
     * @type {Object}
     */
    return {
        /**
         * Entry point of the page
         */
        start : function start() {
            var container = containerFactory().changeScope(cssScope).write(indexTpl());
            var currentRoute = urlHelper.parse(window.location.href);
            var context = currentRoute.query.context && decodeURIComponent(currentRoute.query.context);
            var deliveryId = currentRoute.query.delivery && decodeURIComponent(currentRoute.query.delivery);
            var sessions = decodeURIComponent(currentRoute.query.session).split(',');
            var monitoringUrl = currentRoute.query.monitoring && decodeURIComponent(currentRoute.query.monitoring);

            appController
                .on('set-referrer.history', function(route) {
                    monitoringUrl = route;
                })
                .on('change.history', function() {
                    appController.off('.history');
                    container.destroy();
                });

            proxyFactory('ajax').init({
                actions: {
                    read: serviceUrl
                }
            }).then(function(proxyService) {
                return proxyService.read({delivery : deliveryId, context: context, session: sessions}).then(function(data) {
                    var detailedHistory = data.detailedHistory;
                    var historyTable = historyTableFactory({
                        tools: [{
                            id: 'show-detailed-report',
                            icon: 'insert-horizontal-line',
                            title: __('Show detailed session history messages'),
                            label: __('Show detailed report'),
                            action: function() {
                                var tool = _.find(historyTable.config.tools, {'id' : 'show-detailed-report'});

                                historyTable.config.params.detailed = detailedHistory = !detailedHistory;
                                tool.label = detailedHistory ? __('Show brief report') : __('Show detailed report');
                                historyTable.refresh();
                            }
                        }],
                        params: {detailed: detailedHistory, delivery : deliveryId, context: context, session: sessions},
                        service: sessionsUrl,
                        sortBy: data.sortBy,
                        sortOrder: data.sortOrder
                    }, data.set)
                        .on('loading', function() {
                            loadingBar.start();
                        })
                        .on('loaded', function() {
                            loadingBar.stop();
                        })
                        .on('error',function(err){
                            appController.onError(err);
                        })
                        .render(container.find('.list'));

                    if (data.monitoringUrl) {
                        monitoringUrl = data.monitoringUrl;
                    }

                    dateRangeFactory({
                        start : data.periodStart,
                        end : data.periodEnd,
                        renderTo: container.find('.panel')
                    }).on('change submit', function() {
                        historyTable.refresh({
                            periodStart : this.getStart(),
                            periodEnd : this.getEnd()
                        });
                    });

                    buttonFactory({
                        id: 'back',
                        type: 'info',
                        label: __('Back to sessions'),
                        cls: 'back-button',
                        renderTo: container.find('.panel')
                    }).on('click', function () {
                        if (monitoringUrl) {
                            appController.getRouter().redirect(monitoringUrl);
                        } else {
                            history.go(-1);
                        }
                    });
                });
            }).catch(function(err) {
                appController.onError(err);
                loadingBar.stop();
            });
        }
    };
});
