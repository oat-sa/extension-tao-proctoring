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
define([
    'lodash',
    'jquery',
    'i18n',
    'helpers',
    'core/eventifier',
    'generis.tree.select',
    'tpl!taoProctoring/component/eligibilityEditor/layout',
    'ui/feedback',
    'ui/modal',
    'css!taoProctoringCss/eligibilityEditor'
], function(_, $, __, helpers, eventifier, GenerisTreeSelectClass, layoutTpl, feedback){
    'use strict';
    
    var _ns = '.eligibility-editor';

    var _modalDefaults = {
        width : 600
    };

    /**
     * Builds group tree inside target container
     * 
     * @param {Object} the eligibility editor instance
     * @param {String} selector - the selector for the tree (generis tree works with selector only)
     * @param {Array} [testTakers] - array of selected test takers
     */
    function buildTestTakerTree(instance, selector, testTakers){

        var tree = new GenerisTreeSelectClass(selector, helpers._url('getData', 'GenerisTree', 'tao'), {
            actionId : 'treeOptions.actionId',
            saveUrl : 'treeOptions.saveUrl',
            saveData : {},
            checkedNodes : _.pluck(testTakers, 'encodedUri'), //generis tree uses "encoded uri" to check nodes
            serverParameters : {
                openParentNodes : _.pluck(testTakers, 'uri'), //generis tree uses normal if to open nodes...
                rootNode : 'http://www.tao.lu/Ontologies/TAOSubject.rdf#Subject'
            },
            paginate : 10,
            onChangeCallback : function(){
                _.delay(function(){
                    //requires a delay to let the node status to be updated
                    instance.eligibility.testTakers = _.uniq(tree.getChecked());
                    instance.trigger('change', instance.eligibility);
                }, 100);
            }
        });

        return tree;
    }

    /**
     * Builds delivery tree inside target container
     * 
     * @param {Object} the eligibility editor instance
     * @param {String} selector - the selector for the tree (generis tree works with selector only)
     * @param {Array} [deliveries] - array of selected deliveries
     */
    function buildDeliveryTree(instance, selector, deliveries){

        var tree = new GenerisTreeSelectClass(selector, helpers._url('getData', 'GenerisTree', 'tao'), {
            actionId : 'treeOptions.actionId',
            saveUrl : 'treeOptions.saveUrl',
            saveData : {},
            checkedNodes : _.pluck(deliveries, 'encodedUri'), //generis tree uses "encoded uri" to check nodes
            serverParameters : {
                openParentNodes : _.pluck(deliveries, 'uri'), //generis tree uses normal if to open nodes...
                rootNode : 'http://www.tao.lu/Ontologies/TAODelivery.rdf#Delivery'
            },
            paginate : 10,
            onChangeCallback : function(){
                _.delay(function(){
                    //requires a delay to let the node status to be updated
                    instance.eligibility.deliveries = _.uniq(tree.getChecked());
                    instance.trigger('change', instance.eligibility);
                }, 100);
            }
        });

        return tree;
    }

    /**
     * Add the editor into a popup and display it
     * 
     * @param {Object} instance - the eligibility editor instance
     * @param {Object} [modalConfig] - any config option available in ui/modal
     */
    function initModal(instance, modalConfig){

        modalConfig = _.defaults(modalConfig || {}, _modalDefaults);

        instance.$container.children('.eligibility-editor')
            .addClass('modal')
            .on('closed.modal', function(){
                //one shot only, on close, destroy the widget
                destroy(instance);
            })
            .modal(modalConfig)
            .on('click' + _ns, '.actions .done', function(e){
                
                if(instance.eligibility && instance.eligibility.deliveries && instance.eligibility.deliveries.length){
                    instance.trigger('ok', instance.eligibility);
                    destroy(instance);
                }else{
                    feedback(instance.$container).warning(__('At least one delivery need to be selected to create'));
                }

            }).on('click' + _ns, '.actions .cancel', function(e){

                e.preventDefault();
                instance.trigger('cancel');
                destroy(instance);
            });
    }

    /**
     * Destroy the eligibility editor
     * 
     * @param {object} instance
     * @returns {undefined}
     */
    function destroy(instance){
        instance.$container.children('.eligibility-editor')
            .modal('destroy')
            .remove();
    }

    /**
     * Create an eligibility editor into a $container
     * 
     * @param {JQuery} $container
     * @param {Array} eligibilities
     * @param {Object} [delivery]
     * @param {String} [delivery.label]
     * @param {String} [delivery.uri]
     * @returns {Object} the eligibility editor instance
     */
    function init($container, eligibilities, delivery){

        var instance = eventifier({
            eligibility : {}
        });
        var subjectTreeId = _.uniqueId('eligible-testTaker-tree-');//generating the generis tree id, because it requires one to work
        var deliveryTreeId = _.uniqueId('eligible-delivery-tree-');//generating the generis tree id, because it requires one to work
        var $deliverySelector;
        var creationMode = true;
        var deliveryName = '';
        
        if(!_.isArray(eligibilities)){
            throw 'the egibility editor requires an array of eligibilities';
        }

        if(delivery && delivery.uri && delivery.label){
            var eligibility = _.find(eligibilities, {delivery : delivery.uri});
            if(eligibility){
                instance.eligibility = eligibility;
                creationMode = false;
                deliveryName = delivery.label;
            }else{
                throw ('given delivery does not exist in the list of eligibilities');
            }
        }
        instance.$container = $container;
        $container.append(layoutTpl({
            title : creationMode ? __('Add Eligibility') : __('Edit Eligibility'),
            editingMode : !creationMode,
            subjectTreeId : subjectTreeId,
            deliveryTreeId : deliveryTreeId,
            deliveryName : deliveryName
        }));

        if(creationMode){
            //init delivery selector only when no delivery is selected
            $deliverySelector = $container.find('.eligible-delivery');
            buildDeliveryTree(instance, '#' + deliveryTreeId, []);
        }

        //init test taker selector
        buildTestTakerTree(instance, '#' + subjectTreeId, instance.eligibility.testTakers || []);

        //init modal
        initModal(instance, {
            width : creationMode ? 650 : 400
        });

        return instance;
    }

    return {
        init : init
    };
});