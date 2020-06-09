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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
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
    'taoProctoring/component/timeHandling/encoder',
    'tpl!taoProctoring/component/timeHandling/timeHandling',
    'ui/modal'
], function ($, _, __, timeEncoder, component, hider, encodeExtraTime, extraTimeTpl) {
    'use strict';

    /**
     * Some default config
     * @type {Object}
     * @private
     */
    const _defaults = {
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
     * @param {Boolean} [config.changeTimeMode] - mode for change time action
     * @fires cancel when the component is closed without validation
     * @fires ok when the ok button is clicked
     */
    function timeHandlingFactory(config) {
        const initConfig = _.defaults(config || {}, _defaults);
        const timeUnit = initConfig.unit || _defaults.unit;

        _.forEach(initConfig.allowedResources, (resource) => {
            const remaining = parseFloat(resource.remaining) || 0;
            const extraTime = parseFloat(resource.extraTime);
            const consumedTime = parseFloat(resource.consumedTime);

            if (remaining) {
                resource.remainingStr = timeEncoder.encode(remaining);
            }

            resource.extraTimeStr = encodeExtraTime(extraTime, consumedTime, __('%s minutes more'), timeUnit);
        });

        return component()
            .setTemplate(extraTimeTpl)
            .on('render', function () {
                const self = this;
                const $cmp = this.getElement();
                const $time = $cmp.find('[data-control="time"]');
                const $error = $cmp.find('.feedback-error');
                const $ok = $cmp.find('[data-control="done"]');
                const $changeTimeControls = $cmp.find('input[name="changeTimeControl"]');
                let changeTimeOperator = '';
                const state = {
                    reasons: null,
                    comment: '',
                    time: 0,
                };

                /**
                 * Validate the input time
                 * @returns {Boolean}
                 */
                function checkInputError() {
                    const value = $time.val().trim();

                    // use Number() instead of parseInt/parseFloat to prevent number with text like "10$$" to be
                    // converted to number, as we need to avoid that case
                    const time = Number(value);

                    // here we also check with the parseFloat to detect non decimal notation,
                    // otherwise numbers like 0x10 will be accepted, but misunderstood when applied
                    const error = isNaN(time)
                        || time !== parseFloat(value)
                        || (config.changeTimeMode && parseFloat(value) === 0);

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
                 * Validates that all required fields have been filled
                 * @param {jQuery} $container
                 * @returns {Boolean}
                 */
                function checkRequiredFields($container) {
                    return (
                        $('select, textarea', $container).filter(function() {
                            return $.trim($(this).val()).length === 0;
                        }).length === 0
                    );
                }

                function checkReasonError() {
                    const $element = self.getElement();

                    if ($element) {
                        $('.feedback-error', $element).remove();
                        if (!checkRequiredFields($element)) {
                            const $error = $('<div class="feedback-error small"></div>').text(__('All fields are required'));
                            $element.find('.actions').prepend($error);
                            return true;
                        }
                    }
                    return false;
                }

                /**
                 * Submit the data
                 */
                function submit() {
                    if (!initConfig.allowedResources.length) {
                        $cmp.modal('close');
                        return;
                    }

                    if (!checkInputError() && !checkReasonError()) {
                        state.time = parseFloat(`${changeTimeOperator}${$time.val()}`) * timeUnit;
                        self.trigger('ok', state);
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
                if (!config.changeTimeMode) {
                    $time.val(_.reduce(initConfig.allowedResources, function(time, testTaker) {
                        return Math.max(time, testTaker && testTaker.extraTime || 0);
                    }, 0) / timeUnit);
                }

                $cmp
                    .addClass('modal')
                    .on('closed.modal', function () {
                        self.destroy();
                    })
                    .on('selected.cascading-combobox', (e, reasons) => {
                        state.reasons = reasons;
                        self.trigger('change', state);
                    })
                    .on('change', 'textarea', (e) => {
                        state.comment = $(e.currentTarget).val();
                        self.trigger('change', state);
                    })
                    .on('keyup', function(event) {
                        if (13 === event.keyCode) {
                            submit();
                        } else {
                            if (
                                event.hasOwnProperty('target')
                                && event.target.hasOwnProperty('id')
                                && event.target.id === 'input-extra-time') {
                                    checkInputError();
                            }
                        }
                    })
                    .on('click', '.action', function (event) {
                        const $btn = $(event.target).closest('.action');
                        const control = $btn.data('control');

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

                if (_.isObject(this.config.categoriesSelector)) {
                    const $reason = $cmp.find('.reason');
                    const $reasonCategories = $reason.children('.categories');
                    this.config.categoriesSelector
                        .on('render', () => {
                            if (initConfig.hasOwnProperty('predefinedReason')) {
                                const predefinedReason = initConfig.predefinedReason;
                                if (predefinedReason.hasOwnProperty('comment')) {
                                    const $textarea = $('textarea', $reason);
                                    $textarea.text(predefinedReason.comment);
                                    $textarea.trigger('change');
                                }
                                if (predefinedReason.hasOwnProperty('reasons')) {
                                    const reasons = predefinedReason.reasons;
                                    if (reasons.hasOwnProperty('category')) {
                                        $('select[data-id="category"]', $reasonCategories)
                                            .val(reasons.category)
                                            .on('change', () => {
                                                _.defer(() => {
                                                    if (reasons.hasOwnProperty('subCategory')) {
                                                        $('select[data-id="subCategory"]', $reasonCategories)
                                                            .val(reasons.subCategory)
                                                            .trigger('change');
                                                    }
                                                })
                                            })
                                            .trigger('change');
                                    }
                                }
                            }
                        })
                        .render($reasonCategories);
                }

                $time.on('change', function() {
                  checkInputError();
                });

                $changeTimeControls.on('change', ({ target: { value } }) => {
                  changeTimeOperator = value;
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

    return timeHandlingFactory;
});
