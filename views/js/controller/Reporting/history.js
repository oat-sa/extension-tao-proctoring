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
    'jquery',
    'lodash',
    'i18n',
    'helpers',
    'layout/loading-bar',
    'util/encode',
    'taoProctoring/component/dateRange',
    'taoProctoring/component/history/historyTable',
    'taoProctoring/component/breadcrumbs',
    'ui/datatable'
], function ($, _, __, helpers, loadingBar, encode, dateRangeFactory, historyTableFactory, breadcrumbsFactory) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.session-history';

    /**
     * Controls the taoProctoring session history page
     *
     * @type {Object}
     */
    var taoProctoringReportCtlr = {
        /**
         * Entry point of the page
         */
        start : function start() {
            var $container = $(cssScope);
            var dataset = $container.data('set');
            var testCenterId = $container.data('testcenter');
            var deliveryId = $container.data('delivery');
            var sessions = $container.data('sessions');
            var sortBy = $container.data('sortBy');
            var sortOrder = $container.data('sortorder');
            var periodStart = $container.data('periodstart');
            var periodEnd = $container.data('periodEnd');
            var serviceUrl = helpers._url('history', 'Reporting', 'taoProctoring', {testCenter : testCenterId, delivery : deliveryId, session: sessions});
            var monitoringUrl = helpers._url('monitoring', 'Delivery', 'taoProctoring', {testCenter: testCenterId, delivery : deliveryId});
            var monitoringAllUrl = helpers._url('monitoringAll', 'Delivery', 'taoProctoring', {testCenter: testCenterId});

            var historyTable = historyTableFactory({
                    tools: [{
                        id: 'back',
                        icon: 'preview',
                        title: __('Return to the session monitoring'),
                        label: __('Monitoring'),
                        action: function() {
                            window.location.href = deliveryId ? monitoringUrl : monitoringAllUrl;
                        }
                    }],
                    service: serviceUrl,
                    sortBy: sortBy,
                    sortOrder: sortOrder
                }, dataset)
                .on('loading', function() {
                    loadingBar.start();
                })
                .on('loaded', function() {
                    loadingBar.stop();
                })
                .render($container.find('.list'));

            breadcrumbsFactory($container, $container.data('breadcrumbs'));

            dateRangeFactory({
                start : periodStart,
                end : periodEnd,
                renderTo: $container.find('.panel')
            }).on('change submit', function() {
                historyTable.refresh({
                    periodStart : this.getStart(),
                    periodEnd : this.getEnd()
                });
            });
        }
    };

    // the page is always loading data when starting
    loadingBar.start();

    return taoProctoringReportCtlr;
});
