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
    'module',
    'controller/app',
    'core/polling',
    'core/timer',
    'util/url',
    'layout/loading-bar',
    'core/encoder/time',
    'util/encode',
    'ui/feedback',
    'ui/dialog',
    'ui/bulkActionPopup',
    'ui/cascadingComboBox',
    'ui/container',
    'taoProctoring/component/proxy',
    'taoProctoring/component/extraTime/extraTime',
    'taoProctoring/component/extraTime/encoder',
    'taoProctoring/helper/status',
    'tpl!taoProctoring/templates/delivery/monitoring',
    'tpl!taoProctoring/templates/delivery/deliveryLink',
    'tpl!taoProctoring/templates/delivery/statusFilter',
    'moment',
    'util/locale',
    'tpl!taoProctoring/templates/delivery/approximatedTimer',
    'ui/datatable',
    'jqueryui',
    'select2'
], function (
    $,
    _,
    __,
    module,
    appController,
    pollingFactory,
    timerFactory,
    urlHelper,
    loadingBar,
    timeEncoder,
    encode,
    feedback,
    dialog,
    bulkActionPopup,
    cascadingComboBox,
    containerFactory,
    proxyFactory,
    extraTimePopup,
    encodeExtraTime,
    _status,
    monitoringTpl,
    deliveryLinkTpl,
    statusFilterTpl,
    moment,
    locale,
    approximatedTimerTpl
) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.delivery-monitoring';

    var terminateUrl = urlHelper.route('terminateExecutions', 'Monitor', 'taoProctoring');
    var reactivateUrl = urlHelper.route('reactivateExecutions', 'MonitorProctorAdministrator', 'taoProctoring');
    var pauseUrl = urlHelper.route('pauseExecutions', 'Monitor', 'taoProctoring');
    var authorizeUrl = urlHelper.route('authoriseExecutions', 'Monitor', 'taoProctoring');
    var extraTimeUrl = urlHelper.route('extraTime', 'Monitor', 'taoProctoring');
    var reportUrl = urlHelper.route('reportExecutions', 'Monitor', 'taoProctoring');
    var serviceUrl = urlHelper.route('monitor', 'Monitor', 'taoProctoring');
    var executionsUrl = urlHelper.route('deliveryExecutions', 'Monitor', 'taoProctoring');
    var historyUrl = urlHelper.route('index', 'Reporting', 'taoProctoring');


    /**
     * The extra time unit: by default in minutes
     * @type {Number}
     */
    var extraTimeUnit = 60;

    /**
     * Validates the params to be sent along the provider's requests
     * @param params
     * @returns {boolean}
     */
    function validateParams(params) {
        return _.isPlainObject(params) &&
            (_.isUndefined(params.delivery) || !_.isEmpty(params.delivery)) &&
            (_.isUndefined(params.testCenter) || !_.isEmpty(params.testCenter)) &&
            !_.isEmpty(params.execution);
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
            var container = containerFactory().changeScope(cssScope).write(monitoringTpl());
            var $content = container.find('.content');
            var $list = container.find('.list');
            var pageParams = module.config();
            var deliveryId = pageParams.delivery;
            var context = pageParams.context;
            var defaultTag = pageParams.defaultTag;
            var defaultAvailableLabel = __('Current sessions');
            var dataset;
            var extraFields;
            var categories;
            var timeHandlingButton;
            var allowedConnectivity;
            var printReportButton;
            var printReportUrl;
            var hasAccessToReactivate;
            var tools = [];
            var model = [];
            var actionButtons;
            var highlightRows = [];
            var actionList;
            var serviceParams = {};
            var sessionsHistoryUrl = historyUrl;
            var timer = timerFactory({
                autoStart: false
            });
            var label;

            var polling = pollingFactory({
                action: function() {
                    var elapsed = timer.tick() / 1000;
                    var timers = $('.procotor-timer_time.countDown');
                    _.forEach(timers, function (timerItem) {
                        var remaining = $(timerItem).data('remaining');
                        if (remaining > 0) {
                            remaining -= elapsed;
                            $(timerItem).html(timeEncoder.encode(Math.round(remaining)));
                            $(timerItem).data('remaining', remaining);
                        }
                    });
                },
                interval: 1000,
                autoStart: false
            });
            appController.on('change.deliveryMonitoring', function() {
                appController.off('.deliveryMonitoring');
                container.destroy();
            });

            proxyFactory('ajax').init({
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
                    reactivate: {
                        url: reactivateUrl,
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
            }).then(function(proxyExecutions) {
                // request the server with a selection of test takers
                function request(action, selection, data, message) {
                    var params;
                    if (selection && selection.length) {
                        loadingBar.start();

                        params = _.merge({
                            execution: selection
                        }, data);

                        if (deliveryId) {
                            params.delivery = deliveryId;
                        }

                        if (context) {
                            params.testCenter = context;
                        }

                        proxyExecutions.action(action, params)
                            .then(function() {
                                if (message) {
                                    feedback().success(message);
                                }
                                $list.datatable('refresh');
                            })
                            .catch(function(err) {
                                var messageContext = '',
                                    responseData,
                                    unprocessed;

                                if (err.response) {
                                    responseData = err.response.data;
                                    unprocessed = _.map(responseData.unprocessed, function (msg, id) {
                                        var execution;

                                        if (!id) {
                                            id = msg;
                                            msg = null;
                                        }

                                        if (msg) {
                                            return msg;
                                        } else {
                                            execution = getExecutionData(id);

                                            if (execution) {
                                                return __('Session %s - %s has not been processed', execution.delivery.label, execution.start_time);
                                            }
                                        }
                                    });

                                    if (unprocessed.length) {
                                        messageContext += '<br>' + unprocessed.join('<br>');
                                    }
                                    if (responseData.error) {
                                        messageContext += '<br>' + encode.html(responseData.error);
                                    }
                                }
                                appController.onError(err);
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

                // request the reactivate to reactivate the selected delivery executions
                function reactivate(selection) {
                    execBulkAction('reactivate', __('Reactivate Session'), selection, function(sel, reason){
                        request('reactivate', sel, {reason: reason}, __('Sessions reactivated'));
                    });
                }

                // report irregularities on the selected delivery executions
                function report(selection) {
                    execBulkAction( 'report', __('Report Irregularity'), selection, function(sel, reason){
                        request('report', sel, {reason: reason}, __('Sessions reported'));
                    });
                }

                function terminateOrReactivateAndIrregularity(selection) {
                    var delivery = getExecutionData(selection);
                    var buttons = [];
                    if (hasAccessToReactivate && canDo('reactivate', delivery.state)) {
                        buttons.push({
                            id: 'reactivate',
                            type: 'error',
                            label: __('Reactivate session'),
                            icon: 'play',
                            close: true,
                            action: function() {reactivate(selection);}
                        });
                    }else if (canDo('terminate', delivery.state)) {
                        buttons.push({
                            id: 'terminate',
                            type: 'error',
                            label: __('Terminate session'),
                            icon: 'stop',
                            close: true,
                            action: function() {terminate(selection);}
                        });
                    }

                    buttons.push({
                        id: 'irregularity',
                        type: 'info',
                        label: __('Report irregularity'),
                        icon: 'delivery-small',
                        close: true,
                        action: function(){report(selection);}
                    });

                    dialog({
                        message: __('Please, make your selection'),
                        autoRender: true,
                        autoDestroy: true,
                        buttons: buttons
                    });
                }

                // display the session history
                function showHistory(selection) {
                    var monitoringRoute = window.location + '';
                    var urlParams = {
                        session: selection
                    };
                    if (context) {
                        urlParams.context = context;
                    }
                    if (deliveryId) {
                        urlParams.delivery = deliveryId;
                    }
                    appController.getRouter().redirect(urlHelper.build(sessionsHistoryUrl, urlParams)).then(function() {
                        appController.trigger('set-referrer', monitoringRoute);
                    }).catch(function(err){
                        appController.onError(err);
                    });
                }

                // print the score reports
                function printReport(selection) {
                    execBulkAction('print', __('Print Score'), selection, function(sel) {
                        var params = { id: sel };
                        if (context){
                            params.context = context;
                        }
                        var url = urlHelper.route(
                            printReportUrl.action,
                            printReportUrl.controller,
                            printReportUrl.extension,
                            params
                        );
                        window.open(url, 'printReport' + JSON.stringify(sel));
                    });
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
                        deliveryName = testTakerData.delivery;
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
                            formatted.warning = status.warning[actionName] ?
                                status.warning[actionName](null, testTakerData.id) :
                                __('Unable to perform action on test %s.', testTakerData.id);
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
                    var config = _.merge(listSessions(actionName, _selection), {
                        renderTo : $content,
                        actionName : actionTitle,
                        reason : askForReason,
                        reasonRequired: true,
                        categoriesSelector: cascadingComboBox(categories[actionName] || {})
                    });

                    if (!config.allowedResources.length) {
                        feedback().warning(_status.buildWarningMessage(actionName, _selection, config.deniedResources));
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
                        } else {
                            $list.find('.action-bar').children('.tool-notag').hide();
                        }
                    }
                }

                function getTagsUsage() {
                    return $list.data('applytags');
                }

                /**
                 * Set initial datatable filters
                 */
                function setInitialFilters()
                {
                    var now = new Date();
                    var dateFormat = locale.getDateTimeFormat().split(" ");
                    var nowStr = dateFormat[0];

                    nowStr = nowStr.replace("YYYY", now.getFullYear());
                    nowStr = nowStr.replace("MM", ("0" + (now.getMonth() + 1)).slice(-2));
                    nowStr = nowStr.replace("DD", ("0" + (now.getDate())).slice(-2));
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

                if (deliveryId) {
                    serviceParams.delivery = deliveryId;
                }
                if (context) {
                    serviceParams.context = context;
                }
                return proxyExecutions.read(serviceParams).then(function(data) {
                    dataset = data.set;
                    extraFields = data.extrafields;
                    categories = data.categories;
                    deliveryId = data.delivery || deliveryId;
                    context = data.context || context;
                    timeHandlingButton = data.timeHandling;
                    allowedConnectivity = data.onlineStatus || false;
                    printReportButton = data.printReportButton;
                    printReportUrl = data.printReportUrl;
                    hasAccessToReactivate = data.hasAccessToReactivate;
                    sessionsHistoryUrl = data.historyUrl || historyUrl;

                    if (deliveryId) {
                        serviceParams.delivery = deliveryId;
                    }
                    if (context) {
                        serviceParams.context = context;
                    }

                    /**
                     * configurable parameter to show button
                     */
                    if (data.refreshBtn) {
                        // tool: page refresh
                        tools.push({
                            id: 'refresh',
                            icon: 'reset',
                            title: __('Refresh the page'),
                            label: __('Refresh'),
                            action: function () {
                                $list.datatable('refresh');
                            }
                        });
                    }

                    /**
                     * Configurable parameter to auto renew datatable
                     */
                    if (data.autoRefresh) {
                        setInterval(function () {
                            $list.datatable('refresh');
                        }, data.autoRefresh);
                    }

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

                    if(data.canPause === null || data.canPause){
                        // tool: pause the executions
                        tools.push({
                            id: 'pause',
                            icon: 'pause',
                            title: __('Pause sessions'),
                            label: __('Pause'),
                            massAction: true,
                            action: pause
                        });
                    }

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
                    model.push({
                        id: 'deliveryLabel',
                        label: __('Session'),
                        sortable : true,
                        transform: function(value, row) {
                            var delivery = row && row.delivery;
                            if (delivery) {
                                value = deliveryLinkTpl(delivery);
                            }
                            return value;
                        }
                    });

                    // column: test taker first name
                    model.push({
                        id: 'test_taker_first_name',
                        label: __('First name'),
                        filterable: true,
                        sortable : true,
                        transform: function(value, row) {
                            return row && row.testTaker && row.testTaker.test_taker_first_name || '';

                        }
                    });

                    // column: test taker last name
                    model.push({
                        id: 'test_taker_last_name',
                        label: __('Last name'),
                        filterable: true,
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
                            filterable: extraField.filterable,
                            sortable : true,
                            order: extraField.columnPosition,
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
                        transform: function(value) {
                            return locale.formatDateTime(value);
                        },
                        filterTransform: function (value) {
                            var values = value.split(" - ");
                            var result = '';
                            var dateFormat = locale.getDateTimeFormat();
                            if (values[0]) {
                                result = moment(values[0], dateFormat).format('X');
                            }
                            if (values[1]) {
                                values[1] = moment(values[1], dateFormat).add(1, 'd').format('X');
                                if (result !== '') {
                                    result += ' - ';
                                }
                                result += values[1];
                            }
                            return result;
                        },
                        customFilter : {
                            template : '<input type="text" id="start_time_filter" name="filter[start_time]"/>' +
                            '<button class="icon-find js-start_time_filter_button" type="button"></button>',
                            callback : function ($el) {
                                var dateFormat = locale.getDateTimeFormat().split(" ");
                                var dateFormatStr = dateFormat[0];
                                var comparisonDateFormat;
                                dateFormatStr = dateFormatStr.replace('YYYY', 'yy');
                                dateFormatStr = dateFormatStr.replace('MM', 'mm');
                                dateFormatStr = dateFormatStr.replace('DD', 'dd');
                                comparisonDateFormat = dateFormat[0];
                                $el.datepicker({
                                    dateFormat: dateFormatStr,
                                    onSelect: function( selectedDate ) {
                                        var datePickerData = $(this).data().datepicker;
                                        var firstDate, selDate;
                                        if (!datePickerData.first) {
                                            datePickerData.inline = true;
                                            datePickerData.first = selectedDate;
                                        } else {
                                            firstDate = moment(datePickerData.first, comparisonDateFormat).format('X');
                                            selDate = moment(selectedDate, comparisonDateFormat).format('X');
                                            if (selDate > firstDate) {
                                                $(this).val(datePickerData.first+" - "+selectedDate);
                                            } else {
                                                $(this).val(selectedDate+" - "+datePickerData.first);
                                            }
                                            datePickerData.inline = false;
                                            $('.js-start_time_filter_button').trigger('click');
                                        }
                                    },
                                    onClose:function(event){
                                        if (event.which === $.ui.keyCode.ENTER) {
                                            event.preventDefault();
                                            $el.datepicker("hide");
                                        }
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

                    if(data.canPause === null || data.canPause){
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
                    }

                    // column: remaining time
                    model.push({
                        id: 'remaining_time',
                        sortable : true,
                        sorttype: 'numeric',
                        label: __('Remaining'),
                        transform: function(value, row) {
                            var rowTimer = _.isObject(row.timer) ? row.timer : {};
                            var refinedValue = rowTimer.approximatedRemaining ? rowTimer.approximatedRemaining : rowTimer.remaining_time;
                            var remaining = parseInt(refinedValue, 10);
                            if (remaining || _.isFinite(remaining) ) {
                                if (remaining < 0) {
                                    if (rowTimer.extraTime && rowTimer.consumedExtraTime) {
                                        rowTimer.consumedExtraTime += -remaining;
                                    }
                                    remaining = 0;
                                }
                                if (remaining) {
                                    if (rowTimer.extraTime && rowTimer.consumedExtraTime) {
                                        remaining -= rowTimer.consumedExtraTime;
                                    }
                                    refinedValue = timeEncoder.encode(remaining);
                                } else {
                                    refinedValue = '';
                                }

                                refinedValue = approximatedTimerTpl({
                                    timer: refinedValue,
                                    remaining: remaining,
                                    countDown: rowTimer.countDown
                                });
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
                                    var allowExtraTime = _.isNull(this.allowExtraTime) || this.allowExtraTime;
                                    return !canDo('time', this.state) || !allowExtraTime;
                                }
                            }]
                        });
                    }

                    model.push({
                        id: 'extendedTime',
                        label: __('Extended Time'),
                        transform: function(value, row) {
                            var extendedTimer = _.isObject(row.timer) ? row.timer : {};
                            return (extendedTimer.extendedTime ? 'x' : '') + extendedTimer.extendedTime;
                        }
                    });

                    if (allowedConnectivity) {
                        // column: connectivity status of execution progress
                        model.push({
                            id: 'last_connect',
                            sortable: true,
                            label: __('Connectivity'),
                            transform: function(value, row) {
                                if (row.state.status === _status.STATUS_INPROGRESS) {
                                    return row.online ? __('online') : __('offline');
                                }
                                return '';
                            }
                        });
                    }

                    // column: delivery execution progress
                    model.push({
                        id: 'progress',
                        label: __('Progress'),
                        transform: function(value, row) {
                            return row && row.state && row.state.progress || '' ;
                        }
                    });

                    label = 'Terminate and irregularity';
                    if (hasAccessToReactivate) {
                        label = 'Terminate/Reactivate and irregularity';
                    }

                    // column: proctoring actions
                    actionList = [{
                        id: 'terminateOrReactivateAndIrregularity',
                        icon: 'delivery-small',
                        title: __(label),
                        action: terminateOrReactivateAndIrregularity
                    }, {
                        id: 'history',
                        icon: 'history',
                        title: __('Show the detailed session history'),
                        action: showHistory
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

                            appController
                                .off('change.polling')
                                .on('change.polling', function () {
                                polling.stop();
                            });

                            polling.start();
                            timer.resume();
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
                        }).on('error.datatable', function(e, err){
                            appController.onError(err);
                        }).datatable({
                            url: urlHelper.build(executionsUrl, serviceParams),
                            status: {
                                empty: __('No sessions'),
                                available: function () {
                                    return getTagsUsage() ? __("Groups: %s. %s", defaultTag.split(',').join(', '), defaultAvailableLabel) : defaultAvailableLabel;
                                },
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
