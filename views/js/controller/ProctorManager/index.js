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
    'taoProctoring/helper/textConverter',
    'tpl!taoProctoring/templates/proctorManager/counters',
    'tpl!taoProctoring/templates/proctorManager/status',
    'ui/datatable'
], function (_, $, __, helpers, loadingBar, encode, feedback, dialogConfirm, bulkActionPopup, datalist, breadcrumbsFactory, proctorForm, textConverter, counterTpl, statusTpl) {
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
            textConverter().then(function(labels) {
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
                        request(authorizeUrl, selection, labels.get('Proctors authorized'));
                    });
                }

                function revoke(selection, message) {
                    dialogConfirm(message, function() {
                        request(unauthorizeUrl, selection, labels.get('Proctors revoked'));
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
                    .on('load.datatable', function(e, dataset) {
                        var partially = 0;
                        var authorized = 0;
                        var lines = dataset && dataset.data;
                        _.forEach(lines, function(line) {
                            if (line && line.status) {
                                switch (line.status) {
                                    case 1: partially ++; break;
                                    case 2: authorized ++; break;
                                }
                            }

                        });

                        $(this).find('.datatable-wrapper h2').append(counterTpl([{
                            id: 'authorized-list',
                            label: labels.get('Authorized proctors'),
                            count: authorized
                        }, {
                            id: 'partially-list',
                            label: labels.get('Partially authorized proctors'),
                            count: partially
                        }]));

                        loadingBar.stop();
                    })
                    .datatable({
                        url: proctorsDataUrl,
                        status: {
                            empty: labels.get('No assigned proctors'),
                            available: labels.get('Assigned proctors'),
                            loading: __('Loading')
                        },
                        tools: [{
                            id: 'refresh',
                            icon: 'reset',
                            title: __('Refresh the page'),
                            label: __('Refresh'),
                            action: function() {
                                $containerList.datatable('refresh');
                            }
                        }, {
                            id: 'create',
                            icon: 'add',
                            title: labels.get('Creates and authorizes proctor'),
                            label: labels.get('Create Proctor'),
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
                            title: labels.get('Authorize the selected proctors'),
                            label: __('Authorize'),
                            massAction: true,
                            action: function(selection) {
                                authorize(selection, labels.get('The proctors will be authorized. Continue ?'));
                            }
                        }, {
                            id: 'revoke',
                            icon: 'revoke',
                            title: labels.get('Revoke authorization for the selected proctors'),
                            label: __('Revoke'),
                            massAction: true,
                            action: function(selection) {
                                revoke(selection, labels.get('The proctors will be revoked. Continue ?'));
                            }
                        }],
                        actions: [{
                            id: 'authorize',
                            icon: 'authorize',
                            title: labels.get('Authorize the proctor'),
                            hidden: function() {
                                return this.status === 2;
                            },
                            action: function(id) {
                                authorize([id], labels.get('The proctor will be authorized. Continue ?'));
                            }
                        }, {
                            id: 'revoke',
                            icon: 'revoke',
                            title: labels.get('Revoke the proctor'),
                            hidden: function() {
                                return !this.status;
                            },
                            action: function(id) {
                                revoke([id], labels.get('The proctor will be revoked. Continue ?'));
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
                            id: 'state',
                            label: __('Status'),
                            transform: function(value, row) {
                                return statusTpl({
                                    testCenters: row.authorized,
                                    partially: row.status === 1,
                                    authorized: row.status === 2
                                });
                            }
                        }]
                    }, []);

                loadingBar.stop();
            }).catch(function (err) {
                console.error(err);
            });
        }
    };

    return taoProctoringCtlr;
});
