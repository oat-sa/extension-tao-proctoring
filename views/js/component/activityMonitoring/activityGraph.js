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
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
define([
    'jquery',
    'lodash',
    'i18n',
    'ui/component',
    'util/url',
    'c3',
    'tpl!taoProctoring/component/activityMonitoring/progressbar',
    'core/polling'
], function ($, _, __, component, url, c3, progressbarTpl, pollingFactory) {
    'use strict';

    /**
     * Some default config
     * @type {Object}
     * @private
     */
    var _defaults = {
        autoRefresh : 0,
        autoRefreshBar : false,
        transition: {
            duration: 3000
        },
        graphConfig : {
            padding: {
                bottom: 0,
                left: 35
            },
            data: {
                x: 'time',
                xFormat: '%Y-%m-%d %H:%M:%S',
                mimeType: 'json',
                type: 'bar'
            },
            axis: {
                x: {
                    type: 'timeseries',
                    tick: {
                        format: '%H:%M'
                    },
                    label: {
                        position: 'bottom center'
                    }
                },
                y: {
                    label: {
                        position: 'top'
                    }
                }
            }
        }
    };

    /**
     * @param {jQuery} $refreshProgress
     * @param duration
     */
    function runRefreshProgressBar($refreshProgress, duration) {
        duration = duration / 1000;
        $refreshProgress.css({
            '-webkit-transition': 'width 0s',
            '-moz-transition': 'width 0s',
            '-o-transition': 'width 0s',
            'transition': 'width 0s',
            'width': '0%'
        });
        //hack to refresh width
        $refreshProgress.hide().show();
        $refreshProgress.css({
            '-webkit-transition': 'width ' + duration + 's linear',
            '-moz-transition': 'width ' + duration + 's linear',
            '-o-transition': 'width ' + duration + 's linear',
            'transition': 'width ' + duration + 's linear',
            'width': '100%'
        });
    }

    /**
     * Creates a dates range with date pickers
     *
     * @param {Object} config
     * @param {String} [config.graphConfig] - configuration of c3 chart
     * @param {String} [config.autoRefresh] - interval of auto refresh
     * @param {String} [config.autoRefreshBar] - show auto refresh bar
     */
    function activityGraphFactory(config) {
        var initConfig = _.merge({}, _defaults, config);
        var chart;
        var $refreshProgress;
        var $progressbar;
        var polling;
        var activityGraph = {
            /**
             * Refresh the graph
             * @param {Object} newConfig
             */
            refresh: function refresh(newConfig) {
                if (chart) {
                    initConfig = _.merge({}, initConfig, newConfig);
                    //there is no way to update graph with new config
                    chart.internal.config.axis_x_tick_format = initConfig.graphConfig.axis.x.tick.format;
                    chart.axis.labels({
                        x: initConfig.graphConfig.axis.x.label.text
                    });
                    chart.load(initConfig.graphConfig.data);
                }
            }
        };

        return component(activityGraph)
            .on('render', function() {
                chart = c3.generate(initConfig.graphConfig);
                if (initConfig.autoRefresh) {
                    $progressbar = $(progressbarTpl());
                    $(initConfig.graphConfig.bindto).after($progressbar);
                    $refreshProgress = $progressbar.find('.js-refresh-bar-progress');

                    if (initConfig.autoRefreshBar) {
                        runRefreshProgressBar($refreshProgress, initConfig.autoRefresh);
                    }

                    polling = pollingFactory({
                        action: function() {
                            chart.load(initConfig.graphConfig.data);
                            if (initConfig.autoRefreshBar) {
                                runRefreshProgressBar($refreshProgress, initConfig.autoRefresh);
                            }
                        },
                        interval: initConfig.autoRefresh,
                        autoStart: true
                    });
                }
            })
            .on('destroy', function() {
                if (polling) {
                    polling.stop();
                }
            })
            .init(initConfig);
    }

    return activityGraphFactory;
});
