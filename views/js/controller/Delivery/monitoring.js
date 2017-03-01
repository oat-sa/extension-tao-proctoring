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
    'core/promise',
    'core/dataProvider/proxy',
    'controller/app',
    'util/url',
    'layout/loading-bar',
    'core/encoder/time',
    'util/encode',
    'ui/feedback',
    'ui/dialog',
    'ui/bulkActionPopup',
    'ui/cascadingComboBox',
    'ui/container',
    'taoProctoring/component/dataBroker',
    'taoProctoring/component/extraTime/extraTime',
    'taoProctoring/component/extraTime/encoder',
    'taoProctoring/helper/status',
    'tpl!taoProctoring/templates/delivery/monitoring',
    'tpl!taoProctoring/templates/delivery/deliveryLink',
    'tpl!taoProctoring/templates/delivery/statusFilter',
    'ui/datatable',
    'jqueryui',
    'select2'
], function (
    $,
    _,
    __,
    Promise,
    proxyFactory,
    appController,
    urlHelper,
    loadingBar,
    timeEncoder,
    encode,
    feedback,
    dialog,
    bulkActionPopup,
    cascadingComboBox,
    containerFactory,
    dataBrokerFactory,
    extraTimePopup,
    encodeExtraTime,
    _status,
    monitoringTpl,
    deliveryLinkTpl,
    statusFilterTpl
) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.delivery-monitoring';

    var terminateUrl = urlHelper.route('terminateExecutions', 'Monitor', 'taoProctoring');
    var pauseUrl = urlHelper.route('pauseExecutions', 'Monitor', 'taoProctoring');
    var authorizeUrl = urlHelper.route('authoriseExecutions', 'Monitor', 'taoProctoring');
    var extraTimeUrl = urlHelper.route('extraTime', 'Monitor', 'taoProctoring');
    var reportUrl = urlHelper.route('reportExecutions', 'Monitor', 'taoProctoring');
    var serviceUrl = urlHelper.route('monitor', 'Monitor', 'taoProctoring');
    var executionsUrl = urlHelper.route('deliveryExecutions', 'Monitor', 'taoProctoring');

    /**
     * The extra time unit: by default in minutes
     * @type {Number}
     */
    var extraTimeUnit = 60;

    function validateParams(params) {
        return _.isPlainObject(params) && !_.isEmpty(params.delivery) && !_.isEmpty(params.execution);
    }

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
            var currentRoute = urlHelper.parse(window.location.href);
            var deliveryId = decodeURIComponent(currentRoute.query.delivery);
            var context = decodeURIComponent(currentRoute.query.context);
            var container, $content, $list;
            var dataset;
            var extraFields;
            var categories;
            var defaultTag;
            var tagWaringBlock;
            var timeHandlingButton;
            var printReportButton;
            var tools = [];
            var model = [];
            var actionButtons;
            var highlightRows = [];
            var actionList;

            container = containerFactory().changeScope(cssScope).write(monitoringTpl());
            $content = container.find('.content');
            $list = container.find('.list');

            appController.on('change.deliveryMonitoring', function() {
                appController.off('change.deliveryMonitoring');
                container.destroy();
            });

            dataBrokerFactory().on('error', function(err) {
                if (err.code === 403) {
                    //we just leave if any 403 occurs
                    window.location.reload(true);
                }
            }).loadProviders({
                executions: proxyFactory('ajax').init({
                    actions: {
                        read: serviceUrl,
                        authorize: {
                            url: authorizeUrl,
                            validate: validateParams
                        },
                        pause: {
                            url: pauseUrl,
                            validate: validateParams
                        },
                        terminate: {
                            url: terminateUrl,
                            validate: validateParams
                        },
                        report: {
                            url: reportUrl,
                            validate: validateParams
                        },
                        extraTime: {
                            url: extraTimeUrl,
                            validate: validateParams
                        }
                    }
                })
            }).then(function(dataBroker) {
                // request the server with a selection of test takers
                function request(action, selection, data, message) {
                    if (selection && selection.length) {
                        loadingBar.start();

                        dataBroker.getProvider('executions').action(action, _.merge({
                            delivery : deliveryId,
                            execution: selection
                        }, data))
                            .then(function() {
                                if (message) {
                                    feedback().success(message);
                                }
                                $list.datatable('refresh');
                            })
                            .catch(function(response) {
                                var messageContext = '', unprocessed;
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
                            })
                            .then(function() {
                                loadingBar.stop();
                            });
                    }
                }

                // request the server to authorize the selected delivery executions
                function authorize(selection) {
                    execBulkAction('authorize', __('Authorize Session'), selection, function(sel, reason){
                        request('authorize', sel, {reason: reason}, __('Sessions authorized'));
                    });
                }

                // request the server to pause the selected delivery executions
                function pause(selection) {
                    execBulkAction('pause', __('Pause Session'), selection, function(sel, reason){
                        request('pause', sel, {reason: reason}, __('Sessions paused'));
                    });
                }

                // request the server to terminate the selected delivery executions
                function terminate(selection) {
                    execBulkAction('terminate', __('Terminate Session'), selection, function(sel, reason){
                        request('terminate', sel, {reason: reason}, __('Sessions terminated'));
                    });
                }

                // report irregularities on the selected delivery executions
                function report(selection) {
                    execBulkAction( 'report', __('Report Irregularity'), selection, function(sel, reason){
                        request('report', sel, {reason: reason}, __('Sessions reported'));
                    });
                }

                function print(selection, type) {
                    execBulkAction('print', __('Print Score'), selection, function(sel){
                        window.open(urlHelper.route(type,  'Reporting', 'taoProctoring', {'id' : sel}), 'printReport' + JSON.stringify(sel));
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
                        session: selection
                    };
                    if (deliveryId) {
                        urlParams.delivery = deliveryId;
                    }
                    window.location.href = urlHelper.route('sessionHistory', 'Reporting', 'taoProctoring', urlParams);
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
                        request('extraTime', _selection, {time: time}, __('Extra time granted'));
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
                        label: deliveryName + ' [' + testTakerData.start_time + '] ' + testTakerData.test_taker_first_name + ' ' + testTakerData.test_taker_last_name
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
                 * Prepare data to be sent on server + internal state saving
                 * @param {Boolean} applyTags
                 */
                function setTagUsage(applyTags) {
                    var $filter;
                    if (defaultTag) {

                        if (!$list.find('.tag').length) {
                            $filter = $('<span class="filter"><input type="hidden" name="tag" class="tag" value="' + applyTags + '"/></span>');
                            $filter.appendTo($list);
                        }

                        $list.find('.tag').val(applyTags);
                        $list.data('applytags', applyTags);

                        if (applyTags) {
                            $list.find('.action-bar').children('.tool-tag').hide();
                            tagWaringBlock = feedback().warning(__('Currently you are only viewing the test session in the "%s" group', defaultTag), {
                                timeout: {
                                    success: -1
                                }
                            });
                        } else {
                            $list.find('.action-bar').children('.tool-notag').hide();
                            tagWaringBlock.close();
                        }

                    }
                }

                /**
                 * Ser initial datatable filters
                 */
                function setInitialFilters()
                {
                    var now = new Date();
                    var nowStr =
                        now.getFullYear() + '/' +
                        ("0" + (now.getMonth() + 1)).slice(-2) + '/' +
                        ("0" + (now.getDate())).slice(-2);

                    $('#start_time_filter').val(nowStr + ' - ' + nowStr);

                    if (defaultTag) {
                        setTagUsage(true);
                    }

                    $list.datatable('filter');
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

                appController.on('change.deliveryMonitoring', function() {
                    dataBroker.destroy();
                });

                return dataBroker.readProvider('executions', {delivery : deliveryId, context: context}).then(function(data) {
                    dataset = data.set;
                    extraFields = data.extrafields;
                    categories = data.categories;
                    deliveryId = data.delivery;
                    context = data.context;
                    defaultTag = data.defaulttag;
                    timeHandlingButton = data.timehandling;
                    printReportButton = data.printreportbutton;

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

                    if (defaultTag) {
                        tools.push({
                            id: 'notag',
                            icon: 'filter',
                            css: 'btn-warning',
                            label: __('Remove default tag filtering'),
                            title: __('Remove default tag filtering'),
                            action: function () {
                                setTagUsage(false);
                                $list.datatable('filter');
                            }
                        });
                        tools.push({
                            id: 'tag',
                            icon: 'filter',
                            title: __('Apply default tag'),
                            label: __('Apply default tag'),
                            action: function () {
                                setTagUsage(true);
                                $list.datatable('filter');
                            }
                        });
                    }

                    // tool: authorize the executions
                    tools.push({
                        id: 'authorize',
                        icon: 'play',
                        title: __('Authorize sessions'),
                        label: __('Authorize'),
                        massAction: true,
                        action: authorize
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
                                    delivery.url = urlHelper.route('monitoring', 'Delivery', 'taoProctoring', {delivery : delivery.uri});
                                    value = deliveryLinkTpl(delivery);
                                }
                                return value;
                            }
                        });
                    }

                    // column: test taker first name
                    model.push({
                        id: 'test_taker_first_name',
                        label: __('First name'),
                        sortable : true,
                        transform: function(value, row) {
                            return row && row.testTaker && row.testTaker.test_taker_first_name || '';

                        }
                    });

                    // column: test taker last name
                    model.push({
                        id: 'test_taker_last_name',
                        label: __('Last name'),
                        sortable : true,
                        transform: function(value, row) {
                            return row && row.testTaker && row.testTaker.test_taker_last_name || '';

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
                            template : '<input type="text" id="start_time_filter" name="filter[start_time]"/>' +
                            '<button class="icon-find js-start_time_filter_button" type="button"></button>',
                            callback : function ($el) {
                                $el.datepicker({
                                    dateFormat: "yy/mm/dd",
                                    onSelect: function( selectedDate ) {
                                        if(!$(this).data().datepicker.first){
                                            $(this).data().datepicker.inline = true;
                                            $(this).data().datepicker.first = selectedDate;
                                        } else {
                                            if(selectedDate > $(this).data().datepicker.first){
                                                $(this).val($(this).data().datepicker.first+" - "+selectedDate);
                                            } else {
                                                $(this).val(selectedDate+" - "+$(this).data().datepicker.first);
                                            }
                                            $(this).data().datepicker.inline = false;
                                            $('.js-start_time_filter_button').trigger('click');
                                        }
                                    },
                                    onClose:function(){
                                        delete $(this).data().datepicker.first;
                                        $(this).data().datepicker.inline = false;
                                    }
                                });
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

                    // action: authorize the execution
                    model.push({
                        id: 'authorizeCl',
                        label: __('Authorize'),
                        type: 'actions',
                        actions: [{
                            id: 'authorize',
                            icon: 'play',
                            title: __('Authorize session'),
                            disabled: function() {
                                return !canDo('authorize', this.state);
                            },
                            action: authorize
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
                            var applyTags;

                            //update dateset in memory
                            dataset = newDataset;

                            //update the buttons, which have been reconstructed
                            actionButtons = _({
                                authorize : $list.find('.action-bar').children('.tool-authorise'),
                                pause : $list.find('.action-bar').children('.tool-pause'),
                                terminate : $list.find('.action-bar').children('.tool-terminate'),
                                report : $list.find('.action-bar').children('.tool-irregularity')
                            });

                            if (defaultTag) {
                                applyTags = $list.data('applytags');
                                applyTags = !_.isUndefined(applyTags) ? applyTags : true;
                                setTagUsage(applyTags);
                            }

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
                            url: urlHelper.build(executionsUrl, {delivery : deliveryId, context: context}),
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

                    setInitialFilters();
                });
            }).catch(function(err) {
                appController.onError(err);
                loadingBar.stop();
            });
        }
    };
});
