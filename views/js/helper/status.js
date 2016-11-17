/*
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
 *
 */
define(['lodash', 'i18n'], function(_, __){
    'use strict';
    var _init = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusInit',
        _awaiting = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusAwaiting',
        _authorized = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusAuthorized',
        _inprogress = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusActive',
        _paused = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusPaused',
        _completed = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusFinished',
        _terminated = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusTerminated';

    var _status = {
        init : {
            code : _init,
            label : __('Init'),
            can : {
                authorize : __('not awaiting'),
                pause : __('not in progress'),
                terminate : true,
                report : true,
                print : __('not finished'),
                time : true
            }
        },
        awaiting : {
            code : _awaiting,
            label : __('Awaiting'),
            can : {
                authorize : true,
                pause : __('not in progress'),
                terminate : true,
                report : true,
                print : __('not finished'),
                time : true
            }
        },
        authorized : {
            code : _authorized,
            label : __('Authorized but not started'),
            can : {
                authorize : __('already authorized'),
                terminate : true,
                report : true,
                pause : __('not started'), //not in progress
                print : __('not finished'),
                time : true
            }
        },
        inprogress : {
            code : _inprogress,
            label : __('In Progress'),
            can : {
                authorize : __('already authorized'),
                pause : true,
                terminate : true,
                report : true,
                print : __('not finished'),
                time : true
            }
        },
        paused : {
            code : _paused,
            label : __('Paused'),
            can : {
                authorize : __('is paused'),
                pause : __('is already paused'),
                terminate : true,
                report : true,
                print : __('not finished'),
                time : true
            }
        },
        completed : {
            code : _completed,
            label : __('Completed'),
            can : {
                authorize : __('is completed'),
                pause : __('is completed'),
                terminate : __('is completed'),
                report : true,
                print: true,
                time : true
            }
        },
        terminated : {
            code : _terminated,
            label : __('Terminated'),
            can : {
                authorize : __('is terminated'),
                pause : __('is terminated'),
                terminate : __('is terminated'),
                report : true,
                print: true,
                time : true
            }
        }
    };

    /**
     * Get the status model from its name
     * @param {string} statusName
     * @returns {object}
     */
    function getStatus(statusName){
        return _status[statusName];
    }

    /**
     * Get the status model from its code
     * @param {string} statusCode
     * @returns {object}
     */
    function getStatusByCode(statusCode){
        return _.find(_status, {code : statusCode});
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
            label : testTakerData.firstname + ' ' + testTakerData.lastname
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
     * Get status model mappings formatted
     * @returns {Array}
     */
    function getStatuses() {
        return _.map(_status, function (el) {
            return {code: el.code, label: el.label};
        });
    }

    return {
        getStatuses : getStatuses,
        getStatus : getStatus,
        getStatusByCode : getStatusByCode,
        verifyTestTaker : verifyTestTaker,
        STATUS_INIT : _init,
        STATUS_AUTHORIZED : _authorized,
        STATUS_INPROGRESS : _inprogress,
        STATUS_PAUSED : _paused,
        STATUS_COMPLETED : _completed,
        STATUS_TERMINATED : _terminated
    };
});

