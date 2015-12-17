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
 * @author Sam <sam@taotesting.com>
 */
define([
    'lodash',
    'jquery',
    'i18n',
    'helpers',
    'layout/loading-bar',
    'util/encode',
    'ui/feedback',
    'ui/dialog/confirm',
    'ui/bulkActionPopup',
    'ui/datalist',
    'taoProctoring/component/breadcrumbs',
    'taoProctoring/component/proctorForm',
    'ui/datatable'
], function (_, $, __, helpers, loadingBar, encode, feedback, dialogConfirm, bulkActionPopup, datalist, breadcrumbsFactory, proctorForm) {
    'use strict';
    
    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.proctorManager-index';
    
    //service urls:
    var proctorsDataUrl = helpers._url('proctorAuthorizations', 'ProctorManager', 'taoProctoring');
    var authorizeUrl = helpers._url('authorize', 'ProctorManager', 'taoProctoring');
    var unauthorizeUrl = helpers._url('unauthorize', 'ProctorManager', 'taoProctoring');

    // page modes
    var _modes = {
        EMPTY: 0,
        LIST: 1,
        FORM: 2
    };

    // translation map for status
    var _status = {
        0 : '',
        1 : __('Partially authorized'),
        2 : __('Authorized')
    };

    // the page is always loading data when starting
    loadingBar.start();

    /**
     * Controls the ProctorDelivery index page
     *
     * @type {Object}
     */
    var taoProctoringCtlr = {
        /**
         * Entry point of the page
         */
        start : function start() {
            
            var $container = $(cssScope);
            var $panelSelection = $('.test-center-panel');
            var $panelData = $('.proctor-panel');
            var $containerList = $('.proctor-list');
            var $containerForm = $('.proctor-create');
            var $noSelection = $('.proctor-default');
            var testCenters = $container.data('list');
            var crumbs = $container.data('breadcrumbs');
            var pageMode = _modes.EMPTY;
            var bc = breadcrumbsFactory($container, crumbs);
            var list = datalist({
                renderTo: $panelSelection,
                textNumber:  __('Test sites'),
                labelText: __('Test site'),
                selectable: true
            }, testCenters);

            function request(url, selection, message) {
                if (selection && selection.length) {
                    loadingBar.start();

                    $.ajax({
                        url: url,
                        data: {
                            testCenters: list.getSelection(),
                            proctors: selection
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
                            $containerList.datatable('refresh');
                        } else {
                            feedback().error(__('Something went wrong ...') + '<br>' + encode.html(response.error), {encodeHtml: false});
                        }
                    });
                }
            }

            function authorize(selection, message) {
                dialogConfirm(message, function() {
                    request(authorizeUrl, selection, __('Proctors authorized'));
                });
            }

            function revoke(selection, message) {
                dialogConfirm(message, function() {
                    request(unauthorizeUrl, selection, __('Proctors revoked'));
                });
            }

            function processMode(selection) {
                if (!selection.length) {
                    if (_modes.LIST === pageMode) {
                        pageMode = _modes.EMPTY;
                    } else if (_modes.EMPTY !== pageMode) {
                        feedback().warning(__('You must select at least one test center!'));
                    }
                } else {
                    switch (pageMode) {
                        case _modes.EMPTY:
                            pageMode = _modes.LIST;

                        case _modes.LIST:
                            $containerList
                                // erase previous parameters to prevent the datatable to keep old values
                                .datatable('options', {
                                    params: {testCenters: null}
                                })
                                // set new parameter value
                                .datatable('options', {
                                    params: {testCenters: selection}
                                })
                                .datatable('refresh');
                            break;

                        case _modes.FORM:
                            // the form manages itself the selection
                            break;
                    }
                }

                $noSelection.toggleClass('hidden', _modes.EMPTY !== pageMode);
                $containerList.toggleClass('hidden', _modes.LIST !== pageMode);
                $containerForm.toggleClass('hidden', _modes.FORM !== pageMode);
            }

            list.on('select', function(selection) {
                processMode(selection);
            });

            $containerList
                .on('query.datatable', function() {
                    loadingBar.start();
                })
                .on('load.datatable', function() {
                    loadingBar.stop();
                })
                .datatable({
                    url: proctorsDataUrl,
                    status: {
                        empty: __('No authorized proctors'),
                        available: __('Authorized proctors'),
                        loading: __('Loading')
                    },
                    tools: [{
                        id: 'refresh',
                        icon: 'reset',
                        title: __('Refresh the page'),
                        label: __('Refresh'),
                        action: function() {
                            $panelData.datatable('refresh');
                        }
                    }, {
                        id: 'create',
                        icon: 'add',
                        title: __('Creates and authorizes proctor'),
                        label: __('Create Proctor'),
                        action: function() {
                            var selection = list.getSelection();
                            // switch to proctorForm
                            proctorForm({
                                renderTo : $containerForm,
                                testCenterList : list
                            }).on('destroy', function() {
                                pageMode = _modes.LIST;
                                processMode(list.getSelection());
                            });
                            pageMode = _modes.FORM;
                            processMode(selection);
                        }
                    }, {
                        id: 'authorize',
                        icon: 'authorize',
                        title: __('Authorize the selected proctors'),
                        label: __('Authorize'),
                        massAction: true,
                        action: function(selection) {
                            authorize(selection, __('The proctors will be authorized. Continue ?'));
                        }
                    }, {
                        id: 'revoke',
                        icon: 'revoke',
                        title: __('Revoke authorization for the selected proctors'),
                        label: __('Revoke'),
                        massAction: true,
                        action: function(selection) {
                            revoke(selection, __('The proctors will be revoked. Continue ?'));
                        }
                    }],
                    actions: [{
                        id: 'authorize',
                        icon: 'authorize',
                        title: __('Authorize the proctor'),
                        action: function(id) {
                            authorize([id], __('The proctor will be authorized. Continue ?'));
                        }
                    }, {
                        id: 'revoke',
                        icon: 'revoke',
                        title: __('Revoke the proctor'),
                        action: function(id) {
                            revoke([id], __('The proctor will be revoked. Continue ?'));
                        }
                    }],
                    selectable: true,
                    model: [{
                        id: 'firstname',
                        label: __('First name')
                    }, {
                        id: 'lastname',
                        label: __('Last name')
                    }, {
                        id: 'login',
                        label: __('Login')
                    }, {
                        id: 'status',
                        label: __('Status'),
                        transform: function(value) {
                            return _status[value] || '';
                        }
                    }]
                }, []);

            loadingBar.stop();
        }
    };

    return taoProctoringCtlr;
});
