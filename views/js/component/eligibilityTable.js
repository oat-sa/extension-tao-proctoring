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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA ;
 *
 */

/**
 * The eligibility table component.
 *
 * Manages a list of eligibilties.
 *
 * As this is mainly a refactoring, I've kept the data as they were and
 * the format used is inconsistent across the different calls...
 *
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
define([
    'jquery',
    'lodash',
    'i18n',
    'ui/component',
    'helpers',
    'tpl!taoProctoring/component/eligibilityTable/status',
    'tpl!taoProctoring/component/eligibilityTable/actions',
    'css!taoProctoringCss/eligibilityTable.css',
    'ui/datatable'
], function($, _, __, component, helpers, statusTpl, actionsTpl){
    'use strict';


    /**
     * Creates the eligibilityTable component
     *
     * @param {String} testCenterId - the test center URI
     * @returns {eligibilityTable} the component
     * @throws {TypeError} without a test center
     */
    var eligibilityTableFactory = function eligibilityTableFactory(testCenterId){
        var eligibilities = [];

        if(_.isEmpty(testCenterId)){
            throw new TypeError('The eligibility provider needs to be initialized with a test center');
        }

        /**
         * The component.
         *
         * Already initialized, only render should be called.
         *
         *
         * @typedef eligibilityTable
         * @see ui/component
         * @throws eligibilityTable#loading while loading something
         * @throws eligibilityTable#loaded when loading is done
         * @throws eligibilityTable#render once mounted to the DOM
         * @throws eligibilityTable#add action
         * @throws eligibilityTable#edit action
         * @throws eligibilityTable#remove action
         * @throws eligibilityTable#shield action
         * @throws eligibilityTable#unshield action
         */
        return component({}, {
                //config can be changed
                dataUrl : helpers._url('getEligibilities', 'TestCenterManager', 'taoProctoring', { uri : testCenterId })
            })
            .on('render', function(){
                var self = this;

                //set up the ui/datatable
                this.$component
                    .on('query.datatable', function(){
                        self.trigger('loading');
                    })
                    .on('load.datatable', function(e){
                        self.trigger('loaded');
                    })
                    .on('beforeload.datatable', function(e, dataSet){
                        if(dataSet && dataSet.data){
                            eligibilities = dataSet.data;
                        }
                    })
                    .datatable({
                        url : this.config.dataUrl,
                        status : {
                            empty:     __('No Eligible Delivery yet'),
                            available: __('Eligible Deliveries'),
                            loading:   __('Loading')
                        },
                        tools : [{
                            id : 'add',
                            icon : 'add',
                            title : __('Add'),
                            label : __('Add'),
                            action : function(){

                                /**
                                 * Add action
                                 * @event eligibilityTable#add
                                 * @param {Object} eligibilities
                                 */
                                self.trigger('add', eligibilities);
                            }
                        }],
                        model : [{
                            id : 'status',
                            label : '',
                            transform: function(value, row){
                                return statusTpl(row);
                            }
                        }, {
                            id : 'deliveryLabel',
                            label : __('Delivery'),
                            transform : function(value, row){
                                return row.delivery.label;
                            }
                        }, {
                            id : 'testTakersCount',
                            label : __('Eligible Test Takers'),
                            transform : function(value, row){
                                return row.testTakers && row.testTakers.length ? row.testTakers.length : 0;
                            }
                        }, {
                            id: 'actions',
                            label: __('Actions'),
                            transform: function(value, row){
                                return actionsTpl(row);
                            }
                        }],
                        selectable : false
                    }).on('click', '.actions button', function(e){
                        e.preventDefault();

                        var $button = $(this);
                        var itemId = $button.parents('[data-item-identifier]').data('item-identifier');
                        var action = $button.data('action');

                        self.trigger(action, itemId, eligibilities);
                    });

            })
            .on('reload', function(){
                if(this.$component){
                    this.$component.datatable('refresh');
                }
            })
            .init({});
    };

    return eligibilityTableFactory;
});
