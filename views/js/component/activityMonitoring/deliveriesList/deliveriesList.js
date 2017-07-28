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

define([
    'jquery',
    'i18n',
    'util/url',
    'ui/component',
    'ui/datatable'
], function ($, __, url, component) {
    'use strict';

    /**
     * Default options
     * @type {Object}
     * @private
     */
    var _defaults = {
        model: [
            {
                id: 'label',
                label: __('Delivery'),
                sortable : true
            },
            {
                id: 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusAwaiting',
                label: __('Awaiting'),
                sortable : true,
                transform: function(value) {return value.toString();}
            },
            {
                id: 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusAuthorized',
                label: __('Authorized'),
                sortable : true,
                transform: function(value) {return value.toString();}
            },
            {
                id: 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusPaused',
                label: __('Paused'),
                sortable : true,
                transform: function(value) {return value.toString();}
            },
            {
                id: 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusActive',
                label: __('Active'),
                sortable : true,
                transform: function(value) {return value.toString();}
            },
            {
                id: 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusTerminated',
                label: __('Terminated'),
                sortable : true,
                transform: function(value) {return value.toString();}
            },
            {
                id: 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusCanceled',
                label: __('Canceled'),
                sortable : true,
                transform: function(value) {return value.toString();}
            },
            {
                id: 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusFinished',
                label: __('Finished'),
                sortable : true,
                transform: function(value) {return value.toString();}
            },
        ]
    };

    /**
     * Factory for component
     *
     * @param {Object} [config]
     * @param {jQuery} [config.renderTo] - Container of component
     */
    function deliveriesListFactory(config) {
        config = config || {};

        return component({
            /**
             * Update component datatable
             */
            update: function update() {
                this.$datatable.datatable('refresh');
            }
        }, _defaults)

        .on('render', function () {
            this.$datatable = this.getElement().datatable({
                url:                      url.route('deliveriesActivityData', 'Tools', 'taoProctoring'),
                filter:                   false,
                model:                    this.config.model,
                paginationStrategyTop:    'none',
                paginationStrategyBottom: 'none',
                selectable:               true,
                sortorder:                'asc',
                sortby:                   'label'
            }, this.config.model);
        })

        .init(config);
    }

    return deliveriesListFactory;
});
