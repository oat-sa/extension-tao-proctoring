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
    'tpl!taoProctoring/component/eligibilityEditor/deliverySelector',
    'select2',
    'ui/modal'
], function(_, $, __, helpers, eventifier, GenerisTreeSelectClass, layoutTpl, deliverySelectorTpl){
    
    var _ns = '.eligibility-editor';
    
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
            checkedNodes : _.pluck(testTakers, 'idEncoded'), //generis tree uses "encoded uri" to check nodes
            serverParameters : {
                openParentNodes : _.pluck(testTakers, 'id'), //generis tree uses normal if to open nodes...
                rootNode : 'http://www.tao.lu/Ontologies/TAOSubject.rdf#Subject'
            },
            paginate : 10,
            onChangeCallback : function(){
                _.delay(function(){
                    //requires a delay to let the node status to be updated
                    instance.eligibility.testTakers = _.uniq(tree.getChecked());
                    instance.trigger('change');
                }, 100);
            }
        });

        return tree;
    }
    
    /**
     * Build the delivery selector combobox
     * 
     * @param {Object} instance
     * @param {JQuery} $container
     * @param {Array} deliveries
     * @param {Array} eligibles
     * @returns {undefined}
     */
    function buildDeliverySelector(instance, $container, deliveries, eligibles){

        var selectables = _.reject(deliveries, function(delivery){
            //remove all deliveries that are already eligible
            return (_.indexOf(eligibles, delivery.uri) >= 0);
        });
        var $selectorContainer = $(deliverySelectorTpl({
            deliveries : selectables
        }));


        //init select 2 on $comboBox
        var $select = $selectorContainer.find('select');

        //add event handler
        $select.on('change', function(e){
            if(e.val){
                instance.eligibility.delivery = e.val;
                instance.trigger('change');
            }
        });

        $select.select2({
            dropdownAutoWidth : true,
            placeholder : __('select...'),
            minimumResultsForSearch : -1
        });

        $container.append($selectorContainer);
    }

    var _modalDefaults = {
        width : 400
    };

    /**
     * Add the editor into a popup and display it
     * 
     * @param {Object} instance - the eligibility editor instance
     * @param {Object} [modalConfig] - any config option available in ui/modal
     */
    function initModal(instance, modalConfig){

        modalConfig = _.defaults(modalConfig || {}, _modalDefaults);

        instance.$container
            .addClass('modal')
            .on('closed.modal', function(){
                //one shot only, on close, destroy the widget
                destroy(instance);
            })
            .modal(modalConfig)
            .on('click' + _ns, '.actions .done', function(e){
                
                instance.trigger('ok', instance.eligibility);
                destroy(instance);
                
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
        
        instance.$container
            .empty()
            .off(_ns)
            .removeClass('modal')
            .modal('destroy');
    }
    
    /**
     * Create an eligibility editor into a $container
     * 
     * @param {JQuery} $container
     * @param {Array} eligibilities
     * @param {Array} deliveries
     * @param {Object} [delivery]
     * @param {String} [delivery.label]
     * @param {String} [delivery.uri]
     * @returns {Object} the eligibility editor instance
     */
    function init($container, eligibilities, deliveries, delivery){

        var instance = eventifier({
            eligibility : {}
        });
        var testTakers = [];
        var treeId = _.uniqueId('eligible-testTaker-tree-');//generating the generis tree id, because it requires one to work
        var $deliverySelector;
        var creationMode = true;
        
        if(!_.isArray(eligibilities) || !_.isArray(deliveries)){
            throw 'the egibility editor requires an array of eligibilities and an array of deliveries';
        }
        
        if(delivery && delivery.uri && delivery.label){
            var eligibility = _.find(eligibilities, {delivery : delivery.uri});
            if(eligibility){
                instance.eligibility = eligibility;
                creationMode = false;
            }else{
                throw ('given delivery does not exist in the list of eligibilities');
            }
        }
        
        instance.$container = $container;
        $container.append(layoutTpl({
            title : creationMode ? __('Add Eligibility') : __('Edit Eligibility'),
            treeId : treeId
        }));

        if(creationMode){
            //init delivery selector only when no delivery is selected
            $deliverySelector = $container.find('.eligible-delivery-select');
            buildDeliverySelector(instance, $deliverySelector, deliveries, _.pluck(eligibilities, 'delivery'));
        }

        //init test taker selector
        buildTestTakerTree(instance, '#' + treeId, testTakers);

        //init modal
        initModal(instance);

        return instance;
    }

    return {
        init : init
    };
});