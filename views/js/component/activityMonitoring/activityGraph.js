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
    'd3'
], function ($, _, __, component, url, c3) {
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
                left: 35,
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
                        format: '%H:%M',
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
            },
        }
    };

    /**
     * @param {jQuery} $refreshProgress
     * @param duration
     */
    function runRefreshProgressBar($refreshProgress, duration) {
        $refreshProgress.stop();
        $refreshProgress.css({width:'0%'});
        $refreshProgress.animate({
            width: '100%'
        }, duration, 'linear');
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
        var activityGraph = {
            /**
             * Refresh the graph
             * @param {Object} [params]
             */
            refresh: function refresh() {
                if (chart) {
                    chart.load();
                }
            }
        };

        return component(activityGraph)
            .on('render', function() {
                chart = c3.generate(initConfig.graphConfig);
                if (initConfig.autoRefresh) {
                    $(initConfig.graphConfig.bindto).after($(
                        '<div class="js-completed-assessments-refresh-bar refresh-bar"><div class="js-refresh-bar-progress refresh-bar-progress"></div></div>'
                    ));
                    $refreshProgress = $('.js-refresh-bar-progress');

                    if (initConfig.autoRefreshBar) {
                        runRefreshProgressBar($refreshProgress, initConfig.autoRefresh);
                    }
                    setInterval(function() {
                        chart.load(initConfig.graphConfig.data);
                        if (initConfig.autoRefreshBar) {
                            runRefreshProgressBar($refreshProgress, initConfig.autoRefresh);
                        }
                    }, initConfig.autoRefresh);
                }
            })
            .init(initConfig);
    }

    return activityGraphFactory;
});
