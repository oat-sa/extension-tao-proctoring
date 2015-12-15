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
    'taoProctoring/component/eligibilityEditor',
    'ui/datatable'
], function(
    $,
    _,
    __,
    helpers,
    loadingBar,
    encode,
    feedback,
    eligibilityEditor
    ){
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.eligible-deliveries';
    
    /**
     * Format the raw eligibility from the dataset to the expected format for the eligibility editor
     * 
     * @param {Object} dataset
     * @returns {Array}
     */
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

    /**
     * Controls the test center manager screen
     *
     * @type {Object}
     */
    var editCenterCtlr = {
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
            var addUrl = helpers._url('addEligibilities', 'TestCenterManager', 'taoProctoring', {uri : testCenterId});
            var editUrl = helpers._url('editEligibilities', 'TestCenterManager', 'taoProctoring', {uri : testCenterId});
            var removeUrl = helpers._url('removeEligibilities', 'TestCenterManager', 'taoProctoring', {uri : testCenterId});
            var tools = [];
            var actions = [];
            var model = [];
            
            function _getDelivery(uri){
                return _.find(deliveries, {uri : uri});
            }

            // request the server with a selection of test takers
            function _request(url, eligibility, message, errorCallback){
                if(eligibility){
                    
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
                        }else if(_.isFunction(errorCallback)){
                            //execute a final callback if needed
                            errorCallback(response);
                        }else{
                            feedback().error(__('Something went wrong ...') + '<br>' + encode.html(response.error), {encodeHtml : false});
                        }
                    });
                }
            }
            
            //tool : add new eligibility
            tools.push({
                id : 'add',
                icon : 'add',
                title : __('Add'),
                label : __('Add'),
                action : function(){
                    //open modal to select delivery + test takers
                    eligEditor = eligibilityEditor.init($eligibilityEditor, formatEligibilities(eligibilities));
                    eligEditor.on('ok', function(eligibility){
                        _request(addUrl, eligibility, __('New eligible delivery added'), function(res){
                            feedback().warning(__('The following delivery(ies) are already eligible : ')+res.failed.join(', '));
                            $list.datatable('refresh');
                        });
                    });
                }
            });
            
            //action : edit existing eligibility
            actions.push({
                id : 'edit',
                icon : 'edit',
                label : __('Edit'),
                title : __('Edit eligibile test takers'),
                action : function(uri){
                    //open modal to select test takers
                    eligEditor = eligibilityEditor.init($eligibilityEditor, formatEligibilities(eligibilities), _getDelivery(uri));
                    eligEditor.on('ok', function(eligibility){
                        _request(editUrl, eligibility, __('Eligible test takers updated'));
                    });
                }
            });
            
            //action : remove existing eligibility
            actions.push({
                id : 'remove',
                icon : 'bin',
                label : __('Remove'),
                title : __('Remove eligibility'),
                action : function(uri){
                    //open modal to select test takers
                    _request(removeUrl, {deliveries : [uri]}, __('Eligible delivery removed'));
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
                    loadingBar.stop();
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

    return editCenterCtlr;
});
