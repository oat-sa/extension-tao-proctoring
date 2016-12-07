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
    'taoProctoring/component/breadcrumbs',
    'taoClientDiagnostic/tools/diagnostic/status',
    'ui/datatable'
], function ($, __, helpers, loadingBar, encode, feedback, dialog, breadcrumbsFactory, statusFactory) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.diagnostic-index';

    // the page is always loading data when starting
    loadingBar.start();

    /**
     * Format a number with decimals
     * @param {Number} number - The number to format
     * @param {Number} [digits] - The number of decimals
     * @returns {Number}
     */
    function formatNumber(number, digits) {
        var nb = undefined === digits ? 2 : Math.max(0, parseInt(digits, 10));
        var factor = Math.pow(10, nb) || 1;
        return Math.round(number * factor) / factor;
    }

    /**
     * Controls the taoProctoring readiness check page
     *
     * @type {Object}
     */
    var taoProctoringDiagnosticCtlr = {
        /**
         * Entry point of the page
         */
        start : function start() {
            var $container = $(cssScope);
            var $list = $container.find('.list');
            var crumbs = $container.data('breadcrumbs');
            var dataset = $container.data('set');
            var config = $container.data('config') || {};
            var installedExtension = $container.data('installedextension') || false;
            var testCenterId = $container.data('testcenter');
            var diagnosticUrl = helpers._url('diagnostic', 'Diagnostic', 'taoProctoring', {testCenter : testCenterId});
            var deliveryUrl = helpers._url('deliveriesByProctor', 'Diagnostic', 'taoProctoring', {testCenter : testCenterId});
            var removeUrl = helpers._url('remove', 'Diagnostic', 'taoProctoring', {testCenter : testCenterId});
            var serviceUrl = helpers._url('diagnosticData', 'Diagnostic', 'taoProctoring', {testCenter : testCenterId});

            var performancesConfig = config.performances || {};
            var performancesOptimal = performancesConfig.optimal;
            var performancesRange = Math.abs(performancesOptimal - (performancesConfig.threshold));

            var diagnosticStatus = statusFactory();

            var bc = breadcrumbsFactory($container, crumbs);

            var tools = [];
            var actions = [];
            var model = [];

            // request the server with a selection of readiness check results
            function request(url, selection, message) {
                if (selection && selection.length) {
                    loadingBar.start();

                    $.ajax({
                        url: url,
                        data: {
                            id: selection
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
            }

            // request the server to remove the selected diagnostic-index
            function remove(selection) {
                request(removeUrl, selection, __('The readiness check result have been removed'));
            }

            // tool: page refresh
            tools.push({
                id: 'refresh',
                icon: 'reset',
                title: __('Refresh the page'),
                label: __('Refresh'),
                action: function() {
                    $list.datatable('refresh');
                }
            });

            // tool: readiness check
            tools.push({
                id: 'launch',
                icon: 'play',
                title: __('Launch another readiness check'),
                label: __('Launch readiness check'),
                action: function() {
                    window.location.href = diagnosticUrl;
                }
            });

            if(installedExtension){
                // tool: compatibilty via lti
                tools.push({
                    id: 'lti',
                    icon: 'play',
                    title: __('Try a test delivery'),
                    label: __('Try a test delivery'),
                    action: function() {
                        window.location.href = deliveryUrl;
                    }
                });
            }

            // tool: remove selected results
            tools.push({
                id: 'remove',
                icon: 'remove',
                title: __('Remove the selected readiness check results'),
                label: __('Remove'),
                massAction: true,
                action: function(selection) {
                    dialog({
                        message: __('The selected readiness check results will be removed. Continue ?'),
                        autoRender: true,
                        autoDestroy: true,
                        onOkBtn: function() {
                            remove(selection);
                        }
                    });
                }
            });

            // action: remove the result
            actions.push({
                id: 'remove',
                icon: 'remove',
                title: __('Remove the readiness check result?'),
                action: function(id) {
                    dialog({
                        autoRender: true,
                        autoDestroy: true,
                        message: __('The readiness check result will be removed. Continue ?'),
                        onOkBtn: function() {
                            remove([id]);
                        }
                    });
                }
            });

            // column: Workstation identifier
            model.push({
                id: 'workstation',
                label: __('Workstation')
            });

            // column: Operating system information
            model.push({
                id: 'os',
                label: __('OS')
            });

            // column: Browser information
            model.push({
                id: 'browser',
                label: __('Browser')
            });

            // column: Performances of the workstation
            model.push({
                id: 'performance',
                label: __('Performances'),
                transform: function(value) {
                    var cursor = performancesRange - value + performancesOptimal;
                    var status = diagnosticStatus.getStatus(cursor / performancesRange * 100, 'performances');
                    return status.feedback.message;
                }
            });

            // column: Available bandwidth
            model.push({
                id: 'bandwidth',
                label: __('Bandwidth'),
                transform: function(value) {
                    var bandwidth = formatNumber(value);

                    if (value > 100) {
                        bandwidth = '> 100';
                    }

                    return bandwidth + ' Mbs';
                }
            });

            // column: Date of diagnostic
            model.push({
                id: 'date',
                label: __('Date')
            });

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
                        empty: __('No readiness checks have been done!'),
                        available: __('Readiness checks already done'),
                        loading: __('Loading')
                    },
                    tools: tools,
                    actions: actions,
                    selectable: true,
                    model: model
                }, dataset);
        }
    };

    return taoProctoringDiagnosticCtlr;
});
