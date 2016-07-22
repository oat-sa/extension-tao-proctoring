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
    'helpers',
    'layout/loading-bar',
    'util/encode',
    'moment',
    'taoProctoring/component/dateRange',
    'tpl!taoProctoring/templates/reporting/irregularities',
    'ui/feedback',
    'ui/dialog',
    'taoProctoring/component/breadcrumbs',
    'taoProctoring/helper/status',
    'ui/datatable'
], function ($, _, __, helpers, loadingBar, encode, moment, dateRangeFactory, irregularitiesTpl, feedback, dialog, breadcrumbsFactory, _status) {
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
            var printReportButton = $container.data('printreportbutton');
            var categories = $container.data('categories');
            var testCenterId = $container.data('testcenter');
			var downloadUrl = helpers._url('download', 'Reporting', 'taoProctoring', {testCenter : testCenterId});
            var serviceUrl = helpers._url('reports', 'Reporting', 'taoProctoring', {testCenter : testCenterId});
            var bc = breadcrumbsFactory($container, crumbs);

            // request the server with a selection of reports
            var request = function(url, selection, message) {
                if (selection && selection.length) {
                    loadingBar.start();

                    $.ajax({
                        url: url,
                        data: {
                            report: selection
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

            /**
             * Open new tab with page to be printed
             * @param string|array rowId
             */
            var printResults = function printReport(rowId) {
                window.open(helpers._url('printReport',  'Reporting', 'taoProctoring', {'id' : rowId}), 'printReport' + JSON.stringify(rowId));
            };

            /**
             * Print rubric blocks with item tagged with tao-print tag
             * @param string|array rowId
             */
            var printRubric = function printRubric(rowId) {
                window.open(helpers._url('printRubric',  'Reporting', 'taoProctoring', {'id' : rowId}), 'printRubric' + JSON.stringify(rowId));
            };

            var today = moment().format('YYYY-MM-DD');

            // renderer for date strings
            function transformDate(date) {
                if (date) {
                    return moment(date).toString();
                }
                return '';
            }

            var datatableTools = [
                {
                    id: 'download',
                    icon: 'download',
                    title: __('Download the selected reports to a CSV file'),
                    label: __('Download CSV'),
                    action: function() {
                        notYet();
                    }
                }, {
                    id : 'printRubric',
                    title : __('Print rubric block with item tagged with tao-print tag'),
                    icon : 'print',
                    label : __('Print Score Report'),
                    massAction: true,
                    action : printRubric
                }
            ];
            if (printReportButton) {
                datatableTools.push({
                    id : 'printReport',
                    title : __('Print the assessment results'),
                    icon : 'print',
                    label : __('Print results'),
                    massAction: true,
                    action : printResults
                });
            }

            var datatableActions =  {
                printRubrick : {
                    id : 'printRubric',
                    title : __('Print rubric block with item tagged with tao-print tag'),
                    icon : 'print',
                    label : __('Print Score Report'),
                    action : printRubric
                }
            };
            if (printReportButton) {
                datatableActions.printReport = {
                    id : 'printReport',
                    title : __('Print the assessment results'),
                    icon : 'print',
                    label : __('Print results'),
                    action : printResults
                };
            }


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
                    tools: datatableTools,
                    selectable: true,
                    actions : datatableActions,
                    model: [{
                        id: 'delivery',
                        label: __('Test')
                    }, {
                        id: 'testtaker',
                        label: __('Test Taker')
                    }, {
                        id: 'proctor',
                        label: __('Proctor')
                    }, {
                        id: 'status',
                        label: __('Status'),
                        transform: function(value) {
                            var status = _status.getStatusByCode(value);
                            if(status){
                                return status.label;
                            }
                            return '';
                        }
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
                        label: __('Irregularities'),
                        transform: function(value) {
                            _.forEach(value, function(log) {
                                var cat = categories[log.type];

                                log[log.type] = true;

                                switch (log.type) {
                                    case 'pause':
                                        log.type = __('Pause');
                                        break;

                                    case 'resume':
                                        log.type = __('Resume');
                                        break;

                                    case 'terminate':
                                        log.type = __('Terminate');
                                        break;

                                    default:
                                        log.type = __('Irregularity');
                                        break;
                                }

                                if (cat) {
                                    log.reason = '';
                                    if (log.category) {
                                        log.reason += log.category;
                                    }
                                    if (log.subCategory) {
                                        log.reason += '/' + log.subCategory;
                                    }
                                }
                            });
                            return irregularitiesTpl(value);
                        }
                    }],
                    params:{
                        periodStart : today,
                        periodEnd : today
                    }
                }, dataset);

            //init date range picker
            dateRangeFactory({
                start : today,
                end : today,
                renderTo: $container.find('.panel')
            }).on('change submit', function() {
                $list.datatable('options', {
                    params:
                    {
                        periodStart : this.getStart(),
                        periodEnd : this.getEnd()
                    }
                }).datatable('refresh');
            });
        }
    };

    return taoProctoringReportCtlr;
});
