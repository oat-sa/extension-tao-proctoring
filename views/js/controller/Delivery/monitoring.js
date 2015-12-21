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
    'jquery',
    'lodash',
    'i18n',
    'helpers',
    'layout/loading-bar',
    'util/encode',
    'ui/feedback',
    'ui/dialog',
    'ui/bulkActionPopup',
    'taoProctoring/component/breadcrumbs',
    'taoProctoring/helper/status',
    'ui/cascadingComboBox',
    'tpl!taoProctoring/templates/delivery/itemProgress',
    'tpl!taoProctoring/templates/delivery/deliveryLink',
    'ui/datatable'
], function (
    $,
    _,
    __,
    helpers,
    loadingBar,
    encode,
    feedback,
    dialog,
    bulkActionPopup,
    breadcrumbsFactory,
    _status,
    cascadingComboBox,
    itemProgressTpl,
    deliveryLinkTpl
) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.delivery-monitoring';

    // the page is always loading data when starting
    loadingBar.start();

    /**
     * Formats a time value to string
     * @param {Number} time
     * @returns {String}
     * @private
     */
    var _timerFormat = function(time) {
        return __('%d min', Math.floor(time / 60));
    };

    var notYet = function() {
        dialog({
            message: __('Not yet implemented!'),
            autoRender: true,
            autoDestroy: true,
            buttons: 'ok'
        });
    };
    
    /**
     * Controls the taoProctoring delivery page
     *
     * @type {Object}
     */
    var proctorDeliveryIndexCtlr = {
        /**
         * Entry point of the page
         */
        start : function start() {
            var $container = $(cssScope);
            var $content = $container.find('.content');
            var $list = $container.find('.list');
            var crumbs = $container.data('breadcrumbs');
            var dataset = $container.data('set');
            var categories = $container.data('categories');
            var deliveryId = $container.data('delivery');
            var testCenterId = $container.data('testcenter');
            var manageUrl = helpers._url('manage', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var terminateUrl = helpers._url('terminateExecutions', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var pauseUrl = helpers._url('pauseExecutions', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var authoriseUrl = helpers._url('authoriseExecutions', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var reportUrl = helpers._url('reportExecutions', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var serviceUrl = helpers._url('deliveryExecutions', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var serviceAllUrl = helpers._url('allDeliveriesExecutions', 'Delivery', 'taoProctoring', {testCenter : testCenterId});
            var tools = [];
            var actions = [];
            var model = [];
            var actionButtons;
            var bc = breadcrumbsFactory($container, crumbs);

            // request the server with a selection of test takers
            function request(url, selection, reason, message) {
                if (selection && selection.length) {
                    loadingBar.start();

                    $.ajax({
                        url: url,
                        data: {
                            execution: selection,
                            reason: reason
                        },
                        dataType : 'json',
                        type: 'POST',
                        error: function() {
                            loadingBar.stop();
                        }
                    }).done(function(response) {
                        loadingBar.stop();

                        if (response && response.success) {
                            if (message) {
                                feedback().success(message);
                            }
                            $list.datatable('refresh');
                        } else {
                            feedback().error(__('Something went wrong ...') + '<br>' + encode.html(response.error), {encodeHtml: false});
                        }
                    });
                }
            }

            // request the server to authorise the selected delivery executions
            function authorise(selection) {
                execBulkAction('authorize', __('Authorize Session'), selection, function(selection, reason){
                    request(authoriseUrl, selection, reason, __('Sessions authorized'));
                });
            }

            // request the server to pause the selected delivery executions
            function pause(selection) {
                execBulkAction('pause', __('Pause Session'), selection, function(selection, reason){
                    request(pauseUrl, selection, reason, __('Sessions paused'));
                });
            }

            // request the server to terminate the selected delivery executions
            function terminate(selection) {
                execBulkAction('terminate', __('Terminate Session'), selection, function(selection, reason){
                    request(terminateUrl, selection, reason, __('Sessions terminated'));
                });
            }
            
            // report irregularities on the selected delivery executions
            function report(selection) {
                execBulkAction( 'report', __('Report Irregularity'), selection, function(selection, reason){
                    request(reportUrl, selection, reason, __('Sessions reported'));
                });
            }
            
            /**
             * Verify and reformat test taker data for the execBulkAction's need
             * @param {Object} testTakerData
             * @param {String} actionName
             * @returns {Object}
             */
            function verifyTestTaker(testTakerData, actionName){
                var formatted = {
                    id : testTakerData.id,
                    label : testTakerData.firstname+' '+testTakerData.lastname
                };
                var status = _status.getStatusByCode(testTakerData.state.status);
                if(status){
                    formatted.allowed = (status.can[actionName] === true);
                    if(!formatted.allowed){
                        formatted.reason = status.can[actionName];
                    }
                }
                return formatted;
            }
            
            /**
             * Find the execution row data from its uri
             * 
             * @param {String} uri
             * @returns {Object}
             */
            function getExecutionData(uri){
                return _.find(dataset.data, {id : uri});
            }
            
            /**
             * Exec 
             * @param {String} actionName
             * @param {String} actionTitle
             * @param {Array|String} selection
             * @param {Function} cb
             * @returns {undefined}
             */
            function execBulkAction(actionName, actionTitle, selection, cb){

                var allowedTestTakers = [];
                var forbiddenTestTakers = [];
                var _selection = _.isArray(selection) ? selection : [selection];
                var askForReason = (categories[actionName] && categories[actionName].categoriesDefinitions && categories[actionName].categoriesDefinitions.length);
                
                _.each(_selection, function(uri){
                    var testTaker = getExecutionData(uri);
                    var checkedTestTaker;
                    if(testTaker){
                        checkedTestTaker = verifyTestTaker(testTaker, actionName);
                        if(checkedTestTaker.allowed){
                            allowedTestTakers.push(checkedTestTaker);
                        }else{
                            forbiddenTestTakers.push(checkedTestTaker);
                        }
                    }
                });
                var config = {
                    renderTo : $content,
                    actionName : actionTitle,
                    reason : askForReason,
                    resourceType : 'test taker',
                    allowedResources : allowedTestTakers,
                    deniedResources : forbiddenTestTakers,

                    categoriesSelector: cascadingComboBox(categories[actionName])
                };
                
                bulkActionPopup(config).on('ok', function(reason){
                    //execute callback
                    if(_.isFunction(cb)){
                        cb(_selection, reason);
                    }
                });
            }

            // tool: page refresh
            tools.push({
                id: 'refresh',
                icon: 'reset',
                title: __('Refresh the page'),
                label: __('Refresh'),
                action: function() {
                    $list.datatable('refresh');
                }
            });

            // tool: manage test takers (only for unique delivery)
            if (deliveryId) {
                tools.push({
                    id: 'manage',
                    icon: 'property-advanced',
                    title: __('Manage sessions'),
                    label: __('Manage'),
                    action: function() {
                        location.href = manageUrl;
                    }
                });
            }

            // tool: authorise the executions
            tools.push({
                id: 'authorise',
                icon: 'play',
                title: __('Authorize sessions'),
                label: __('Authorise'),
                massAction: true,
                action: authorise
            });

            // tool: pause the executions
            tools.push({
                id: 'pause',
                icon: 'pause',
                title: __('Pause sessions'),
                label: __('Pause'),
                massAction: true,
                action: pause
            });

            // tool: terminate the executions
            tools.push({
                id: 'terminate',
                icon: 'stop',
                title: __('Terminate sessions'),
                label: __('Terminate'),
                massAction: true,
                action: terminate
            });

            // tool: report irregularities
            tools.push({
                id: 'irregularity',
                icon: 'delivery-small',
                title: __('Report irregularities'),
                label: __('Report'),
                massAction: true,
                action: report
            });

            // action: authorise the execution
            actions.push({
                id: 'authorise',
                icon: 'play',
                title: __('Authorize session'),
                hidden: function() {
                    var status;
                    if(this.state && this.state.status){
                        status = _status.getStatusByCode(this.state.status);
                        return !status || status.can.authorize !== true;
                    }
                    return true;
                },
                action: authorise
            });

            // action: pause the execution
            actions.push({
                id: 'pause',
                icon: 'pause',
                title: __('Pause session'),
                hidden: function() {
                    var status;
                    if(this.state && this.state.status){
                        status = _status.getStatusByCode(this.state.status);
                        return !status || status.can.pause !== true;
                    }
                    return true;
                },
                action: pause
            });

            // action: terminate the execution
            actions.push({
                id: 'terminate',
                icon: 'stop',
                title: __('Terminate session'),
                hidden: function() {
                    var status;
                    if(this.state && this.state.status){
                        status = _status.getStatusByCode(this.state.status);
                        return !status || status.can.terminate !== true;
                    }
                    return true;
                },
                action: terminate
            });

            // action: report irregularities
            actions.push({
                id: 'irregularity',
                icon: 'delivery-small',
                title: __('Report irregularity'),
                hidden: function() {
                    var status;
                    if(this.state && this.state.status){
                        status = _status.getStatusByCode(this.state.status);
                        return !status || status.can.report !== true;
                    }
                    return true;
                },
                action: report
            });

            // column: delivery (only for all deliveries view)
            if (!deliveryId) {
                model.push({
                    id: 'delivery',
                    label: __('Session'),
                    transform: function(value, row) {
                        var delivery = row && row.delivery;
                        if (delivery) {
                            delivery.url = helpers._url('monitoring', 'Delivery', 'taoProctoring', {delivery : delivery.uri, testCenter : testCenterId});
                            value = deliveryLinkTpl(delivery);
                        }
                        return value;

                    }
                });
            }

            // column: test taker first name
            model.push({
                id: 'firstname',
                label: __('First name'),
                transform: function(value, row) {
                    return row && row.testTaker && row.testTaker.firstName || '';

                }
            });

            // column: test taker last name
            model.push({
                id: 'lastname',
                label: __('Last name'),
                transform: function(value, row) {
                    return row && row.testTaker && row.testTaker.lastName || '';

                }
            });

            // column: test taker identifier
            model.push({
                id: 'identifier',
                label: __('Identifier'),
                transform: function(value, row) {
                    return row && row.testTaker && row.testTaker.id || '';
                }
            });

            // column: start time
            model.push({
                id: 'date',
                label: __('Started at')
            });

            // column: delivery execution status
            model.push({
                id: 'status',
                label: __('Status'),
                transform: function(value, row) {
                    if(row && row.state && row.state.status){
                        var status = _status.getStatusByCode(row.state.status);
                        if(status){
                            return status.label;
                        }
                    }
                    return '';
                }
            });

            // column: delivery execution progress
            model.push({
                id: 'progress',
                label: __('Progress'),
                transform: function(value, row) {
                    var state = row && row.state;
                    var item = state && state.item;
                    var time = item && item.time;
                    if (time && time.elapsed) {
                        time.elapsedStr = _timerFormat(time.elapsed);
                        time.totalStr = _timerFormat(time.total);
                        time.display = !!(time.elapsedStr || time.totalStr);
                    }
                    return itemProgressTpl(state);
                }
            });
            
            // renders the datatable
            $list
                .on('query.datatable', function() {
                    loadingBar.start();
                })
                .on('load.datatable', function(e, newDataset) {
                    //update dateset in memory
                    dataset = newDataset;
                    
                    //udate the buttons, which have been reconstructed
                    actionButtons = _({
                        authorize : $list.find('.action-bar').children('.tool-authorise'),
                        pause : $list.find('.action-bar').children('.tool-pause'),
                        terminate : $list.find('.action-bar').children('.tool-terminate'),
                        report : $list.find('.action-bar').children('.tool-irregularity')
                    });
                    
                    loadingBar.stop();
                })
                .on('select.datatable', function(e, newDataset) {
                    //hide all controls then display each required one individually
                    actionButtons.each(function($btn){
                        $btn.hide();
                    });
                    _($list.datatable('selection')).map(function(uri){
                        var row = getExecutionData(uri);
                        return row.state.status;
                    }).uniq().each(function(statusCode){
                        var status = _status.getStatusByCode(statusCode);
                        actionButtons.forIn(function($btn, action){
                            if(status && status.can[action] === true){
                                $btn.show();
                            }
                        });
                    });
                })
                .datatable({
                    url: deliveryId ? serviceUrl : serviceAllUrl,
                    status: {
                        empty: __('No sessions'),
                        available: __('Current sessions'),
                        loading: __('Loading')
                    },
                    tools: tools,
                    actions: actions,
                    model: model,
                    selectable: true
                }, dataset);
                
        }
    };

    return proctorDeliveryIndexCtlr;
});
