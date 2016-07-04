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
    'jquery',
    'lodash',
    'i18n',
    'helpers',
    'uri',
    'ui/component',
    'generis.tree.select',
    'tpl!taoProctoring/component/eligibilityEditor/layout',
    'ui/feedback',
    'ui/modal',
    'css!taoProctoringCss/eligibilityEditor'
], function($, _, __, helpers, uri, component, GenerisTreeSelectClass, layoutTpl, feedback){
    'use strict';

    var _ns = '.eligibility-editor';

    var _modalDefaults = {
        width : 600
    };

    var config = {
        dataUrl :  helpers._url('getData', 'GenerisTree', 'tao')
    };

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
    var eligibilityEditorFactory = function eligibilityEditorFactory() {

        var eligibilityEditor;

        var testTakerTreeId = _.uniqueId('eligible-testTaker-tree-');//generating the generis tree id, because it requires one to work
        var deliveryTreeId  = _.uniqueId('eligible-delivery-tree-');//generating the generis tree id, because it requires one to work

        /**
         * Builds a tree to select test takers
         *
         * @param {String} id - the tree identifier, use to get the DOM node to put the tree
         * @param {String} url - the tree data url
         * @param {Array} [testTakers] - array of currently selected test takers
         * @returns {tree} the created tree
         */
        var buildTestTakerTree = function buildTestTakerTree(id, url, testTakers){

            var selected = _.pluck(testTakers, 'uri');

            return new GenerisTreeSelectClass('#' + id, url, {
                actionId : 'treeOptions.actionId',
                saveUrl : 'treeOptions.saveUrl',
                saveData : {},
                checkedNodes : _.map(selected, uri.encode), //generis tree uses "encoded uri" to check nodes
                serverParameters : {
                    openParentNodes : selected, //generis tree uses normal if to open nodes...
                    rootNode : 'http://www.tao.lu/Ontologies/TAOSubject.rdf#Subject'
                },
                paginate : 10
            });
        };

        /**
         * Builds a tree to select deliveries
         *
         * @param {String} id - the tree identifier, use to get the DOM node to put the tree
         * @param {String} url - the tree data url
         * @param {Array} [deliveries] - array of currently selected deliveris
         * @returns {tree} the created tree
         */
        var buildDeliveryTree = function buildDeliveryTree(id, url, deliveries){

            var selected = _.pluck(deliveries, 'uri');
            return new GenerisTreeSelectClass('#' + id, url, {
                actionId : 'treeOptions.actionId',
                saveUrl : 'treeOptions.saveUrl',
                saveData : {},
                checkedNodes : _.map(selected, uri.encode), //generis tree uses "encoded uri" to check nodes
                serverParameters : {
                    openParentNodes : selected, //generis tree uses normal if to open nodes...
                    rootNode : 'http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDelivery'
                },
                paginate : 10
            });
        };

        /**
         * Destroy the modal
         *
         * @param {object} instance
         * @returns {undefined}
         */
        var destroyModal = function destroyModal(){
            if(eligibilityEditor && eligibilityEditor.getElement()){
                eligibilityEditor.getElement()
                    .modal('destroy')
                    .remove();
            }
        };

        /**
         * Add the editor into a popup and display it
         *
         * @param {Object} [modalConfig] - any config option available in ui/modal
         */
        var initModal = function initModal(modalConfig){
            modalConfig = _.defaults(modalConfig || {}, _modalDefaults);

            if(eligibilityEditor && eligibilityEditor.getElement()){
                eligibilityEditor.getElement()
                    .addClass('modal')
                    .on('closed.modal', destroyModal)
                    .modal(modalConfig);
            }
        };

        /**
         * The eligibiltiyEditor API
         */
        var api = {

            /**
             * Add eligibilities
             * @param {jQueryElement} $container - where to append the component
             * @returns {eligibilityEditor} chains the component
             * @fires eligibilityEditor#ok with the selected eligibities in parameter
             * @fires eligibilityEditor#cancel
             */
            add : function add($container){
                return this.on('render', function(){
                    var self = this;
                    var deliveryTree = buildDeliveryTree(deliveryTreeId, this.config.dataUrl);
                    var testTakerTree = buildTestTakerTree(testTakerTreeId, this.config.dataUrl);

                    initModal({
                        width : 650
                    });

                    this.$component
                        .on('click' + _ns, '.actions .done', function(e){

                            var deliveries = _(deliveryTree.getChecked()).uniq().compact().value();
                            var testTakers = _(testTakerTree.getChecked()).uniq().compact().value();

                            if( deliveries && deliveries.length){
                                self.trigger('ok', {
                                    deliveries: deliveries,
                                    testTakers: testTakers
                                });
                                destroyModal();
                            } else {
                                feedback(self.$component).warning(__('At least one delivery need to be selected to create'));
                            }

                        }).on('click' + _ns, '.actions .cancel', function(e){
                            e.preventDefault();
                            destroyModal();
                            self.trigger('cancel');
                        });
                   this.trigger('open');
                })
                .init({
                    title :  __('Add Eligibility'),
                    editingMode : false,
                    subjectTreeId : testTakerTreeId,
                    deliveryTreeId : deliveryTreeId,
                })
                .render($container);

            },

            /**
             * Add eligibilities
             * @param {jQueryElement} $container - where to append the component
             * @param {String} deliveryName - the name of the eligibility's deliveryA
             * @param {Array} testTakers - the test takers already selected
             * @returns {eligibilityEditor} chains the component
             * @fires eligibilityEditor#ok with the selected test takers in parameter
             * @fires eligibilityEditor#cancel
             */
            edit : function edit($container, deliveryName, testTakers){
                return this.on('render', function(){
                    var self = this;

                    var testTakerTree = buildTestTakerTree(testTakerTreeId, this.config.dataUrl, testTakers);

                    initModal({
                        width : 400
                    });

                    this.$component
                        .on('click' + _ns, '.actions .done', function(e){

                            var testTakers = _(testTakerTree.getChecked()).uniq().compact().value();
                            self.trigger('ok', {
                                testTakers : testTakers
                            });
                            destroyModal();

                        }).on('click' + _ns, '.actions .cancel', function(e){
                            e.preventDefault();
                            destroyModal();
                            self.trigger('cancel');
                        });
                })
                .init({
                    title :  __('Edit Eligibility'),
                    editingMode : true,
                    subjectTreeId : testTakerTreeId,
                    deliveryTreeId : deliveryTreeId,
                    deliveryName : deliveryName
                })
                .render($container);
            }
        };

        //creates the component here
        eligibilityEditor = component(api, config).setTemplate(layoutTpl);

        return eligibilityEditor;
    };

    return eligibilityEditorFactory;
});
