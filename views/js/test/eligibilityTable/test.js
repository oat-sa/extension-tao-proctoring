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
    'taoProctoring/component/eligibilityTable'
], function($, _, eligibilityTableFactory){
    'use strict';

    QUnit.module('API');

    QUnit.test('factory', function(assert) {
        QUnit.expect(5);

        var testCenterId = 'area51';
        var eligibilityTable;
        assert.equal(typeof eligibilityTableFactory, 'function', "The module exposes a function");

        assert.throws(function(){
            eligibilityTableFactory();
        }, TypeError, 'The component needs to be configured');

        assert.throws(function(){
            eligibilityTableFactory('');
        }, TypeError, 'The component needs a valid test center');

        eligibilityTable = eligibilityTableFactory(testCenterId);

        assert.equal(typeof eligibilityTable, 'object', 'The factory creates an object');
        assert.notDeepEqual(eligibilityTable, eligibilityTableFactory(testCenterId), 'The factory creates new objects');
    });

    var pluginApi = [
        { name : 'init', title : 'init' },
        { name : 'render', title : 'render' },
        { name : 'destroy', title : 'destroy' },
        { name : 'on', title : 'on' },
        { name : 'off', title : 'off' },
        { name : 'trigger', title : 'trigger' },
    ];

    QUnit
        .cases(pluginApi)
        .test('component method ', function(data, assert) {
            QUnit.expect(1);

            var testCenterId = 'area51';
            var eligibilityTable = eligibilityTableFactory(testCenterId);

            assert.equal(typeof eligibilityTable[data.name], 'function', 'The component exposes a "' + data.name + '" function');
        });

    QUnit.module('Behavior');

    QUnit.asyncTest('create table', function (assert){
        QUnit.expect(4);

        var testCenterId = 'area51';
        var $fixtureContainer = $('#qunit-fixture');
        var eligibilityTable = eligibilityTableFactory(testCenterId);

        eligibilityTable
            .on('render', function () {
                var $component = $('.component', $fixtureContainer);

                assert.equal($component.length, 1, 'The component has been appended to the container');
                assert.ok($component.hasClass('rendered'), 'The component has the rendered class');

            })
            .on('loaded', function(){

                var $component = $('.component', $fixtureContainer);
                assert.equal($('.datatable-container > table', $component).length, 1, 'The table is also added');
                assert.equal($('.datatable-container > table tbody tr', $component).length, 2, 'The table contains 2 rows');

                QUnit.start();
            })
            .init({ dataUrl : '/taoProctoring/views/js/test/eligibilityTable/data.json' })
            .render($fixtureContainer);
    });

    QUnit.asyncTest('add action', function (assert){
        QUnit.expect(4);

        var testCenterId = 'area51';
        var $fixtureContainer = $('#qunit-fixture');
        var eligibilityTable = eligibilityTableFactory(testCenterId);

        eligibilityTable
            .on('loaded', function () {
                var $add;
<<<<<<< HEAD
                var $component = $('.component', $fixtureContainer);
=======
                var $component = $('.component:eq(0)', $fixtureContainer);
>>>>>>> f827d8d4fa1d2ab8b4af982dae6e047849e94f06

                assert.equal($component.length, 1, 'The component has been appended to the container');
                assert.equal($('.action-bar', $component).length, 1, 'The action bar exists');

                $add = $('.tool-add', $component);
                assert.equal($add.length, 1, 'The add action button exists');

                $add.trigger('click');
            })
            .on('add', function(data){
                 assert.equal(data.length, 2, 'The table data contains 2 entries');

                QUnit.start();
            })
            .init({ dataUrl : '/taoProctoring/views/js/test/eligibilityTable/data.json' })
            .render($fixtureContainer);
    });

    QUnit.asyncTest('remove action', function (assert){
        QUnit.expect(5);

        var testCenterId = 'area51';
        var $fixtureContainer = $('#qunit-fixture');
        var eligibilityTable = eligibilityTableFactory(testCenterId);

        eligibilityTable
            .on('loaded', function () {
                var $rm;
<<<<<<< HEAD
                var $component = $('.component', $fixtureContainer);
=======
                var $component = $('.component:eq(0)', $fixtureContainer);
>>>>>>> f827d8d4fa1d2ab8b4af982dae6e047849e94f06

                assert.equal($component.length, 1, 'The component has been appended to the container');

                $rm = $('.actions [data-action=remove]', $component);
                assert.equal($rm.length, 2, 'There is 2 remove button, one by row');

                $rm.first().trigger('click');
            })
            .on('remove', function(id, data){
<<<<<<< HEAD
                 var $component = $('.component', $fixtureContainer);
=======
                 var $component = $('.component:eq(0)', $fixtureContainer);
>>>>>>> f827d8d4fa1d2ab8b4af982dae6e047849e94f06
                 assert.equal(typeof id, 'string', 'The id is given');
                 assert.equal(id, $('.datatable-container tbody tr', $component).first().data('item-identifier'), 'The given identifier matches the row');
                 assert.equal(data.length, 2, 'The table data contains 2 entries');

                QUnit.start();
            })
            .init({ dataUrl : '/taoProctoring/views/js/test/eligibilityTable/data.json' })
            .render($fixtureContainer);
    });
});
