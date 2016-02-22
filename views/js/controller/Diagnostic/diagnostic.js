/*
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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 */

/**
 *
 * @author dieter <dieter@taotesting.com>
 * @author Jean-Sébastien Conan <jean-sebastien.conan@vesperiagroup.com>
 */
define([
    'jquery',
    'i18n',
    'helpers',
    'layout/loading-bar',
    'ui/actionbar',
    'taoProctoring/component/breadcrumbs',
    'taoClientDiagnostic/tools/diagnostic/diagnostic',
    'ui/feedback'
], function ($, __, helpers, loadingBar, actionbar, breadcrumbsFactory, diagnosticFactory) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.diagnostic-runner';

    // the page is always loading data when starting
    loadingBar.start();

    /**
     * Controls the taoProctoring readiness check page
     *
     * @type {Object}
     */
    var taoProctoringDiagnosticRunnerCtlr = {
        /**
         * Entry point of the page
         */
        start : function start() {
            var $container = $(cssScope);
            var $list = $container.find('.list');
            var $panel = $('.panel');
            var crumbs = $container.data('breadcrumbs');
            var config = $container.data('config');
            var testCenterId = $container.data('testcenter');
            var indexUrl = helpers._url('index', 'Diagnostic', 'taoProctoring', {testCenter : testCenterId});

            var bc = breadcrumbsFactory($container, crumbs);

            actionbar({
                renderTo: $panel,
                buttons: [{
                    id: 'back',
                    icon: 'step-backward',
                    title: __('Return to the list'),
                    label: __('List of readiness checks'),
                    action: function() {
                        location.href = indexUrl;
                    }
                }]
            });

            diagnosticFactory(config)
                .on('start', function() {
                    loadingBar.start();
                })
                .on('end', function() {
                    loadingBar.stop();
                })
                .on('render', function() {
                    loadingBar.stop();
                })
                .render($list);
        }
    };

    return taoProctoringDiagnosticRunnerCtlr;
});
