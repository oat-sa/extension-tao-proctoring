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
    'ui/component',
    'tpl!taoProctoring/component/dateRange/form',
    'util/locale',
    'jquery.timePicker'
], function ($, _, component, formTpl, locale) {
    'use strict';

    var dateFormat = locale.getDateTimeFormat().split(" ");
    var dateFormatStr = dateFormat[0];
    dateFormatStr = dateFormatStr.replace('YYYY', 'yy');
    dateFormatStr = dateFormatStr.replace('MM', 'mm');
    dateFormatStr = dateFormatStr.replace('DD', 'dd');

    /**
     * Some default config
     * @type {Object}
     * @private
     */
    var _defaults = {
        dateFormat: dateFormatStr
    };
    /**
     * Creates a dates range with date pickers
     *
     * @param {Object} config
     * @param {String} [config.start] - The initial start date (default: none)
     * @param {String} [config.end] - The initial end date (default: none)
     * @param {String} [config.dateFormat] - The date picker format (default: 'yy-mm-dd')
     * @fires change when any date is changed
     * @fires submit when the submit button is clicked
     */
    function dateRangeFactory(config){
        var initConfig = _.defaults(config || {}, _defaults);
        var periodStart = initConfig.start || '';
        var periodEnd = initConfig.end || '';

        var dateRange = {
            /**
             * Gets the start date of the range
             * @returns {String}
             */
            getStart: function getStart() {
                return periodStart;
            },

            /**
             * Gets the end date of the range
             * @returns {String}
             */
            getEnd: function getEnd() {
                return periodEnd;
            }
        };

        initConfig.start = periodStart;
        initConfig.end = periodEnd;

        return component(dateRange)
            .setTemplate(formTpl)
            .on('render', function() {
                var self = this;
                var $form = this.getElement();
                var $periodStart = $form.find('input[name=periodStart]');
                var $periodEnd = $form.find('input[name=periodEnd]');
                var $filterBtn = $form.find('[data-control="filter"]');

                $periodStart.datepicker({
                    dateFormat: initConfig.dateFormat,
                    autoSize: true
                }).change(function(){
                    periodStart = $periodStart.val();
                    $periodEnd.datepicker('option', 'minDate', periodStart);

                    /**
                     * @event change
                     * @param {String} property
                     * @param {String} value
                     */
                    self.trigger('change', 'start', periodStart);
                });

                $periodEnd.datepicker({
                    dateFormat: initConfig.dateFormat,
                    autoSize: true
                }).change(function(){
                    periodEnd = $periodEnd.val();
                    $periodStart.datepicker('option', 'maxDate', periodEnd);

                    /**
                     * @event change
                     * @param {String} property
                     * @param {String} value
                     */
                    self.trigger('change', 'end', periodEnd);
                });

                $filterBtn.on('click', function(event) {
                    event.preventDefault();

                    periodStart = $periodStart.val();
                    periodEnd = $periodEnd.val();

                    /**
                     * @event submit
                     */
                    self.trigger('submit');
                });
            })
            .init(initConfig);
    }

    return dateRangeFactory;
});
