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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA ;
 */
/**
 * @author Jean-Sébastien Conan <jean-sebastien.conan@vesperiagroup.com>
 */
define([
    'lodash',
    'jquery',
    'i18n',
    'helpers',
    'layout/loading-bar',
    'ui/dialog/alert',
    'core/polling',
    'taoQtiTest/testRunner/resumingStrategy/keepAfterResume',
    'ui/dialog/confirm',
    'tpl!taoProctoring/templates/deliveryServer/authorizationSuccess',
    'tpl!taoProctoring/templates/deliveryServer/authorizationListBoxActions',
    'tpl!taoProctoring/templates/deliveryServer/authorizationEntryPoint',
    'util/clipboard',
    'ui/component',
], function (_, $, __, helpers, loadingBar, dialogAlert, polling, keepAfterResume, dialogConfirm, authSuccessTpl, listBoxActionsTpl, authorizationEntryPointTpl, clipboard) {
    'use strict';

    /**
     * The polling delay used to refresh the list
     * @type {Number}
     */
    const refreshPolling = 10 * 1000; // once every 10 seconds

    /**
     * The CSS scope
     * @type {String}
     */
    const cssScope = '.awaiting-authorization';

    /**
     * Controls the ProctorDelivery index page
     *
     * @type {Object}
     */
    const awaitingAuthorizationCtlr = {
        /**
         * Entry point of the page
         */
        start: function start(config) {
            const $container = $(cssScope);
            const isAuthorizedUrl = helpers._url('isAuthorized', 'DeliveryServer', 'taoProctoring', { deliveryExecution: config.deliveryExecution });
            const runDeliveryUrl = config.runDeliveryUrl;
            const spaceEnterKeyCodes = [13, 32];

            $container.html(authorizationEntryPointTpl({ label: config.deliveryLabel }));
            $container
                .find('.authorization-actions')
                .html(listBoxActionsTpl({ id: config.deliveryExecution, cancelable: config.cancelable }));

            const $content = $container.find('.authorization-status');
            const $proccedButton = $container.find('.js-proceed');
            let deliveryStarted = false;

            const runDelivery = () => {
                loadingBar.start();

                // make sure that the delivery is still allowed (before redirect)
                $.get(isAuthorizedUrl, (result) => {
                    if (!result.success) {
                        if (result.message) {
                            dialogAlert(result.message, exit);
                        } else {
                            exit();
                        }
                    } else if (result.authorized) {
                        clipboard.clean();
                        deliveryStarted = true;
                        window.location.href = runDeliveryUrl;
                    } else {
                        dialogAlert(__('Unexpected response'), exit);
                    }
                });
            };

            const isRunnable = () => {
                return !$container.hasClass('authorization-in-progress') && !deliveryStarted;
            };

            /**
             * Function to be called when the delivery execution has been authorized
             */
            const authorized = () => {
                $content.html(authSuccessTpl({ message: __('Authorized, you may proceed') }));
                loadingBar.stop();
                $container.addClass('authorization-granted').removeClass('authorization-in-progress');

                // Enable procced button proccedButton
                $proccedButton.removeClass('dissabled-action');
                $proccedButton.attr('aria-disabled', false);
            }

            /**
             * Goes back to the delivery index
             */
            const exit = () => {
                window.location.href = config.returnUrl;
            }

            // Disable procced button proccedButton
            $proccedButton.addClass('dissabled-action');
            $proccedButton.attr('aria-disabled', true);

            // we need to reset the local timer to avoid loss of time inside the assessment test session
            keepAfterResume().reset();

            loadingBar.start(false);

            $container.on('click', '.js-cancel', () => {
                dialogConfirm(
                    __('Are you sure you want to end the test?'),
                    function () {
                        window.location.href = config.cancelUrl;
                    }
                );
            });

            $container.on('keyup', '.js-cancel', (e) => {
                if (spaceEnterKeyCodes.includes(e.which)) {
                    dialogConfirm(
                        __('Are you sure you want to end the test?'),
                        function () {
                            window.location.href = config.cancelUrl;
                        }
                    );
                }
            });

            $container.on('click', '.js-proceed', () => {
                if (isRunnable()) {
                    runDelivery();
                }

                return false;
            });

            $container.on('keyup', '.js-proceed', (e) => {
                if (spaceEnterKeyCodes.includes(e.which)) {
                    if (isRunnable()) {
                        runDelivery();
                    }
                }

                return false;
            });

            polling({
                action: function () {
                    const async = this.async();

                    $.get(isAuthorizedUrl, (result) => {
                        let stop = false;

                        if (!result.success) {
                            stop = true;

                            if (result.message) {
                                dialogAlert(result.message, exit);
                            } else {
                                exit();
                            }
                        } else if (result.authorized) {
                            stop = true;

                            authorized();
                        }

                        if (stop) {
                            async.reject();
                        } else {
                            async.resolve();
                        }
                    });
                },
                interval: refreshPolling,
                autoStart: true
            })
                // Trigger the action immediately
                .next();
        }
    };

    return awaitingAuthorizationCtlr;
});
