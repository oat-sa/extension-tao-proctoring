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
 * Copyright (c) 2016  (original work) Open Assessment Technologies SA;
 *
 */

define(['jquery', '/taoProctoring/views/js/component/activityMonitoring/activityGraph.js'], function ($, activityGraph) {
    'use strict';

    var c3options = {
        autoRefresh : 0,
        autoRefreshBar : true,
        graphConfig : {
            bindto : '.js-graph',
            data: {
                x: 'data1',
                columns: [
                    ['data1', 30, 200, 100, 400, 150, 250]
                ]
            }
        }
    };
    var graph;
    var $container;

    QUnit.module('API');

    QUnit.test('factory', function (assert) {
        QUnit.expect(3);

        assert.ok(typeof activityGraph === 'function', 'the module exposes a function');
        assert.ok(typeof activityGraph(c3options) === 'object', 'the factory creates an object');
        assert.notEqual(activityGraph(c3options), activityGraph(c3options), 'the factory creates new objects');
    });

    QUnit.test('component', function (assert) {
        QUnit.expect(2);

        graph = activityGraph(c3options);

        assert.ok(typeof graph.render === 'function', 'the component has a render method');
        assert.ok(typeof graph.destroy === 'function', 'the component has a destroy method');
    });

    QUnit.test('eventifier', function (assert) {
        QUnit.expect(3);

        graph = activityGraph(c3options);

        assert.ok(typeof graph.on === 'function', 'the component has a on method');
        assert.ok(typeof graph.off === 'function', 'the component has a off method');
        assert.ok(typeof graph.trigger === 'function', 'the component has a trigger method');
    });

    QUnit.module('Component');

    QUnit.test('render', function (assert) {
        QUnit.expect(2);

        $container = $('#qunit-fixture');
        assert.equal($container.length, 1, 'The container exists');

        activityGraph(c3options).on('render', function() {
            assert.equal($container.find('svg').length, 1, 'periodStart have been created');
        }).render();
    });
});
