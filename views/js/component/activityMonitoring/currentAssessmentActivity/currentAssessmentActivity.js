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
    'tpl!taoProctoring/component/activityMonitoring/currentAssessmentActivity/currentAssessmentActivity'
], function ($, _, __, component, tpl) {
    'use strict';

    /**
     * Default options
     * @type {Object}
     * @private
     */
    var _defaults = {
        assessments: {
            awaiting: {
                container: 'awaiting-assessments',
                label: __('Awaiting'),
                value: 0
            },
            authorized: {
                container: 'authorized-but-not-started-assessments',
                label: __('Authorized'),
                value: 0
            },
            current: {
                container: 'current-assessments',
                label: __('Total Current Assessments'),
                value: 0,
            },
            inProgress: {
                container: 'in-progress-assessments',
                label: __('In Progress'),
                value: 0
            },
            paused: {
                container: 'paused-assessments',
                label: __('Paused'),
                value: 0
            }
        }
    };

    /**
     * Factory for component
     *
     * @param {Object} [config]
     * @param {String} [config.assessments.awaiting.container]
     * @param {String} [config.assessments.awaiting.label]
     * @param {Number} [config.assessments.awaiting.value]
     * @param {String} [config.assessments.authorized.container]
     * @param {String} [config.assessments.authorized.label]
     * @param {Number} [config.assessments.authorized.value]
     * @param {String} [config.assessments.current.container]
     * @param {String} [config.assessments.current.label]
     * @param {Number} [config.assessments.current.value]
     * @param {String} [config.assessments.inProgress.container]
     * @param {String} [config.assessments.inProgress.label]
     * @param {Number} [config.assessments.inProgress.value]
     * @param {String} [config.assessments.paused.container]
     * @param {String} [config.assessments.paused.label]
     * @param {Number} [config.assessments.paused.value]
     * @param {jQuery} [config.renderTo] - Container of component
     */
    function currentAssessmentActivityFactory(config) {
        config = config || {};

        return component({
            /**
             * Update component variables
             *
             * @param {Object} assessments
             * @param {Number} [assessments.awaiting.value]
             * @param {Number} [assessments.authorized.value]
             * @param {Number} [assessments.current.value]
             * @param {Number} [assessments.inProgress.value]
             * @param {Number} [assessments.paused.value]
             */
            update: function update(data) {
                // Update the component config values
                _.merge(this.config.assessments, data);

                // Update the template variables
                _.each(this.config.assessments, function(val) {
                    $('.' + val.container, this.getElement())
                    .text(val.value);
                }, this);
            }
        }, _defaults)

        .setTemplate(tpl)

        .init(config);
    }

    return currentAssessmentActivityFactory;
});
