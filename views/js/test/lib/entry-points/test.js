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
    'taoProctoring/lib/entry-points'
], function($, _, entryPoints) {
    'use strict';

    QUnit.module('entryPoints');


    QUnit.test('module', 3, function(assert) {
        assert.equal(typeof entryPoints, 'function', "The entryPoints module exposes a function");
        assert.equal(typeof entryPoints(), 'object', "The entryPoints factory produces an object");
        assert.notStrictEqual(entryPoints(), entryPoints(), "The entryPoints factory provides a different object on each call");
    });


    var testReviewApi = [
        { name : 'init', title : 'init' },
        { name : 'destroy', title : 'destroy' },
        { name : 'render', title : 'render' },
        { name : 'update', title : 'update' },
        { name : 'show', title : 'show' },
        { name : 'hide', title : 'hide' },
        { name : 'enable', title : 'enable' },
        { name : 'disable', title : 'disable' },
        { name : 'is', title : 'is' },
        { name : 'setState', title : 'setState' },
        { name : 'setLoading', title : 'setLoading' },
        { name : 'setTitle', title : 'setTitle' },
        { name : 'setTextNumber', title : 'setTextNumber' },
        { name : 'setTextEmpty', title : 'setTextEmpty' },
        { name : 'setTextLoading', title : 'setTextLoading' },
        { name : 'getDom', title : 'getDom' }
    ];

    QUnit
        .cases(testReviewApi)
        .test('instance API ', function(data, assert) {
            var instance = entryPoints();
            assert.equal(typeof instance[data.name], 'function', 'The entryPoints instance exposes a "' + data.title + '" function');
            instance.destroy();
        });


    QUnit.test('init', function(assert) {
        var config = {
            nothing: undefined,
            dummy: null,
            title: 'My Title',
            textEmpty: 'Nothing to list',
            textNumber: 'Number',
            textLoading: 'Please wait'
        };
        var instance = entryPoints(config);

        assert.notEqual(instance.config, config, 'The entryPoints instance must duplicate the config set');
        assert.equal(instance.hasOwnProperty('nothing'), false, 'The entryPoints instance must not accept undefined config properties');
        assert.equal(instance.hasOwnProperty('dummy'), false, 'The entryPoints instance must not accept null config properties');
        assert.equal(instance.config.title, config.title, 'The entryPoints instance must catch the title config');
        assert.equal(instance.config.textNumber, config.textNumber, 'The entryPoints instance must catch the textNumber config');
        assert.equal(instance.config.textEmpty, config.textEmpty, 'The entryPoints instance must catch the textNumber config');
        assert.equal(instance.config.textLoading, config.textLoading, 'The entryPoints instance must catch the textNumber config');
        assert.equal(instance.is('rendered'), false, 'The entryPoints instance must not be rendered');

        instance.destroy();
    });


    QUnit.test('render', function(assert) {
        var $dummy = $('<div class="dummy" />');
        var $container = $('#fixture-1').append($dummy);
        var config = {
            title: 'My Title',
            textEmpty: 'Nothing to list',
            textNumber: 'Number',
            textLoading: 'Please wait',
            renderTo: $container,
            replace: true,
            entries: [{
                url: 'http://localhost/test',
                label: 'Test',
                text: 'test',
                content: '<b>TEST</b>'
            }]
        };
        var instance;

        assert.equal($container.children().length, 1, 'The container already contains an element');
        assert.equal($container.children().get(0), $dummy.get(0), 'The container contains the dummy element');
        assert.equal($container.find('.dummy').length, 1, 'The container contains an element of the class dummy');

        instance = entryPoints(config);

        assert.equal($container.find('.dummy').length, 0, 'The container does not contain an element of the class dummy');
        assert.equal(instance.is('rendered'), true, 'The entryPoints instance must be rendered');
        assert.equal(typeof instance.getDom(), 'object', 'The entryPoints instance returns the rendered content as an object');
        assert.equal(instance.getDom().length, 1, 'The entryPoints instance returns the rendered content');
        assert.equal(instance.getDom().parent().get(0), $container.get(0), 'The entryPoints instance is rendered inside the right container');

        assert.equal(instance.getDom().find('h1').text(), config.title, 'The entryPoints instance has rendered a title with the right content');
        assert.equal(instance.getDom().find('.empty-list').text(), config.textEmpty, 'The entryPoints instance has rendered a message to display when the list is empty, and set the right content');
        assert.equal(instance.getDom().find('.available-list .label').text(), config.textNumber, 'The entryPoints instance has rendered a message to show the number of entries, and set the right content');
        assert.equal(instance.getDom().find('.loading').text(), config.textLoading + '...', 'The entryPoints instance has rendered a message to show when the component is in loading state, and set the right content');

        assert.equal(instance.getDom().find('.list .entry').length, config.entries.length, 'The entryPoints instance has rendered the list of entries');
        assert.equal(instance.getDom().find('.list .entry').first().find('a').attr('href'), config.entries[0].url, 'The entryPoints instance has set the right url in the first entry');
        assert.equal(instance.getDom().find('.list .entry').first().find('h3').text(), config.entries[0].label, 'The entryPoints instance has set the right label in the first entry');
        assert.equal(instance.getDom().find('.list .entry').first().find('.content').html(), config.entries[0].content, 'The entryPoints instance has set the content text in the first entry');
        assert.equal(instance.getDom().find('.list .entry').first().find('.text-link').text(), config.entries[0].text, 'The entryPoints instance has set the right bottom text in the first entry');

        instance.destroy();

        assert.equal($container.children().length, 0, 'The container is now empty');
        assert.equal(instance.getDom(), null, 'The entryPoints instance has removed its rendered content');
    });


    QUnit.test('update', function(assert) {
        var instance = entryPoints();
        var $component = instance.render();
        var entries = [{
            url: 'http://localhost/test',
            label: 'Test',
            text: 'test',
            content: '<b>TEST</b>'
        }];

        assert.equal(instance.is('rendered'), true, 'The entryPoints instance must be rendered');
        assert.equal($component.length, 1, 'The entryPoints instance returns the rendered content');

        assert.equal(instance.getDom().find('.list .entry').length, 0, 'The entryPoints instance has rendered an empty list');
        assert.equal(instance.getDom().hasClass('empty'), true, 'The entryPoints instance displays a message telling the list is empty');
        assert.equal(instance.getDom().hasClass('loaded'), false, 'The entryPoints instance does not display the number of entries');
        assert.equal(instance.getDom().hasClass('loading'), false, 'The entryPoints instance is not loading');

        assert.equal(instance.is('empty'), true, 'The entryPoints instance has the state empty');
        assert.equal(instance.is('loaded'), false, 'The entryPoints instance does not have the state loaded');
        assert.equal(instance.is('loading'), false, 'The entryPoints instance does not have the state loading');

        instance.update(entries);

        assert.equal(instance.getDom().find('.list .entry').length, entries.length, 'The entryPoints instance has rendered the list of entries');
        assert.equal(instance.getDom().find('.list .entry').first().find('a').attr('href'), entries[0].url, 'The entryPoints instance has set the right url in the first entry');
        assert.equal(instance.getDom().find('.list .entry').first().find('h3').text(), entries[0].label, 'The entryPoints instance has set the right label in the first entry');
        assert.equal(instance.getDom().find('.list .entry').first().find('.content').html(), entries[0].content, 'The entryPoints instance has set the content text in the first entry');
        assert.equal(instance.getDom().find('.list .entry').first().find('.text-link').text(), entries[0].text, 'The entryPoints instance has set the right bottom text in the first entry');

        assert.equal(instance.getDom().hasClass('empty'), false, 'The entryPoints instance does not display a message telling the list is empty');
        assert.equal(instance.getDom().hasClass('loaded'), true, 'The entryPoints instance displays the number of entries');
        assert.equal(instance.getDom().hasClass('loading'), false, 'The entryPoints instance is not loading');
        assert.equal(instance.getDom().find('.available-list .count').text(), entries.length, 'The entryPoints instance dispkays the right number of entries');

        assert.equal(instance.is('empty'), false, 'The entryPoints instance does not have the state empty');
        assert.equal(instance.is('loaded'), true, 'The entryPoints instance has the state loaded');
        assert.equal(instance.is('loading'), false, 'The entryPoints instance does not have the state loading');

        instance.destroy();
    });


    QUnit.test('show/hide', function(assert) {
        var instance = entryPoints();
        var $component = instance.render();

        assert.equal(instance.is('rendered'), true, 'The entryPoints instance must be rendered');
        assert.equal($component.length, 1, 'The entryPoints instance returns the rendered content');

        assert.equal(instance.is('hidden'), false, 'The entryPoints instance is visible');
        assert.equal(instance.getDom().hasClass('hidden'), false, 'The entryPoints instance does not have the hidden class');

        instance.hide();

        assert.equal(instance.is('hidden'), true, 'The entryPoints instance is hidden');
        assert.equal(instance.getDom().hasClass('hidden'), true, 'The entryPoints instance has the hidden class');

        instance.show();

        assert.equal(instance.is('hidden'), false, 'The entryPoints instance is visible');
        assert.equal(instance.getDom().hasClass('hidden'), false, 'The entryPoints instance does not have the hidden class');

        instance.destroy();
    });


    QUnit.test('enable/disable', function(assert) {
        var instance = entryPoints();
        var $component = instance.render();

        assert.equal(instance.is('rendered'), true, 'The entryPoints instance must be rendered');
        assert.equal($component.length, 1, 'The entryPoints instance returns the rendered content');

        assert.equal(instance.is('disabled'), false, 'The entryPoints instance is enabled');
        assert.equal(instance.getDom().hasClass('disabled'), false, 'The entryPoints instance does not have the disabled class');

        instance.disable();

        assert.equal(instance.is('disabled'), true, 'The entryPoints instance is disabled');
        assert.equal(instance.getDom().hasClass('disabled'), true, 'The entryPoints instance has the disabled class');

        instance.enable();

        assert.equal(instance.is('disabled'), false, 'The entryPoints instance is enabled');
        assert.equal(instance.getDom().hasClass('disabled'), false, 'The entryPoints instance does not have the disabled class');

        instance.destroy();
    });


    QUnit.test('state', function(assert) {
        var instance = entryPoints();
        var $component = instance.render();

        assert.equal(instance.is('rendered'), true, 'The entryPoints instance must be rendered');
        assert.equal($component.length, 1, 'The entryPoints instance returns the rendered content');

        // loading
        assert.equal(instance.is('loading'), false, 'The entryPoints instance is not loading');
        assert.equal(instance.getDom().hasClass('loading'), false, 'The entryPoints instance does not have the loading class');

        instance.setLoading(true);

        assert.equal(instance.is('loading'), true, 'The entryPoints instance is loading');
        assert.equal(instance.getDom().hasClass('loading'), true, 'The entryPoints instance has the loading class');

        instance.setLoading(false);

        assert.equal(instance.is('loading'), false, 'The entryPoints instance is not loading');
        assert.equal(instance.getDom().hasClass('loading'), false, 'The entryPoints instance does not have the loading class');

        // custom state
        assert.equal(instance.is('customState'), false, 'The entryPoints instance does not have the customState state');
        assert.equal(instance.getDom().hasClass('customState'), false, 'The entryPoints instance does not have the customState class');

        instance.setState('customState', true);

        assert.equal(instance.is('customState'), true, 'The entryPoints instance has the customState state');
        assert.equal(instance.getDom().hasClass('customState'), true, 'The entryPoints instance has the customState class');

        instance.setState('customState', false);

        assert.equal(instance.is('customState'), false, 'The entryPoints instance does not have the customState state');
        assert.equal(instance.getDom().hasClass('customState'), false, 'The entryPoints instance does not have the customState class');

        instance.destroy();
    });


    QUnit.test('setters', function(assert) {
        var config = {
            title: 'My Title',
            textEmpty: 'Nothing to list',
            textNumber: 'Number',
            textLoading: 'Please wait'
        };
        var instance = entryPoints();
        var $component = instance.render();

        assert.equal(instance.is('rendered'), true, 'The entryPoints instance must be rendered');
        assert.equal($component.length, 1, 'The entryPoints instance returns the rendered content');

        assert.notEqual(instance.config.title, config.title, 'The entryPoints instance has its own title');
        assert.notEqual(instance.config.textEmpty, config.textEmpty, 'The entryPoints instance has its own empty list message');
        assert.notEqual(instance.config.textNumber, config.textNumber, 'The entryPoints instance has its own number label');
        assert.notEqual(instance.config.textLoading, config.textLoading, 'The entryPoints instance has its own loading message');

        assert.notEqual(instance.getDom().find('h1').text(), config.title, 'The entryPoints instance has rendered a title with its own content');
        assert.notEqual(instance.getDom().find('.empty-list').text(), config.textEmpty, 'The entryPoints instance has rendered a message to display when the list is empty, and set its own content');
        assert.notEqual(instance.getDom().find('.available-list .label').text(), config.textNumber, 'The entryPoints instance has rendered a message to show the number of entries, and set its own content');
        assert.notEqual(instance.getDom().find('.loading').text(), config.textLoading + '...', 'The entryPoints instance has rendered a message to show when the component is in loading state, and set its own content');

        instance.setTitle(config.title);
        assert.equal(instance.config.title, config.title, 'The entryPoints instance has taken the right title');
        assert.equal(instance.getDom().find('h1').text(), config.title, 'The entryPoints instance has updated the title with the right content');

        instance.setTextEmpty(config.textEmpty);
        assert.equal(instance.config.textEmpty, config.textEmpty, 'The entryPoints instance has the right empty list message');
        assert.equal(instance.getDom().find('.empty-list').text(), config.textEmpty, 'The entryPoints instance has updated the message to display when the list is empty, and set the right content');

        instance.setTextNumber(config.textNumber);
        assert.equal(instance.config.textNumber, config.textNumber, 'The entryPoints instance has the right number label');
        assert.equal(instance.getDom().find('.available-list .label').text(), config.textNumber, 'The entryPoints instance has updated the number label, and set the right content');

        instance.setTextLoading(config.textLoading);
        assert.equal(instance.config.textLoading, config.textLoading, 'The entryPoints instance has the right loading label');
        assert.equal(instance.getDom().find('.loading').text(), config.textLoading + '...', 'The entryPoints instance has updated the loading label, and set the right content');

        instance.destroy();

        assert.equal(instance.is('rendered'), false, 'The entryPoints instance must be destroyed');

        $component = instance.render();

        assert.equal(instance.is('rendered'), true, 'The entryPoints instance must be rendered');
        assert.equal($component.length, 1, 'The entryPoints instance returns the rendered content');

        assert.equal(instance.config.title, config.title, 'The entryPoints instance has its own title');
        assert.equal(instance.config.textEmpty, config.textEmpty, 'The entryPoints instance has its own empty list message');
        assert.equal(instance.config.textNumber, config.textNumber, 'The entryPoints instance has its own number label');
        assert.equal(instance.config.textLoading, config.textLoading, 'The entryPoints instance has its own loading message');

        assert.equal(instance.getDom().find('h1').text(), config.title, 'The entryPoints instance has rendered a title with its own content');
        assert.equal(instance.getDom().find('.empty-list').text(), config.textEmpty, 'The entryPoints instance has rendered a message to display when the list is empty, and set its own content');
        assert.equal(instance.getDom().find('.available-list .label').text(), config.textNumber, 'The entryPoints instance has rendered a message to show the number of entries, and set its own content');
        assert.equal(instance.getDom().find('.loading').text(), config.textLoading + '...', 'The entryPoints instance has rendered a message to show when the component is in loading state, and set its own content');
    });
});
