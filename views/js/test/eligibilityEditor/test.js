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
    'taoProctoring/component/eligibilityEditor'
], function($, _, eligibilityEditor){
    'use strict';
    
    /**
     * Only basic test for now. The tree embedded tree component is not easily testatable...
     */
    
    QUnit.test('render (creation mode)', function(assert){
        var $container = $('#fixture-1');
        var eligibilities = [
            {
                "delivery" : "http:\/\/tao.local\/mytao.rdf#i14496515319645147",
                "testTakers" : ['testTakerA', 'testTakerB', 'testTakerC']
            },
            {
                "delivery" : "http:\/\/tao.local\/mytao.rdf#i1449651502115597",
                "testTakers" : ['testTakerA']
            }
        ];
        var editor = eligibilityEditor.init($container, eligibilities);
        var $editorContainer = $container.children('.eligibility-editor');
        assert.equal($editorContainer.length, 1, 'eligibility editor dom ok');
        assert.ok($editorContainer.hasClass('modal'), 'eligibility in modal window');
        assert.equal($editorContainer.find('.eligible-delivery').length, 1, 'eligibility editor dom ok');
        assert.ok($editorContainer.find('.eligible-delivery').is(':visible'), 'delivery selector visible');
        assert.equal($editorContainer.find('.eligible-testTaker').length, 1, 'eligibility editor dom ok');
        assert.ok($editorContainer.find('.eligible-testTaker').is(':visible'), 'test taker selector visible');
    });

    QUnit.test('render (edit mode)', function(assert){

        var $container = $('#fixture-1');
        var deliveryA = {"uri" : "http:\/\/tao.local\/mytao.rdf#i1449651502115597", "label" : "Delivery A"};
        var eligibilities = [
            {
                "delivery" : "http:\/\/tao.local\/mytao.rdf#i14496515319645147",
                "testTakers" : ['testTakerA', 'testTakerB', 'testTakerC']
            },
            {
                "delivery" : "http:\/\/tao.local\/mytao.rdf#i1449651502115597",
                "testTakers" : ['testTakerA']
            }
        ];
        var editor = eligibilityEditor.init($container, eligibilities, deliveryA);
        var $editorContainer = $container.children('.eligibility-editor');
        assert.equal($editorContainer.length, 1, 'eligibility editor dom ok');
        assert.ok($editorContainer.hasClass('modal'), 'eligibility in modal window');
        assert.equal($editorContainer.find('.eligible-delivery').length, 1, 'eligibility editor dom ok');
        assert.ok(!$editorContainer.find('.eligible-delivery').is(':visible'), 'delivery selector not visible');
        assert.equal($editorContainer.find('.eligible-testTaker').length, 1, 'eligibility editor dom ok');
        assert.ok($editorContainer.find('.eligible-testTaker').is(':visible'), 'test taker selector visible');
    });
});
