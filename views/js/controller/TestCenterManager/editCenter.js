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
define([
    'jquery',
    'lodash',
    'i18n',
    'helpers',
    'layout/loading-bar',
    'util/encode',
    'ui/feedback',
    'ui/dialog',
    'taoProctoring/component/eligibilityEditor',
    'css!taoProctoringCss/testCenterManager'
], function(
    $,
    _,
    __,
    helpers,
    loadingBar,
    encode,
    feedback,
    dialog,
    eligibilityEditor
    ){
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.eligible-deliveries';

    function formatEligibilities(dataset){
        if(dataset.data && _.isArray(dataset.data)){
            return _.map(dataset.data, function(eligibility){
                return {
                    delivery : eligibility.id,
                    testTakers : eligibility.testTakers
                };
            });
        }
        return [];
    }

    //test mock
    var _eligibilities = {
        page : 1,
        total : 1,
        data : [
            {
                id : 'http://tao.local/mytao.rdf#i1449752331825885',
                "testTakers" : [
                    {
                        uri : 'ttA',
                        label : 'testTakerA'
                    },
                    {
                        uri : 'ttB',
                        label : 'testTakerB'
                    },
                    {
                        uri : 'ttC',
                        label : 'testTakerC'
                    }
                ]
            },
            {
                id : 'http://tao.local/mytao.rdf#i14497523428335109',
                "testTakers" : [
                    {
                        uri : 'ttA',
                        label : 'testTakerA'
                    }
                ]
            }
        ]
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
        start : function start(){

            var $container = $(cssScope);
            var $list = $container.find('.list');
            var eligEditor;
            var $eligibilityEditor = $container.find('.eligibility-editor-container');
            var deliveries = $container.data('deliveries');
            var eligibilities = $container.data('eligibilities');
            var testCenterId = $container.data('testcenter');
            var serviceUrl = helpers._url('getEligibilities', 'TestCenterManager', 'taoProctoring', {uri : testCenterId});
            var addUrl = helpers._url('addEligibility', 'TestCenterManager', 'taoProctoring', {uri : testCenterId});
            var editUrl = helpers._url('editEligibility', 'TestCenterManager', 'taoProctoring', {uri : testCenterId});
            var removeUrl = helpers._url('removeEligibility', 'TestCenterManager', 'taoProctoring', {uri : testCenterId});
            var tools = [];
            var actions = [];
            var model = [];
            console.log('init data', deliveries, eligibilities);

            function _getDelivery(uri){
                return _.find(deliveries, {uri : uri});
            }

            // request the server with a selection of test takers
            function _request(url, eligibility, message){
                if(eligibility){

                    console.log(eligibility);

                    loadingBar.start();
                    
                    $.ajax({
                        url : url,
                        data : {
                            eligibility : eligibility
                        },
                        dataType : 'json',
                        type : 'POST',
                        error : function(){
                            loadingBar.stop();
                        }
                    }).done(function(response){
                        loadingBar.stop();

                        if(response && response.success){
                            if(message){
                                feedback().success(message);
                            }
                            $list.datatable('refresh');
                        }else{
                            feedback().error(__('Something went wrong ...') + '<br>' + encode.html(response.error), {encodeHtml : false});
                        }
                    });
                }
            }

            // tool: page refresh
            tools.push({
                id : 'add',
                icon : 'add',
                title : __('Add'),
                label : __('Add'),
                action : function(){
                    //open modal to select delivery + test takers
                    eligEditor = eligibilityEditor.init($eligibilityEditor, formatEligibilities(eligibilities), deliveries);
                    eligEditor.on('ok', function(eligibility){
                        _request(addUrl, eligibility, __('New eligible delivery added'));
                    });
                }
            });

            actions.push({
                id : 'edit',
                icon : 'edit',
                label : __('Edit'),
                title : __('Edit eligibile test takers'),
                action : function(uri){
                    //open modal to select test takers
                    eligEditor = eligibilityEditor.init($eligibilityEditor, formatEligibilities(eligibilities), deliveries, _getDelivery(uri));
                    eligEditor.on('ok', function(eligibility){
                        _request(editUrl, eligibility, __('Eligible test takers updated'));
                    });
                }
            });

            actions.push({
                id : 'remove',
                icon : 'bin',
                label : __('Remove'),
                title : __('Remove eligibility'),
                action : function(uri){
                    //open modal to select test takers
                    _request(removeUrl, {delivery : uri}, __('Eligible delivery removed'));
                }
            });


            // column: delivery
            model.push({
                id : 'del',
                label : __('Delivery'),
                transform : function(value, row){
                    return _getDelivery(row.id).label;

                }
            });

            // column: test taker
            model.push({
                id : 'ttakers',
                label : __('Eligible Test Takers'),
                transform : function(value, row){
                    return row.testTakers.length;
                }
            });

            // renders the datatable
            $list
                .on('query.datatable', function(){
                    loadingBar.start();
                })
                .on('load.datatable', function(e, newDataset){
                    //update dateset in memory
                    eligibilities = newDataset;
                    console.log('reloaded data', eligibilities);
                    loadingBar.stop();
                })
                .on('select.datatable', function(e, newDataset){

                })
                .datatable({
                    url : serviceUrl,
                    status : {
                        empty : __('No Eligible Delivery yet'),
                        available : __('Eligible Deliveries'),
                        loading : __('Loading')
                    },
                    tools : tools,
                    actions : actions,
                    model : model,
                    selectable : false
                }, eligibilities);

        }
    };

    return proctorDeliveryIndexCtlr;
});
