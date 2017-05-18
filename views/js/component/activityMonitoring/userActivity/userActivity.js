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
    'tpl!taoProctoring/component/activityMonitoring/userActivity/userActivity'
], function ($, _, __, component, tpl) {
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
            value: 0,
        },
        activeTestTakers: {
            container: 'active-test-takers',
            label: __('Active test-takers'),
            value: 0
        }
    };

    /**
     * Factory for component
     *
     * @param {Object} config
     * @param {String} [config.activeProctors.container]
     * @param {String} [config.activeProctors.label]
     * @param {Number} [config.activeProctors.value]
     * @param {String} [config.activeTestTakers.container]
     * @param {String} [config.activeTestTakers.label]
     * @param {Number} [config.activeTestTakers.value]
     * @param {jQuery} [config.renderTo] - Container of component
     */
    function userActivityFactory($container, config) {
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
                _.merge(this.config, data);

                $('.'+this.config.activeProctors.container, this.getElement())
                .text(this.config.activeProctors.value);

                $('.'+this.config.activeTestTakers.container, this.getElement())
                .text(this.config.activeTestTakers.value);
            }
        }, _defaults)

        .setTemplate(tpl)

        .init(config);
    }

    return userActivityFactory;
});
