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
    'ui/breadcrumbs',
    'ui/datatable'
], function ($, __, helpers, loadingBar, encode, feedback, dialog, breadcrumbs) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.diagnostic';

    // the page is always loading data when starting
    loadingBar.start();

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
            var testSiteId = $container.data('id');
            var removeUrl = helpers._url('remove', 'Diagnostic', 'taoProctoring', {testCenter : testSiteId});
            var serviceUrl = helpers._url('index', 'Diagnostic', 'taoProctoring', {testCenter : testSiteId});

            var bc = breadcrumbs({
                breadcrumbs : crumbs,
                renderTo: $container.find('.header'),
                replace: true
            });

            // request the server with a selection of readiness check results
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

            // request the server to remove the selected diagnostic
            var remove = function(selection) {
                notYet();
                //request(removeUrl, selection, __('The readiness check result have been removed'));
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
                        empty: __('No readiness checks have been done!'),
                        available: __('Readiness checks already done'),
                        loading: __('Loading')
                    },
                    tools: [{
                        id: 'launch',
                        icon: 'play',
                        title: __('Launch another readiness check'),
                        label: __('Launch readiness check'),
                        action: function() {
                            notYet();
                        }
                    }, {
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
                    }],
                    actions: [{
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
                    }],
                    selectable: true,
                    model: [{
                        id: 'workstation',
                        label: __('Workstation')
                    }, {
                        id: 'os',
                        label: __('OS')
                    }, {
                        id: 'browser',
                        label: __('Browser')
                    }, {
                        id: 'performance',
                        label: __('Performance')
                    }, {
                        id: 'bandwidth',
                        label: __('Bandwidth')
                    }, {
                        id: 'date',
                        label: __('Date')
                    }]
                }, dataset);
        }
    };

    return taoProctoringDiagnosticCtlr;
});
