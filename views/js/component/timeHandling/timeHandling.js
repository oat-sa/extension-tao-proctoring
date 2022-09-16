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
                const defaultTime = 1;
                const state = {
                    reasons: null,
                    comment: '',
                    time: 0,
                };

                /**
                 * Remove errors messages
                 * @returns undefined
                 */
                function clearErrors() {
                    const $error = $('.feedback-error', $cmp);
                    hider.hide($error);
                    $error.remove();
                }

                /**
                 * Show error messages
                 * @param {string[]} errors
                 * @returns undefined
                 */
                function renderErrors(errors) {
                    const errorMessages = [];

                    if ($cmp) {
                        errors.forEach( error => {
                            errorMessages.push(`<div class="feedback-error small">${error}</div>`);
                        });

                        $cmp.find('.errors').prepend(errorMessages.join(''));
                        hider.show($error);
                    }
                }

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
                        || (config.changeTimeMode && parseFloat(value) <= 0);
                    const timeUnit = config.unit;
                    const errList = [];
                    let errs = error;
                    let resError = error;

                    // add shared error once, but update each session message
                    _.forEach(config.allowedResources, (resource) => {
                        if (error) {
                            if (errList.length === 0) {
                                errList.push(config.errorMessage);
                            }
                        }
                    });

                    // add messages about separated errors
                    _.forEach(config.allowedResources, (resource) => {
                        if (resource.timeAdjustmentLimits) {
                            const remainingTime = Math.floor(resource.remaining_time) || 0;
                            const limitTime = Math.floor(resource.timeAdjustmentLimits.decrease + resource.timeAdjustmentLimits.increase) || 0;

                            const tooMuch = (changeTimeOperator === '')
                                && (resource.timeAdjustmentLimits.increase !== -1)
                                && (resource.timeAdjustmentLimits.increase < timeUnit*value);
                            const tooFew = (changeTimeOperator === '-') && (timeUnit*value > resource.remaining_time);

                            resError = error || tooMuch || tooFew;

                            if (typeof remainingTime !== 'undefined') {
                                resource.remainingStr = timeEncoder.encode(remainingTime);
                                resource.timeLimitsStr = timeEncoder.encode(limitTime);
                                switch (true) {
                                    case error:
                                        resource.errorLabel = __('Entered value is not correct');
                                        break;
                                    case tooFew:
                                        errList.unshift(__('The decreased time cannot be higher than remaining time %s', resource.remainingStr));
                                        resource.errorLabel = __('Time decrease is too high');
                                        break;
                                    case tooMuch:
                                        errList.unshift(__('The increased time, when added to the remaining time, %s cannot be higher than the overall time granted for this timer %s', resource.remainingStr, resource.timeLimitsStr));
                                        resource.errorLabel = __('Time increase is too high');
                                        break;
                                    default:
                                        resource.errorLabel = '';
                                }
                            }

                            $(`LI[data-resource="${resource.id}"] .error`, $cmp).remove();
                            if (resError) {
                                const $resError = $('<span class="error"></span>').text(' - ' + resource.errorLabel);
                                $(`LI[data-resource="${resource.id}"] .resource-label`, $cmp).append($resError);
                            }

                            errs = errs || resError;
                        }
                    });

                    if (errs) {
                        renderErrors(errList);
                        focus();
                    }

                    $ok.attr('disabled', (errs || value.length === 0));

                    return errs;
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
                    if ($cmp) {
                        if (!checkRequiredFields($cmp)) {
                            renderErrors([
                                __('All fields are required')
                            ]);
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

                    clearErrors();
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
                                    clearErrors();
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

                $time.on('input', function() {
                    clearErrors();
                    checkInputError();
                });

                $changeTimeControls.on('change', ({ target: { value } }) => {
                    changeTimeOperator = value;
                    if($time.val().length !== 0) {
                        clearErrors();
                        checkInputError();
                    } else {
                        $time.val(defaultTime);
                    }
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
