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
    'core/encoder/time',
    'ui/component',
    'ui/hider',
    'taoProctoring/component/extraTime/encoder',
    'tpl!taoProctoring/component/extraTime/extraTime',
    'ui/modal'
], function ($, _, __, timeEncoder, component, hider, encodeExtraTime, extraTimeTpl) {
    'use strict';

    /**
     * Some default config
     * @type {Object}
     * @private
     */
    var _defaults = {
        unit: 60,           // default time unit is minutes
        deniedResources: []
    };

    /**
     * Creates a form that manage the extra time allowed to test takers
     *
     * @param {Object} config
     * @param {String} config.actionName - the action name (use in the title text)
     * @param {Array} config.allowedResources - list of allowed resources to be displayed
     * @param {Array} [config.deniedResources] - list of denied resources to be displayed
     * @param {Number} [config.unit] - the time is stored in seconds, but can be handled in other time units, by default this is minutes (60)
     * @fires cancel when the component is closed without validation
     * @fires ok when the ok button is clicked
     */
    function extraTimeFactory(config) {
        var initConfig = _.defaults(config || {}, _defaults);
        var timeUnit = initConfig.unit || _defaults.unit;

        _.forEach(initConfig.allowedResources, function(resource) {
            var remaining = parseFloat(resource.remaining) || 0;
            var extraTime = parseFloat(resource.extraTime);
            var consumedTime = parseFloat(resource.consumedTime);

            if (remaining) {
                resource.remainingStr = timeEncoder.encode(remaining);
            }

            resource.extraTimeStr = encodeExtraTime(extraTime, consumedTime, __('%s minutes more'), timeUnit);
        });

        return component()
            .setTemplate(extraTimeTpl)
            .on('render', function () {
                var self = this;
                var $cmp = this.getElement();
                var $time = $cmp.find('[data-control="time"]');
                var $error = $cmp.find('.feedback-error');
                var $ok = $cmp.find('[data-control="done"]');

                /**
                 * Validate the input time
                 * @returns {Boolean}
                 */
                function checkInputError() {
                    var value = $time.val().trim();

                    // use Number() instead of parseInt/parseFloat to prevent number with text like "10$$" to be
                    // converted to number, as we need to avoid that case
                    var time = Number(value);

                    // here we also check with the parseFloat to detect non decimal notation,
                    // otherwise numbers like 0x10 will be accepted, but misunderstood when applied
                    var error = isNaN(time) || time !== parseFloat(value);

                    if (error) {
                        hider.show($error);
                        $ok.attr('disabled', true);
                        focus();
                    } else {
                        $ok.removeAttr('disabled');
                        hider.hide($error);
                    }

                    return error;
                }

                /**
                 * Submit the data
                 */
                function submit() {
                    if (!checkInputError()) {
                        /**
                         * The user has input a time and submitted the data
                         * @event ok
                         * @param {Number} time
                         */
                        self.trigger('ok', parseFloat($time.val()) * timeUnit);
                        $cmp.modal('close');
                    }
                }

                /**
                 * Cancel the dialog
                 */
                function cancel() {
                    /**
                     * The dialog is just canceled
                     * @event cancel
                     */
                    self.trigger('cancel');
                    $cmp.modal('close');
                }

                /**
                 * Set the focus on the input
                 */
                function focus() {
                    $time.focus().select();
                }

                // we need to find the common extra time for all selected test takers
                $time.val(_.reduce(initConfig.allowedResources, function(time, testTaker) {
                    return Math.max(time, testTaker && testTaker.extraTime || 0);
                }, 0) / timeUnit);

                $cmp
                    .addClass('modal')
                    .on('closed.modal', function () {
                        self.destroy();
                    })
                    .on('change', $time, function() {
                        checkInputError();
                    })
                    .on('keyup', function(event) {
                        if (13 === event.keyCode) {
                            submit();
                        } else {
                            checkInputError();
                        }
                    })
                    .on('click', '.action', function (event) {
                        var $btn = $(event.target).closest('.action');
                        var control = $btn.data('control');

                        event.preventDefault();

                        if ('done' === control) {
                            submit();
                        } else {
                            cancel();
                        }
                    })
                    .modal({
                        width: 800
                    });

                focus();
            })
            .on('destroy', function () {
                this.getElement()
                    .removeClass('modal')
                    .modal('destroy');
            })
            .init(initConfig);
    }

    return extraTimeFactory;
});
