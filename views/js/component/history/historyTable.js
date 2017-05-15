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
    'ui/component',
    'ui/datatable'
], function ($, _, __, component) {
    'use strict';

    /**
     * Some default config
     * @type {Object}
     * @private
     */
    var _defaults = {
        sortBy: 'timestamp',
        sortOrder: 'desc'
    };

    /**
     * Renders the event details
     * @param {String|Array} details
     * @returns {String}
     */
    function renderDetails(details) {
        if (_.isArray(details)) {
            details = details.join('<br />');
        }
        return details;
    }

    /**
     * Renders a history table
     * @param {Object} config
     * @param {String} config.service - The URL of the service providing the data
     * @param {Object} [config.params] - A list of additional parameters to provide to the service
     * @param {String} [config.sortBy] - The default sorted column
     * @param {String} [config.sortOrder] - The default sort direction
     * @param {Array} [config.tools] - A list of optional tools, using the datable format
     * @param {Object} [data] - The first data set
     * @returns {*}
     */
    function historyTableFactory(config, data) {
        var initConfig = _.defaults(config || {}, _defaults);

        // define some additional behavior
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
                var self = this;
                var tools = [{
                    id: 'refresh',
                    icon: 'reset',
                    title: __('Refresh the page'),
                    label: __('Refresh'),
                    action: function() {
                        self.refresh();
                    }
                }];
                this.getElement()
                    .on('query.datatable', function() {
                        self.trigger('loading');
                    })
                    .on('load.datatable', function() {
                        self.trigger('loaded');
                    })
                    .on('error.datatable', function (e, err) {
                        self.trigger('error', err);
                    })
                    .datatable({
                        url: initConfig.service,
                        params: initConfig.params,
                        sortby: initConfig.sortBy,
                        sortorder: initConfig.sortOrder,
                        status: {
                            empty: __('No history to display!'),
                            available: __('Available history'),
                            loading: __('Loading')
                        },
                        selectable: !!(initConfig.tools && _.find(initConfig.tools, {massAction: true})),
                        tools: tools.concat(initConfig.tools || []),
                        model: [{
                            id: 'date',
                            label: __('Date'),
                            sortable: true
                        }, {
                            id: 'role',
                            label: __('Role'),
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
                            transform: renderDetails
                        }]
                    }, data);
            })
            .init(initConfig);
    }

    return historyTableFactory;
});
