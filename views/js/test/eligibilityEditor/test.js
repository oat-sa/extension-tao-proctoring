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

    QUnit.asyncTest('add', function(assert){
        QUnit.expect(7);
        var $container = $('#fixture-1');

        var editor = eligibilityEditorFactory();
        editor
            .on('open', function(){

                var $editorContainer = $container.children('.eligibility-editor');
                assert.equal($editorContainer.length, 1, 'eligibility editor dom ok');
                assert.ok($editorContainer.hasClass('modal'), 'eligibility in modal window');
                assert.equal($editorContainer.find('.eligible-delivery').length, 1, 'eligibility editor dom ok');
                assert.ok($editorContainer.find('.eligible-delivery').is(':visible'), 'delivery selector visible');
                assert.equal($editorContainer.find('.eligible-testTaker').length, 1, 'eligibility editor dom ok');
                assert.ok($editorContainer.find('.eligible-testTaker').is(':visible'), 'test taker selector visible');

                assert.ok(true, 'Button clicked');
                QUnit.start();
            });
        editor.add($container);
    });

});
