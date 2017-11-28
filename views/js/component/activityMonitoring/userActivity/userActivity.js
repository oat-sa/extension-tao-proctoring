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
    'lodash',
    'i18n',
    'ui/component',
    'handlebars',
    'tpl!taoProctoring/component/activityMonitoring/userActivity/userActivityBlock',
    'tpl!taoProctoring/component/activityMonitoring/userActivity/userActivity'
], function ($, _, __, component, Handlebars, groupTpl, componentTpl) {
    'use strict';

    /**
     * Default options
     * @type {Object}
     * @private
     */
    var _defaults = {
        activeProctors: {
            container: 'active-proctors',
            label: __('Active proctors'),
            size: 4,
            icon: 'taker',
            value: 0
        },
        activeTestTakers: {
            container: 'active-test-takers',
            label: __('Active test-takers'),
            size: 4,
            icon: 'takers',
            value: 0
        }
    };

    Handlebars.registerPartial('ui-activity-widget-group', groupTpl);

    /**
     * Factory for component
     *
     * @param {Object} config
     * @param {String} [config.<WidgetName>.container]
     * @param {String} [config.<WidgetName>.label]
     * @param {Number} [config.<WidgetName>.value]
     * @param {Number} [config.<WidgetName>.size]
     * @param {String} [config.<WidgetName>.icon]
     * @param {jQuery} [config.renderTo] - Container of component
     */
    function userActivityFactory(config) {
        config = config || {};

        return component({
            /**
             * Update component variables
             *
             * @param {Object} data
             * @param {Number} [data.activeProctors.value]
             * @param {Number} [data.activeTestTakers.value]
             */
            update: function update(data) {
                var metricsWidget;
                _.merge(this.config, data);

                for (metricsWidget in this.config) {
                    if (this.config.hasOwnProperty(metricsWidget)) {
                        $('.'+this.config[metricsWidget].container, this.getElement())
                            .text(this.config[metricsWidget].value);
                    }
                }
            }
        }, _defaults)

        .setTemplate(componentTpl)

        .init(config);
    }

    return userActivityFactory;
});
