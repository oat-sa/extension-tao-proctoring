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
    var _status = {
        init : {
            code : 'INIT',
            label : __('Init'),
            can : {
                authorize : __('not awaiting'),
                pause : __('not in progress'),
                terminate : true,
                report : true
            }
        },
        awaiting : {
            code : 'AWAITING',
            label : __('Awaiting'),
            can : {
                authorize : true,
                pause : __('not in progress'),
                terminate : true,
                report : true
            }
        },
        authorized : {
            code : 'AUTHORIZED',
            label : __('Authorized but not started'),
            can : {
                authorize : __('already authorized'),
                pause : __('not started'), //not in progress
                terminate : true,
                report : true
            }
        },
        inprogress : {
            code : 'INPROGRESS',
            label : __('In Progress'),
            can : {
                authorize : __('already authorized'),
                pause : true,
                terminate : true,
                report : true
            }
        },
        paused : {
            code : 'PAUSED',
            label : __('Paused'),
            can : {
                authorize : __('is paused'),
                pause : __('is already paused'),
                terminate : true,
                report : true
            }
        },
        completed : {
            code : 'COMPLETED',
            label : __('Completed'),
            can : {
                authorize : __('is completed'),
                pause : __('is completed'),
                terminate : __('is completed'),
                report : true
            }
        },
        terminated : {
            code : 'TERMINATED',
            label : __('Terminated'),
            can : {
                authorize : __('is terminated'),
                pause : __('is terminated'),
                terminate : __('is terminated'),
                report : true
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
     * @param {string} statusName
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

    return {
        getStatus : getStatus,
        getStatusByCode : getStatusByCode,
        verifyTestTaker : verifyTestTaker
    };
});

