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
    'core/encoder/time',
    'util/encode',
    'ui/feedback',
    'ui/dialog',
    'ui/bulkActionPopup',
    'ui/cascadingComboBox',
    'taoProctoring/component/extraTime/extraTime',
    'taoProctoring/component/extraTime/encoder',
    'taoProctoring/component/breadcrumbs',
    'taoProctoring/helper/status',
    'tpl!taoProctoring/templates/delivery/deliveryLink',
    'tpl!taoProctoring/templates/delivery/statusFilter',
    'ui/datatable',
    'select2'
], function (
    $,
    _,
    __,
    helpers,
    loadingBar,
    timeEncoder,
    encode,
    feedback,
    dialog,
    bulkActionPopup,
    cascadingComboBox,
    extraTimePopup,
    encodeExtraTime,
    breadcrumbsFactory,
    _status,
    deliveryLinkTpl,
    statusFilterTpl
) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.delivery-monitoring';

    /**
     * The extra time unit: by default in minutes
     * @type {Number}
     */
    var extraTimeUnit = 60;

    // the page is always loading data when starting
    loadingBar.start();

    /**
     * Controls the taoProctoring delivery page
     *
     * @type {Object}
     */
    return {
        /**
         * Entry point of the page
         */
        start : function start() {
            var $container = $(cssScope);
            var $content = $container.find('.content');
            var $list = $container.find('.list');
            var crumbs = $container.data('breadcrumbs');
            var dataset = $container.data('set');
            var extraFields = $container.data('extrafields');
            var categories = $container.data('categories');
            var deliveryId = $container.data('delivery');
            var isManageable = $container.data('ismanageable');
            var testCenterId = $container.data('testcenter');
            var timeHandlingButton = $container.data('timehandling');
            var printReportButton = $container.data('printreportbutton');
            var manageUrl = helpers._url('manage', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var terminateUrl = helpers._url('terminateExecutions', 'Monitor', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var pauseUrl = helpers._url('pauseExecutions', 'Monitor', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var authoriseUrl = helpers._url('authoriseExecutions', 'Monitor', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var extraTimeUrl = helpers._url('extraTime', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var reportUrl = helpers._url('reportExecutions', 'Monitor', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
            var serviceUrl = helpers._url('deliveryExecutions', 'Monitor', 'taoProctoring', {delivery : deliveryId});
            var serviceAllUrl = helpers._url('allDeliveriesExecutions', 'Delivery', 'taoProctoring', {testCenter : testCenterId});
            var tools = [];
            var model = [];
            var actionButtons;
            var highlightRows = [];
            var actionList;

            // request the server with a selection of test takers
            function request(url, selection, data, message) {
                if (selection && selection.length) {
                    loadingBar.start();

                    $.ajax({
                        url: url,
                        data: _.merge({
                            execution: selection
                        }, data),
                        dataType : 'json',
                        type: 'POST',
                        error: function() {
                            loadingBar.stop();
                        }
                    }).done(function(response) {
                        var messageContext, unprocessed;

                        loadingBar.stop();

                        if (response && response.success) {
                            if (message) {
                                feedback().success(message);
                            }
                            $list.datatable('refresh');
                        } else {
                            messageContext = '';
                            if (response) {
                                unprocessed = _.map(response.unprocessed, function (id) {
                                    var execution = getExecutionData(id);
                                    if (execution) {
                                        return __('Session %s - %s has not been processed', execution.delivery, execution.start_time);
                                    }
                                });

                                if (unprocessed.length) {
                                    messageContext += '<br>' + unprocessed.join('<br>');
                                }
                                if (response.error) {
                                    messageContext += '<br>' + encode.html(response.error);
                                }
                            }

                            feedback().error(__('Something went wrong ...') + '<br>' + messageContext, {encodeHtml: false});
                        }
                    });
                }
            }

            // request the server to authorise the selected delivery executions
            function authorise(selection) {
                execBulkAction('authorize', __('Authorize Session'), selection, function(sel, reason){
                    request(authoriseUrl, sel, {reason: reason}, __('Sessions authorized'));
                });
            }

            // request the server to pause the selected delivery executions
            function pause(selection) {
                execBulkAction('pause', __('Pause Session'), selection, function(sel, reason){
                    request(pauseUrl, sel, {reason: reason}, __('Sessions paused'));
                });
            }

            // request the server to terminate the selected delivery executions
            function terminate(selection) {
                execBulkAction('terminate', __('Terminate Session'), selection, function(sel, reason){
                    request(terminateUrl, sel, {reason: reason}, __('Sessions terminated'));
                });
            }

            // report irregularities on the selected delivery executions
            function report(selection) {
                execBulkAction( 'report', __('Report Irregularity'), selection, function(sel, reason){
                    request(reportUrl, sel, {reason: reason}, __('Sessions reported'));
                });
            }

            function print(selection, type) {
                execBulkAction('print', __('Print Score'), selection, function(sel){
                    window.open(helpers._url(type,  'Reporting', 'taoProctoring', {'id' : sel}), 'printReport' + JSON.stringify(sel));
                });
            }

            function terminateAndIrregularity(selection) {
                dialog({
                    message: __('Please, make your selection'),
                    autoRender: true,
                    autoDestroy: true,
                    buttons: [{
                        id: 'terminate',
                        type: 'error',
                        label: __('Terminate session'),
                        icon: 'stop',
                        close: true,
                        action: function() {terminate(selection);}
                    },{
                        id: 'irregularity',
                        type: 'info',
                        label: __('Report irregularity'),
                        icon: 'delivery-small',
                        close: true,
                        action: function(){report(selection);}
                    }]
                });
            }

            // display the session history
            function showHistory(selection) {
                var urlParams = {
                    testCenter : testCenterId,
                    session: selection
                };
                if (deliveryId) {
                    urlParams.delivery = deliveryId;
                }
                window.location.href = helpers._url('sessionHistory', 'Reporting', 'taoProctoring', urlParams);
            }

            // print the score reports
            function printReport(selection) {
                print(selection, 'printReport');
            }

            // print the results of the session
            function printResults(selection) {
                print(selection, 'printRubric');
            }

            // display the time handling popup
            function timeHandling(selection) {
                var _selection = _.isArray(selection) ? selection : [selection];
                var config = _.merge(listSessions('time', _selection), {
                    renderTo : $content,
                    actionName : __('Grant Extra Time'),
                    unit: extraTimeUnit // input extra time in minutes
                });

                extraTimePopup(config).on('ok', function(time){
                    request(extraTimeUrl, _selection, {time: time}, __('Extra time granted'));
                });
            }

            /**
             * Check if an action is available with respect to the provided state
             * @param {String} what
             * @param {Object} state
             * @returns {Boolean}
             */
            function canDo(what, state) {
                var status;
                if (state && state.status) {
                    status = _status.getStatusByCode(state.status);
                    return status && status.can[what] === true;
                }
                return false;
            }

            /**
             * Verify and reformat test taker data for the execBulkAction's need
             * @param {Object} testTakerData
             * @param {String} actionName
             * @returns {Object}
             */
            function verifyDelivery(testTakerData, actionName){
                var deliveryName, formatted, status;

                if (_.isObject(testTakerData.delivery)) {
                    deliveryName = testTakerData.delivery.label;
                } else {
                    deliveryName = $(testTakerData.delivery).text();
                }
                formatted = {
                    id : testTakerData.id,
                    label: deliveryName + ' [' + testTakerData.start_time + '] ' + testTakerData.firstname + ' ' + testTakerData.lastname
                };
                status = _status.getStatusByCode(testTakerData.state.status);

                if(status){
                    formatted.allowed = (status.can[actionName] === true);
                    if(!formatted.allowed){
                        formatted.reason = status.can[actionName];
                    }
                }
                if (testTakerData.timer) {
                    formatted.extraTime = testTakerData.timer.extraTime;
                    formatted.consumedTime = testTakerData.timer.consumedExtraTime;
                    formatted.remaining_time = testTakerData.timer.remaining_time;
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
             * Gets the list of allowed and forbidden test sessions from the provided selection
             * @param {String} actionName
             * @param {Array} selection
             * @returns {Object} Returns the config object that contains the lists of allowed and forbidden test sessions
             */
            function listSessions(actionName, selection) {
                var allowedDeliveries = [];
                var forbiddenDeliveries = [];

                _.each(selection, function (uri) {
                    var testTakerData = getExecutionData(uri);
                    var checkedDelivery;
                    if(testTakerData){
                        checkedDelivery = verifyDelivery(testTakerData, actionName);
                        if(checkedDelivery.allowed){
                            allowedDeliveries.push(checkedDelivery);
                        }else{
                            forbiddenDeliveries.push(checkedDelivery);
                        }
                    }
                });

                return {
                    resourceType : 'session',
                    allowedResources: allowedDeliveries,
                    deniedResources: forbiddenDeliveries
                };
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
                var _selection = _.isArray(selection) ? selection : [selection];
                var askForReason = (categories[actionName] && categories[actionName].categoriesDefinitions && categories[actionName].categoriesDefinitions.length);
                var config;


                config = _.merge(listSessions(actionName, _selection), {
                    renderTo : $content,
                    actionName : actionTitle,
                    reason : askForReason,
                    reasonRequired: true,
                    categoriesSelector: cascadingComboBox(categories[actionName] || {})
                });

                if (!config.allowedResources.length) {
                    if (_selection.length > 1) {
                        feedback().warning(__('No report available for these test sessions'));
                    } else {
                        feedback().warning(__('No report available for this test session'));
                    }
                } else {
                    bulkActionPopup(config).on('ok', function(reason){
                        //execute callback
                        if(_.isFunction(cb)){
                            cb(_selection, reason);
                        }
                    });
                }
            }

            /**
             * Return html for filter
             * @returns {String}
             */
            function buildStatusFilter(){
                return statusFilterTpl({statuses: _status.getStatuses()});
            }

            /**
             * Additional action perfomed with filter element
             * @param {jQueryElement} $el
             */
            function statusFilterHandler($el) {
                $el.select2({
                    dropdownAutoWidth: true,
                    placeholder: __('Filter'),
                    minimumResultsForSearch: Infinity,
                    allowClear: true
                });
            }

            breadcrumbsFactory($container, crumbs);

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
            if (deliveryId && isManageable) {
                tools.push({
                    id: 'manage',
                    icon: 'property-advanced',
                    title: __('Manage sessions'),
                    label: __('Manage'),
                    action: function() {
                        window.location.href = manageUrl;
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

            // tool: display sessions history
            tools.push({
                id: 'history',
                icon: 'history',
                title: __('Show the detailed session history'),
                label: __('History'),
                massAction: true,
                action: showHistory
            });

            // tools: print score report
            tools.push({
                id : 'printRubric',
                title : __('Print the score report'),
                icon : 'print',
                label : __('Print Score'),
                massAction: true,
                action : printResults
            });

            // tools: print results
            if (printReportButton) {
                tools.push({
                    id : 'printReport',
                    title : __('Print the assessment results'),
                    icon : 'result',
                    label : __('Print Results'),
                    massAction: true,
                    action : printReport
                });
            }

            // tools: handles the session time
            if (timeHandlingButton) {
                tools.push({
                    id : 'timeHandling',
                    title : __('Session time handling'),
                    icon : 'time',
                    label : __('Time'),
                    massAction: true,
                    action : timeHandling
                });
            }

            // column: delivery (only for all deliveries view)
            if (!deliveryId) {
                model.push({
                    id: 'delivery',
                    label: __('Session'),
                    sortable : true,
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
                sortable : true,
                transform: function(value, row) {
                    return row && row.testTaker && row.testTaker.firstName || '';

                }
            });

            // column: test taker last name
            model.push({
                id: 'lastname',
                label: __('Last name'),
                sortable : true,
                transform: function(value, row) {
                    return row && row.testTaker && row.testTaker.lastName || '';

                }
            });

            //extra fields
            _.each(extraFields, function(extraField){
                model.push({
                    id : extraField.id,
                    label: extraField.label,
                    sortable : true,
                    transform: function(value, row) {
                        return row && row.extraFields && row.extraFields[extraField.id] || '';
                    }
                });
            });

            // column: start time
            model.push({
                id: 'start_time',
                sortable : true,
                label: __('Started at'),
                filterable : true,
                customFilter : {
                    template : '<input type="text" id="start_time_filter" name="filter[start_time]"/><button class="icon-find" type="button"></button>',
                    callback : function ($el) {
                        //given from here:
                        $el.datepicker({
                            onSelect: function( selectedDate ) {
                                if(!$(this).data().datepicker.first){
                                    $(this).data().datepicker.inline = true
                                    $(this).data().datepicker.first = selectedDate;
                                }else{
                                    console.log(selectedDate);
                                    if(selectedDate > $(this).data().datepicker.first){
                                        $(this).val($(this).data().datepicker.first+" - "+selectedDate);
                                    }else{
                                        $(this).val(selectedDate+" - "+$(this).data().datepicker.first);
                                    }
                                    $(this).data().datepicker.inline = false;
                                }
                            },
                            onClose:function(){
                                delete $(this).data().datepicker.first;
                                $(this).data().datepicker.inline = false;
                            }
                        })
                    }
                },
            });

            // column: delivery execution status
            model.push({
                id: 'status',
                label: __('Status'),
                sortable : true,
                filterable : true,
                customFilter : {
                    template : buildStatusFilter(),
                    callback : statusFilterHandler
                },

                transform: function(value, row) {
                    var result = '',
                        status;

                    if (row && row.state && row.state.status) {
                        status = _status.getStatusByCode(row.state.status);
                        if (status) {
                            result = status.label;
                            if (row.state.status === 'INPROGRESS') {
                                result = status.label;
                            }
                            if (result === 'Awaiting') {
                                highlightRows.push(row.id);
                            }
                        }
                    }
                    return result;
                }
            });

            // action: authorise the execution
            model.push({
                id: 'authorizeCl',
                label: __('Authorize'),
                type: 'actions',
                actions: [{
                    id: 'authorise',
                    icon: 'play',
                    title: __('Authorize session'),
                    disabled: function() {
                        return !canDo('authorize', this.state);
                    },
                    action: authorise
                }]
            });

            // action: pause the execution
            model.push({
                id: 'pauseCl',
                label: __('Pause'),
                type: 'actions',
                actions: [{
                    id: 'pause',
                    icon: 'pause',
                    title: __('Pause session'),
                    disabled: function() {
                        return !canDo('pause', this.state);
                    },
                    action: pause
                }]
            });

            // column: remaining time
            model.push({
                id: 'remaining_time',
                sortable : true,
                label: __('Remaining'),
                transform: function(value, row) {
                    var timer = _.isObject(row.timer) ? row.timer : {};
                    var refinedValue = timer.remaining_time;
                    var remaining = parseInt(refinedValue, 10);

                    if (remaining || _.isFinite(remaining) ) {
                        if (remaining) {
                            refinedValue = timeEncoder.encode(remaining);
                        } else {
                            refinedValue = '';
                        }
                        refinedValue += encodeExtraTime(timer.extraTime, timer.consumedExtraTime, __('%s min'), extraTimeUnit);
                    }

                    return refinedValue;
                }
            });
            if (timeHandlingButton) {
                model.push({
                    id: 'extraTime',
                    label: __('Extra Time'),
                    type: 'actions',
                    actions: [{
                        id : 'timeHandling',
                        title : __('Session time handling'),
                        icon : 'time',
                        action : timeHandling,
                        hidden: function() {
                            return !canDo('time', this.state);
                        }
                    }]
                });
            }

            // column: connectivity status of execution progress
            model.push({
                id: 'connectivity',
                sortable: true,
                label: __('Connectivity'),
                transform: function(value, row) {
                    if (row.state.status === _status.STATUS_INPROGRESS) {
                        return row.online ? __('online') : __('offline');
                    }
                    return '';
                }
            });

            // column: delivery execution progress
            model.push({
                id: 'progress',
                label: __('Progress'),
                transform: function(value, row) {
                    return row && row.state && row.state.progress || '' ;
                }
            });

            // column: proctoring actions
            actionList = [{
                id: 'terminateAndIrregularity',
                icon: 'delivery-small',
                title: __('Terminate and irregularity'),
                action: terminateAndIrregularity
            }, {
                id: 'history',
                icon: 'history',
                title: __('Show the detailed session history'),
                action: showHistory
            }, {
                id : 'printRubric',
                title : __('Print the Score Report'),
                icon : 'print',
                action : printResults
            }];
            if (printReportButton) {
                actionList.push({
                    id : 'printReport',
                    title : __('Print the assessment results'),
                    icon : 'result',
                    action : printReport
                });
            }

            model.push({
                id: 'administrationCl',
                label: __('Administration'),
                type: 'actions',
                actions: actionList
            });

            // renders the datatable
            $list
                .on('query.datatable', function() {
                    loadingBar.start();
                    highlightRows = [];
                })
                .on('load.datatable', function(e, newDataset) {
                    //update dateset in memory
                    dataset = newDataset;

                    //update the buttons, which have been reconstructed
                    actionButtons = _({
                        authorize : $list.find('.action-bar').children('.tool-authorise'),
                        pause : $list.find('.action-bar').children('.tool-pause'),
                        terminate : $list.find('.action-bar').children('.tool-terminate'),
                        report : $list.find('.action-bar').children('.tool-irregularity')
                    });

                    // highlight rows
                    if (highlightRows.length) {
                        _.forEach(highlightRows, function (v) {
                            $list.datatable('highlightRow', v);
                        });
                    }

                    loadingBar.stop();
                })
                .on('select.datatable', function() {
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
                    filterStrategy: 'multiple',
                    filterSelector: 'select, input:not(.select2-input, .select2-focusser)',
                    filter: true,
                    tools: tools,
                    model: model,
                    selectable: true,
                    sortorder: 'desc',
                    sortby : 'start_time'
                }, dataset);
        }
    };
});
