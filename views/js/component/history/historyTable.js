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
    'layout/loading-bar',
    'ui/component',
    'tpl!taoProctoring/component/history/details',
    'tpl!taoProctoring/component/history/context',
    'ui/datatable'
], function ($, _, __, loadingBar, component, detailsTpl, contextTpl) {
    'use strict';

    /**
     * Some default config
     * @type {Object}
     * @private
     */
    var _defaults = {};

    /**
     * Renders the event details
     * @param {Object} details
     * @param {Object} row
     * @returns {String}
     */
    function renderDetails(details, row) {
        return detailsTpl({
            value: details,
            row: row
        });
    }

    /**
     * Renders the event context
     * @param {Object} context
     * @param {Object} row
     * @returns {String}
     */
    function renderContext(context, row) {
        return contextTpl({
            value: context,
            row: row
        });
    }

    /**
     * Renders a history table
     * @param {Object} config
     * @param {String} config.service - The URL of the service providing the data
     * @param {Object} [config.params] - A list of additional parameters to provide to the service
     * @param {Object} [data] - The first data set
     * @returns {*}
     */
    function historyTableFactory(config, data) {
        var initConfig = _.defaults(config || {}, _defaults);

        //
        var historyTable = {
            /**
             * Refresh the table
             * @param {Object} [params]
             */
            refresh: function refresh(params) {
                var $element;

                if (this.is('rendered')) {
                    $element = this.getElement();
                    if (params) {
                        $element.datatable('options', {
                            params: params
                        });
                    }
                    $element.datatable('refresh');
                } else {
                    if (params) {
                        initConfig.params = _.merge(initConfig.params, params);
                    }
                }
            }
        };

        return component(historyTable)
            .on('render', function() {
                this.getElement()
                    .on('query.datatable', function() {
                        loadingBar.start();
                    })
                    .on('load.datatable', function() {
                        loadingBar.stop();
                    })
                    .datatable({
                        url: initConfig.service,
                        params: initConfig.params,
                        model: [{
                            id: 'timestamp',
                            label: __('Timestamp'),
                            sortable: true
                        }, {
                            id: 'session',
                            label: __('Test'),
                            sortable: true
                        }, {
                            id: 'actor',
                            label: __('Actor'),
                            sortable: true
                        }, {
                            id: 'event',
                            label: __('Event'),
                            sortable: true
                        }, {
                            id: 'details',
                            label: __('Details'),
                            transform: renderDetails
                        }, {
                            id: 'context',
                            label: __('Context'),
                            transform: renderContext
                        }]
                    }, data);
            })
            .init(initConfig);
    }

    return historyTableFactory;
});
