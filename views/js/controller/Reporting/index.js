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
    'i18n',
    'helpers',
    'layout/loading-bar',
    'util/encode',
    'ui/feedback',
    'ui/dialog',
    'taoProctoring/helper/breadcrumbs',
    'ui/datatable'
], function ($, __, helpers, loadingBar, encode, feedback, dialog, breadcrumbsFactory) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.reporting-index';

    // the page is always loading data when starting
    loadingBar.start();

    /**
     * Controls the taoProctoring reporting-index page
     *
     * @type {Object}
     */
    var taoProctoringReportCtlr = {
        /**
         * Entry point of the page
         */
        start : function start() {
            var $container = $(cssScope);
            var $list = $container.find('.list');
            var crumbs = $container.data('breadcrumbs');
            var dataset = $container.data('set');
            var testCenterId = $container.data('testCenter');
			var downloadUrl = helpers._url('download', 'Reporting', 'taoProctoring', {testCenter : testCenterId});
            var serviceUrl = helpers._url('index', 'Reporting', 'taoProctoring', {testCenter : testCenterId});

            var bc = breadcrumbsFactory($container, crumbs);

            // request the server with a selection of reports
            var request = function(url, selection, message) {
                if (selection && selection.length) {
                    loadingBar.start();

                    $.ajax({
                        url: url,
                        data: {
                            tt: selection
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
            };

            var notYet = function() {
                dialog({
                    message: __('Not yet implemented!'),
                    autoRender: true,
                    autoDestroy: true,
                    buttons: 'ok'
                });
            };
            
            $list
                .on('query.datatable', function() {
                    loadingBar.start();
                })
                .on('load.datatable', function() {
                    loadingBar.stop();
                })
                .datatable({
                    url: serviceUrl,
                    status: {
                        empty: __('No reports to display!'),
                        available: __('Available reports'),
                        loading: __('Loading')
                    },
                    tools: [{
                        id: 'download',
                        icon: 'download',
                        title: __('Download the selected reports to a CSV file'),
                        label: __('Download CSV'),
                        action: function() {
                            notYet();
                        }
                    }],
                    selectable: true,
                    model: [{
                        id: 'delivery',
                        label: __('Delivery')
                    }, {
                        id: 'testtaker',
                        label: __('Test Taker')
                    }, {
                        id: 'proctor',
                        label: __('Proctor')
                    }, {
                        id: 'status',
                        label: __('Status')
                    }, {
                        id: 'start',
                        label: __('Start')
                    }, {
                        id: 'end',
                        label: __('End')
                    }, {
                        id: 'pause',
                        label: __('Pause #')
                    }, {
                        id: 'resume',
                        label: __('Resume #')
                    }, {
                        id: 'irregularities',
                        label: __('Irregularities')
                    }]
                }, dataset);
        }
    };

    return taoProctoringReportCtlr;
});
