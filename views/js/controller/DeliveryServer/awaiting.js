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
    'moment',
    'layout/loading-bar',
    'ui/listbox',
    'core/polling',
    'tpl!taoProctoring/templates/deliveryServer/authorizationSuccess'
], function (_, $, __, helpers, moment, loadingBar, listBox, polling, authSuccessTpl){
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
            var runDeliveryUrl = helpers._url('runDeliveryExecution', 'DeliveryServer', 'taoProctoring', {deliveryExecution : config.deliveryExecution});
            var boxes = [{
                    id : 'goToDelivery',
                    label : config.deliveryLabel,
                    url : runDeliveryUrl,
                    content : __('Please wait, authorization in process ...'),
                    text : __('Proceed')
                }];
            var list = listBox({
                title : config.deliveryInit ? __('Start Delivery') : __('Resume Delivery'),
                textEmpty : '',
                textNumber : '',
                textLoading : '',
                renderTo : $container,
                list : boxes,
                width : 12
            });
            var $content = $container.find('.listbox .content');


            loadingBar.start();

            function authorized(){
                loadingBar.stop();
                //@todo it would be nice to smoothen the transition
                $container.removeClass('authorization-in-progress');
                $content.html(authSuccessTpl({message : __('Authorization done. You may proceed now.')}));
            }

            polling({
                action : function (){
                    var async = this.async();
                    $.get(isAuthorizedUrl, function(result){
                        if(result.authorized){
                            // stop immediately the polling
                            async.reject();
                            authorized();
                        }else{
                            // continue the polling
                            async.resolve();
                        }
                    });
                },
                interval : refreshPolling,
                autoStart : true
            });
            
        }
    };

    return awaitingAuthorizationCtlr;
});
