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
    var _awaiting = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusAwaiting',
        _authorized = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusAuthorized',
        _inprogress = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusActive',
        _paused = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusPaused',
        _completed = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusFinished',
        _terminated = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusTerminated',
        _canceled = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusCanceled';

    var _status = {
        inprogress : {
            code : _inprogress,
            label : __('In Progress'),
            can : {
                authorize : __('already authorized'),
                pause : true,
                terminate : true,
                report : true,
                print : __('not finished'),
                reactivate : __('not terminated'),
                time : true
            },
            warning : {
                authorize : function (username, testId) {
                    if (testId) {
                        return __('Test %s had already been authorized.', testId);
                    } else if (username) {
                        return __('%s\'s test had already been authorized.', username);
                    } else {
                        return __('Test had already been authorized.');
                    }
                },
                print : function (username, testId) {
                    if (testId) {
                        return __('Test %s had not been finished.', testId);
                    } else if (username) {
                        return __('%s\'s test had not been finished.', username);
                    } else {
                        return __('Test had not been finished.');
                    }
                },
                reactivate : function (username, testId) {
                    if (testId) {
                        return __('Test %s must be terminated.', testId);
                    } else if (username) {
                        return __('%s\'s test must be terminated.', username);
                    } else {
                        return __('Test  must be terminated.');
                    }
                }
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
                reactivate : __('not terminated'),
                time : true
            },
            warning : {
                authorize : function (username, testId) {
                    if (testId) {
                        return __('Test %s had already been authorized.', testId);
                    } else if (username) {
                        return __('%s\'s test had already been authorized.', username);
                    } else {
                        return __('Test had already been authorized.');
                    }
                },
                pause : function (username, testId) {
                    if (testId) {
                        return __('Test %s had not been started.', testId);
                    } else if (username) {
                        return __('%s\'s test had not been started.', username);
                    } else {
                        return __('Test had not been started.');
                    }
                },
                print : function (username, testId) {
                    if (testId) {
                        return __('Test %s had not been finished.', testId);
                    } else if (username) {
                        return __('%s\'s test had not been finished.', username);
                    } else {
                        return __('Test had not been finished.');
                    }
                },
                reactivate : function (username, testId) {
                    if (testId) {
                        return __('Test %s must be terminated.', testId);
                    } else if (username) {
                        return __('%s\'s test must be terminated.', username);
                    } else {
                        return __('Test  must be terminated.');
                    }
                }
            }
        },
        awaiting : {
            code : _awaiting,
            label : __('Awaiting'),
            can : {
                authorize : true,
                pause : __('not in progress'),
                terminate : true,
                reactivate : __('not terminated'),
                report : true,
                print : __('not finished'),
                time : true
            },
            warning : {
                pause : function (username, testId) {
                    if (testId) {
                        return __('Test %s had not been in progress.', testId);
                    } else if (username) {
                        return __('%s\'s test had not been in progress.', username);
                    } else {
                        return __('Test had not been in progress.');
                    }
                },
                print : function (username, testId) {
                    if (testId) {
                        return __('Test %s had not been finished.', testId);
                    } else if (username) {
                        return __('%s\'s test had not been finished.', username);
                    } else {
                        return __('Test had not been finished.');
                    }
                },
                reactivate : function (username, testId) {
                    if (testId) {
                        return __('Test %s must be terminated.', testId);
                    } else if (username) {
                        return __('%s\'s test must be terminated.', username);
                    } else {
                        return __('Test  must be terminated.');
                    }
                }
            }
        },
        canceled : {
            code : _canceled,
            label : __('Canceled'),
            can : {
                authorize : __('canceled'),
                pause : __('canceled'),
                terminate : __('canceled'),
                reactivate : __('not terminated'),
                report : true,
                print: __('canceled'),
                time : true
            },
            warning : {
                authorize : function (username, testId) {
                    if (testId) {
                        return __('Test %s had been canceled.', testId);
                    } else if (username) {
                        return __('%s\'s test had been canceled.', username);
                    } else {
                        return __('Test had been canceled.');
                    }
                },
                pause : function (username, testId) {
                    if (testId) {
                        return __('Test %s had been canceled.', testId);
                    } else if (username) {
                        return __('%s\'s test had been canceled.', username);
                    } else {
                        return __('Test had been canceled.');
                    }
                },
                terminate : function (username, testId) {
                    if (testId) {
                        return __('Test %s had been canceled.', testId);
                    } else if (username) {
                        return __('%s\'s test had been canceled.', username);
                    } else {
                        return __('Test had been canceled.');
                    }
                },
                reactivate : function (username, testId) {
                    if (testId) {
                        return __('Test %s had been canceled.', testId);
                    } else if (username) {
                        return __('%s\'s test had been canceled.', username);
                    } else {
                        return __('Test had been canceled.');
                    }
                },
                print : function (username, testId) {
                    if (testId) {
                        return __('Test %s had been canceled.', testId);
                    } else if (username) {
                        return __('%s\'s test had been canceled.', username);
                    } else {
                        return __('Test had been canceled.');
                    }
                }
            }
        },
        completed : {
            code : _completed,
            label : __('Completed'),
            can : {
                authorize : __('completed'),
                pause : __('completed'),
                terminate : __('completed'),
                reactivate : __('not terminated'),
                report : true,
                print: true,
                time : true
            },
            warning : {
                authorize : function (username, testId) {
                    if (testId) {
                        return __('Test %s had been completed.', testId);
                    } else if (username) {
                        return __('%s\'s test had been completed.', username);
                    } else {
                        return __('Test had been completed.');
                    }
                },
                pause : function (username, testId) {
                    if (testId) {
                        return __('Test %s had been completed.', testId);
                    } else if (username) {
                        return __('%s\'s test had been completed.', username);
                    } else {
                        return __('Test had been completed.');
                    }
                },
                terminate : function (username, testId) {
                    if (testId) {
                        return __('Test %s had been completed.', testId);
                    } else if (username) {
                        return __('%s\'s test had been completed.', username);
                    } else {
                        return __('Test had been completed.');
                    }
                }
                ,
                reactivate : function (username, testId) {
                    if (testId) {
                        return __('Test %s must be terminated.', testId);
                    } else if (username) {
                        return __('%s\'s test must be terminated.', username);
                    } else {
                        return __('Test  must be terminated.');
                    }
                }
            }
        },
        paused : {
            code : _paused,
            label : __('Paused'),
            can : {
                authorize : __('paused'),
                pause : __('already paused'),
                reactivate : __('not terminated'),
                terminate : true,
                report : true,
                print : __('not finished'),
                time : true
            },
            warning : {
                authorize : function (username, testId) {
                    if (testId) {
                        return __('Test %s had been paused.', testId);
                    } else if (username) {
                        return __('%s\'s test had been paused.', username);
                    } else {
                        return __('Test had been paused.');
                    }
                },
                pause : function (username, testId) {
                    if (testId) {
                        return __('Test %s had already been paused.', testId);
                    } else if (username) {
                        return __('%s\'s test had already been paused.', username);
                    } else {
                        return __('Test had already been paused.');
                    }
                },
                print : function (username, testId) {
                    if (testId) {
                        return __('Test %s had not been finished.', testId);
                    } else if (username) {
                        return __('%s\'s test had not been finished.', username);
                    } else {
                        return __('Test had not been finished.');
                    }
                },
                reactivate : function (username, testId) {
                    if (testId) {
                        return __('Test %s must be terminated.', testId);
                    } else if (username) {
                        return __('%s\'s test must be terminated.', username);
                    } else {
                        return __('Test  must be terminated.');
                    }
                }
            }
        },
        terminated : {
            code : _terminated,
            label : __('Terminated'),
            can : {
                authorize : __('terminated'),
                pause : __('terminated'),
                terminate : __('already terminated'),
                report : true,
                reactivate : true,
                print: true,
                time : true
            },
            warning : {
                authorize : function (username, testId) {
                    if (testId) {
                        return __('Test %s had been terminated.', testId);
                    } else if (username) {
                        return __('%s\'s test had been terminated.', username);
                    } else {
                        return __('Test had been terminated.');
                    }
                },
                pause : function (username, testId) {
                    if (testId) {
                        return __('Test %s had been terminated.', testId);
                    } else if (username) {
                        return __('%s\'s test had been terminated.', username);
                    } else {
                        return __('Test had been terminated.');
                    }
                },
                terminate : function (username, testId) {
                    if (testId) {
                        return __('Test %s had already been terminated.', testId);
                    } else if (username) {
                        return __('%s\'s test had already been terminated.', username);
                    } else {
                        return __('Test had alredy been terminated.');
                    }
                },
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

    /**
     * Create a warning message for execBulkAction()
     * @param {String} action
     * @param {String[]} selection
     * @param {Object[]} deniedResources
     * @returns {String}
     */
    function buildWarningMessage(action, selection, deniedResources) {
        var isPlural = selection.length > 1;
        var maxReasons = 2;
        var warningAction;
        var warningOmission = __('...');
        var warningReason;

        deniedResources = deniedResources || [{}];

        switch (action) {
            case 'authorize':
                warningAction = isPlural ?
                    __('Cannot authorize test sessions.') :
                    __('Cannot authorize test session.');
                break;
            case 'pause':
                warningAction = isPlural ?
                    __('Cannot pause test sessions.') :
                    __('Cannot pause test session.');
                break;
            case 'terminate':
                warningAction = isPlural ?
                    __('Cannot terminate test sessions.') :
                    __('Cannot terminate test session.');
                break;
            case 'reactivate':
                warningAction = isPlural ?
                    __('Cannot reactivate test sessions.') :
                    __('Cannot reactivate test session.');
                break;
            case 'report':
                warningAction = isPlural ?
                    __('Cannot report test sessions.') :
                    __('Cannot report test session.');
                break;
            case 'print':
                warningAction = isPlural ?
                    __('Cannot print test sessions.') :
                    __('Cannot print test session.');
                break;
            case 'time':
                warningAction = isPlural ?
                    __('Cannot extend test sessions.') :
                    __('Cannot extend test session.');
                break;
            default:
                warningAction = __('Cannot execute action.');
        }

        warningReason = _(deniedResources)
            .slice(0, maxReasons)
            .map(function (deniedResource) {
                return deniedResource.warning;
            })
            .join(' ');

        if (deniedResources.length > maxReasons) {
            warningReason += warningOmission;
        }

        return warningAction + ' ' + warningReason;
    }

    return {
        buildWarningMessage : buildWarningMessage,
        getStatuses : getStatuses,
        getStatus : getStatus,
        getStatusByCode : getStatusByCode,
        verifyTestTaker : verifyTestTaker,
        STATUS_AUTHORIZED : _authorized,
        STATUS_INPROGRESS : _inprogress,
        STATUS_PAUSED : _paused,
        STATUS_COMPLETED : _completed,
        STATUS_TERMINATED : _terminated
    };
});

