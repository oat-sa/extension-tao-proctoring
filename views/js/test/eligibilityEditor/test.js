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

    QUnit.test('render', function(assert){

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
                "testTakers" : ['testTakerA', 'testTakerB', 'testTakerC']
            },
            {
                "delivery" : "http:\/\/tao.local\/mytao.rdf#i1449651502115597",
                "testTakers" : ['testTakerA']
            }
        ];
        var editor = eligibilityEditor.init($container, eligibilities, deliveries);
        var $editorContainer = $container.children('.eligibility-editor');
        assert.equal($editorContainer.length, 1, 'eligibility editor dom ok');
        assert.ok($editorContainer.hasClass('modal'), 'eligibility in modal window');
        assert.equal($editorContainer.find('.eligibility-delivery-selector').length, 1, 'eligibility editor dom ok');
        var $select = $editorContainer.find('.eligibility-delivery-selector').find('select');
        assert.equal($select.length, 1, 'delivery selector found');
        assert.equal($select.children('option').length, 4, 'options found (1 placeholdre + 3 delivery choices');

        assert.equal($editorContainer.children('.eligible-testTaker-tree-container').length, 1, 'tree container found');
        assert.equal($editorContainer.children('.eligible-testTaker-tree-container').find('.tree.tree-checkbox').length, 1, 'tree found');

    });

    QUnit.asyncTest('select delivery', function(assert){

        var $container = $('#fixture-1');
        var selectedDelivery = "http:\/\/tao.local\/mytao.rdf#i14496515079910121a";
        var deliveries = [
            {"uri" : "http:\/\/tao.local\/mytao.rdf#i14496515319645147", "label" : "Delivery A"},
            {"uri" : "http:\/\/tao.local\/mytao.rdf#i1449651502115597", "label" : "Delivery B"},
            {"uri" : selectedDelivery, "label" : "Delivery C"},
            {"uri" : "http:\/\/tao.local\/mytao.rdf#i14496515079910121b", "label" : "Delivery D"},
            {"uri" : "http:\/\/tao.local\/mytao.rdf#i14496515079910121c", "label" : "Delivery E"}
        ];
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

        var editor = eligibilityEditor.init($container, eligibilities, deliveries);
        editor.on('change', function(eligibility){

            assert.ok(_.isPlainObject(eligibility), 'eligibility is an object');
            assert.equal(eligibility.delivery, selectedDelivery, 'eligibility returns the selected delivery');

        }).on('ok', function(eligibility){
            assert.ok(_.isPlainObject(eligibility), 'eligibility is an object');
            assert.equal(eligibility.delivery, selectedDelivery, 'eligibility returns the selected delivery');
            QUnit.start();
        });

        var $editorContainer = $container.children('.eligibility-editor');
        var $select = $editorContainer.find('.eligibility-delivery-selector').find('select');
        $select.select2('val', selectedDelivery, true);

        var $ok = $editorContainer.children('.actions').children('.done');
        assert.equal($ok.length, 1, 'ok button found');
        $ok.click();

    });
});
