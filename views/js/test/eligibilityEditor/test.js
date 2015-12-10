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
 * @author Jean-Sébastien Conan <jean-sebastien.conan@vesperiagroup.com>
 */
define([
    'jquery',
    'lodash',
    'taoProctoring/component/eligibilityEditor'
], function($, _, eligibilityEditor){
    'use strict';


    QUnit.test('render (all options)', 0, function(assert){

        var $container = $('#fixture-1');
        var deliveries = [
            {"uri" : "http:\/\/tao.local\/mytao.rdf#i14496515319645147", "label" : "Delivery A"},
            {"uri" : "http:\/\/tao.local\/mytao.rdf#i1449651502115597", "label" : "Delivery B"},
            {"uri" : "http:\/\/tao.local\/mytao.rdf#i14496515079910121a", "label" : "Delivery C"},
            {"uri" : "http:\/\/tao.local\/mytao.rdf#i14496515079910121b", "label" : "Delivery D"},
            {"uri" : "http:\/\/tao.local\/mytao.rdf#i14496515079910121c", "label" : "Delivery E"}
        ];
        var eligibilities = [
            {
                "delivery" : "http:\/\/tao.local\/mytao.rdf#i14496515319645147",
                "testTakers" : ['testTakerA','testTakerB','testTakerC']
            },
            {
                "delivery" : "http:\/\/tao.local\/mytao.rdf#i1449651502115597",
                "testTakers" : ['testTakerA']
            }
        ];
        
        var editor = eligibilityEditor.init($container, eligibilities, deliveries);
        editor.on('ok', function(eligibility){
            console.log('AAAAAAAAAAAAa', eligibility);
        });
        return;


        var config = {
            renderTo : $container,
            actionName : 'Resume Test Session',
            resourceType : 'test taker',
            categoriesDefinitions : [
                {
                    id : 'reason1',
                    placeholder : 'Reason 1'
                },
                {
                    id : 'reason2',
                    placeholder : 'Reason 2'
                },
                {
                    id : 'reason3',
                    placeholder : 'Reason 3'
                }
            ],
            categories : [
                {
                    id : 'optionA',
                    label : 'option A',
                    categories : [
                        {
                            id : 'optionA1',
                            label : 'option A-1',
                            categories : [
                                {id : 'option A1a', label : 'option A-1-a'},
                                {id : 'option A1b', label : 'option A-1-b'},
                                {id : 'option A1c', label : 'option A-1-c'}
                            ]
                        },
                        {
                            id : 'optionA2',
                            label : 'option A-2',
                            categories : [
                                {id : 'option A2a', label : 'option A-2-a'},
                                {id : 'option A2b', label : 'option A-2-b'}
                            ]
                        },
                        {
                            label : 'option A-3'
                        }
                    ]
                },
                {
                    id : 'optionB',
                    label : 'option B',
                    categories : [
                        {id : 'option B1', label : 'option B-1'},
                        {id : 'option B2', label : 'option B-2'},
                        {id : 'option B3', label : 'option B-3'},
                        {id : 'option B4', label : 'option B-4'}
                    ]
                },
                {
                    id : 'option_C',
                    label : 'option C'
                }
            ]
            ,
            reason : true,
            allowedResources : [
                {
                    id : 'uri_ns#i0000001',
                    label : 'Test Taker 1'
                },
                {
                    id : 'uri_ns#i0000002',
                    label : 'Test Taker 2'
                },
                {
                    id : 'uri_ns#i0000003',
                    label : 'Test Taker 3'
                },
                {
                    id : 'uri_ns#i0000004',
                    label : 'Test Taker 4'
                },
                {
                    id : 'uri_ns#i0000005',
                    label : 'Test Taker 5'
                },
                {
                    id : 'uri_ns#i0000006',
                    label : 'Test Taker 6'
                },
                {
                    id : 'uri_ns#i0000007',
                    label : 'Test Taker 7'
                },
                {
                    id : 'uri_ns#i0000008',
                    label : 'Test Taker 8'
                },
                {
                    id : 'uri_ns#i0000009',
                    label : 'Test Taker 9'
                },
                {
                    id : 'uri_ns#i0000010',
                    label : 'Test Taker 10'
                },
                {
                    id : 'uri_ns#i0000011',
                    label : 'Test Taker 11'
                },
                {
                    id : 'uri_ns#i0000012',
                    label : 'Test Taker with exessiiiiiiiiiiiiive loooooooooooooong loooooooooooooong label'
                }
            ],
            deniedResources : [
                {
                    id : 'uri_ns#i1000001',
                    label : 'Test Taker a',
                    reason : 'too tired'
                },
                {
                    id : 'uri_ns#i1000002',
                    label : 'Test Taker b',
                    reason : 'too sleepy'
                },
                {
                    id : 'uri_ns#i1000003',
                    label : 'Test Taker c',
                    reason : 'too affraid'
                },
                {
                    id : 'uri_ns#i1000004',
                    label : 'Test Taker d',
                    reason : 'does not want to'
                }
            ]
        };
        var $element;
        var instance = bulkActionPopup(config);
        assert.equal($container[0], instance.getContainer()[0], 'container ok');

        $element = $container.children('.bulk-action-popup');
        assert.equal($element.length, 1, 'element ok');
        assert.equal($element.find('.applicables li').length, 12, 'allowed resources are displayed');
        assert.equal($element.find('.no-applicables li').length, 4, 'denied resources are displayed');
        assert.equal($element.children('.reason').length, 1, 'the reason box is displayed');
    });

});
