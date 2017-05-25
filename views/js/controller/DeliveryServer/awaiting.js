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
    'ui/listbox',
    'ui/dialog/alert',
    'core/polling',
    'taoQtiTest/testRunner/resumingStrategy/keepAfterResume',
    'util/url',
    'ui/dialog/confirm',
    'tpl!taoProctoring/templates/deliveryServer/authorizationSuccess',
    'tpl!taoProctoring/templates/deliveryServer/authorizationListBoxActions'
], function (_, $, __, helpers, loadingBar, listBox, dialogAlert, polling, keepAfterResume, url, dialogConfirm, authSuccessTpl, listBoxActionsTpl){
    'use strict';

    /**
     * The polling delay used to refresh the list
     * @type {Number}
     */
    var refreshPolling = 10 * 1000; // once every 10 seconds

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.awaiting-authorization';

    /**
     * Controls the ProctorDelivery index page
     *
     * @type {Object}
     */
    var awaitingAuthorizationCtlr = {
        /**
         * Entry point of the page
         */
        start : function start(config){

            var $container = $(cssScope);
            var isAuthorizedUrl = helpers._url('isAuthorized', 'DeliveryServer', 'taoProctoring', {deliveryExecution : config.deliveryExecution});
            var runDeliveryUrl = config.runDeliveryUrl;
            var boxes = [{
                id : 'goToDelivery',
                label : config.deliveryLabel,
                url : runDeliveryUrl,
                content : __('Please wait, authorization in process ...'),
                html : listBoxActionsTpl({id : config.deliveryExecution, cancelable: config.cancelable})
            }];
            var list = listBox({
                title : '',
                textEmpty : '',
                textNumber : '',
                textLoading : '',
                renderTo : $container,
                list : boxes,
                width : 12
            });
            var $content = $container.find('.listbox .content');

            var deliveryStarted = false;
            var runDelivery = function runDelivery () {
                loadingBar.start();
                deliveryStarted = true;
                window.location.href = runDeliveryUrl;
            };
            var isRunnable = function isRunnable () {
                return !$container.hasClass('authorization-in-progress') && !deliveryStarted;
            };

            // we need to reset the local timer to avoid loss of time inside the assessment test session
            keepAfterResume().reset();

            loadingBar.start(false);

            $container.on('click', '.js-cancel', function (e) {
                //prevent clicking the parent link that goes to the monitoring screen
                e.stopPropagation();
                e.preventDefault();

                dialogConfirm(
                    __('Are you sure you want to end the test?'),
                    function () {
                        window.location.href = config.cancelUrl;
                    }
                );
            });
            $container.on('click', '.js-proceed', function (e) {
                if (isRunnable()) {
                    runDelivery();
                }
                return false;
            });

            $container.on('click', '.block.box', function (e) {
                if (isRunnable()) {
                    runDelivery();
                }
                return false;
            });

            polling({
                action : function (){
                    var async = this.async();
                    $.get(isAuthorizedUrl, function(result){
                        var stop = false;

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
                interval : refreshPolling,
                autoStart : true
            })
            // Trigger the action immediately
            .next();

            /**
             * Function to be called when the delivery execution has been authorized
             */
            function authorized(){
                $content.html(authSuccessTpl({message : __('Authorized, you may proceed')}));
                loadingBar.stop();
                $container.addClass('authorization-granted').removeClass('authorization-in-progress');
            }

            /**
             * Goes back to the delivery index
             */
            function exit() {
                window.location.href = config.returnUrl;
            }
        }
    };

    return awaitingAuthorizationCtlr;
});
