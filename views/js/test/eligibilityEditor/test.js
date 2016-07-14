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
], function($, _, eligibilityEditorFactory){
    'use strict';


    QUnit.module('API');

    QUnit.test('factory', function(assert) {
        QUnit.expect(3);

        var testCenterId = 'area51';
        var eligibilityEditor;
        assert.equal(typeof eligibilityEditorFactory, 'function', "The module exposes a function");

        eligibilityEditor = eligibilityEditorFactory(testCenterId);

        assert.equal(typeof eligibilityEditor, 'object', 'The factory creates an object');
        assert.notDeepEqual(eligibilityEditor, eligibilityEditorFactory(testCenterId), 'The factory creates new objects');
    });

    var pluginApi = [
        { name : 'init', title : 'init' },
        { name : 'render', title : 'render' },
        { name : 'destroy', title : 'destroy' },
        { name : 'on', title : 'on' },
        { name : 'off', title : 'off' },
        { name : 'trigger', title : 'trigger' },
        { name : 'add', title : 'add' },
        { name : 'edit', title : 'edit' }
    ];

    QUnit
        .cases(pluginApi)
        .test('component method ', function(data, assert) {
            QUnit.expect(1);

            var testCenterId = 'area51';
            var eligibilityEditor = eligibilityEditorFactory(testCenterId);

            assert.equal(typeof eligibilityEditor[data.name], 'function', 'The component exposes a "' + data.name + '" function');
        });

    QUnit.module('Behavior');

    QUnit.asyncTest('Check DOM', function(assert){
        QUnit.expect(6);
        var $container = $('#fixture-1');

        eligibilityEditorFactory()
            .on('open', function(){

                var $editorContainer = $container.children('.eligibility-editor');
                assert.equal($editorContainer.length, 1, 'eligibility editor dom ok');
                assert.ok($editorContainer.hasClass('modal'), 'eligibility in modal window');
                assert.equal($editorContainer.find('.eligible-delivery').length, 1, 'eligibility editor dom ok');
                assert.ok($editorContainer.find('.eligible-delivery').is(':visible'), 'delivery selector visible');
                assert.equal($editorContainer.find('.eligible-testTaker').length, 1, 'eligibility editor dom ok');
                assert.ok($editorContainer.find('.eligible-testTaker').is(':visible'), 'test taker selector visible');

                QUnit.start();
            })
            .add($container, {
                dataUrl : '/taoProctoring/views/js/test/eligibilityEditor/data.json'
            });
    });

    QUnit.asyncTest('Add eligibility', function(assert){
        QUnit.expect(6);
        var $container = $('#fixture-1');

        eligibilityEditorFactory()
            .on('open', function(){

                var $editorContainer = $container.children('.eligibility-editor');
                assert.equal($editorContainer.length, 1, 'eligibility editor dom ok');
                _.delay(function(){

                    var $deliveries =  $('.eligible-delivery .node-instance', $editorContainer);
                    assert.equal($deliveries.length, 2, 'The delivery tree contains 2 nodes');

                    var $testTakers =  $('.eligible-testTaker .node-instance', $editorContainer);
                    assert.equal($testTakers.length, 2, 'The test taker tree contains 2 nodes');

                    $('a', $deliveries).trigger('click');
                    $('a', $testTakers).first().trigger('click');


                    _.delay(function(){
                        $('.done', $editorContainer).trigger('click.eligibility-editor');
                    }, 100);
                }, 500);
            })
            .on('ok', function(data){


                assert.equal(typeof data, 'object', 'We\'ve got the data');
                assert.equal(data.deliveries.length, 2, 'We\'ve got the 2 selected deliveries');
                assert.equal(data.testTakers.length, 1, 'We\'ve got the only selected test taker');
                QUnit.start();
            })
            .add($container, {
                dataUrl : '/taoProctoring/views/js/test/eligibilityEditor/data.json'
            });
    });

    QUnit.asyncTest('Cancel Add', function(assert){
        QUnit.expect(2);
        var $container = $('#fixture-1');

        eligibilityEditorFactory()
            .on('open', function(){

                var $editorContainer = $container.children('.eligibility-editor');
                assert.equal($editorContainer.length, 1, 'eligibility editor dom ok');
                _.delay(function(){
                    $('.cancel', $editorContainer).trigger('click.eligibility-editor');
                }, 500);
            })
            .on('cancel', function(){
                assert.ok(true, 'Cancelled triggered');
                QUnit.start();
            })
            .add($container, {
                dataUrl : '/taoProctoring/views/js/test/eligibilityEditor/data.json'
            });
    });

    QUnit.asyncTest('Edit eligibility', function(assert){
        QUnit.expect(5);
        var $container = $('#fixture-1');

        eligibilityEditorFactory()
            .on('open', function(){

                var $editorContainer = $container.children('.eligibility-editor');
                assert.equal($editorContainer.length, 1, 'eligibility editor dom ok');

                assert.equal($('.delivery-name', $editorContainer).text(), 'Foolivery', 'The delivery name is updated');

                _.delay(function(){

                    var $testTakers =  $('.eligible-testTaker .node-instance', $editorContainer);
                    assert.equal($testTakers.length, 2, 'The test taker tree contains 2 nodes');

                    $('a', $testTakers).trigger('click');

                    _.delay(function(){
                        $('.done', $editorContainer).trigger('click.eligibility-editor');
                    }, 100);
                }, 500);
            })
            .on('ok', function(data){

                assert.equal(typeof data, 'object', 'We\'ve got the data');
                assert.equal(data.testTakers.length, 2, 'We\'ve got the 2 selected test takers');
                QUnit.start();
            })
            .edit($container, 'Foolivery', [], {
                dataUrl : '/taoProctoring/views/js/test/eligibilityEditor/data.json'
            });
    });

    QUnit.asyncTest('Canel Edit', function(assert){
        QUnit.expect(2);
        var $container = $('#fixture-1');

        eligibilityEditorFactory()
            .on('open', function(){

                var $editorContainer = $container.children('.eligibility-editor');
                assert.equal($editorContainer.length, 1, 'eligibility editor dom ok');
                _.delay(function(){
                    $('.cancel', $editorContainer).trigger('click.eligibility-editor');
                }, 500);
            })
            .on('cancel', function(){
                assert.ok(true, 'Cancelled triggered');
                QUnit.start();
            })
            .edit($container, 'Foolivery', [], {
                dataUrl : '/taoProctoring/views/js/test/eligibilityEditor/data.json'
            });
    });
});
