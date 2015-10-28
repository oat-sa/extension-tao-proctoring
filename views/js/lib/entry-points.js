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
    'i18n',
    'taoProctoring/lib/component',
    'tpl!taoProctoring/tpl/entry-points-main',
    'tpl!taoProctoring/tpl/entry-points'
], function ($, _, __, component, mainTpl, entriesTpl) {
    'use strict';

    /**
     * Some default values
     * @type {Object}
     * @private
     */
    var _defaults = {
        title : false,
        textNumber : __('Available'),
        textEmpty : __('There is nothing to list!'),
        textLoading : __('Loading')
    };

    /**
     * Defines an entries manager
     * @type {Object}
     */
    var entryPoints = {
        /**
         * Uninstalls the component
         */
        tearDown : function tearDown() {
            this.controls = null;
        },

        /**
         * Renders the component
         */
        postRender : function postRender() {
            this.controls = {
                $title : this.$component.find('h1'),
                $textEmpty : this.$component.find('.empty-list'),
                $textAvailable : this.$component.find('.available-list'),
                $textLoading : this.$component.find('.loading span'),
                $numberLabel : this.$component.find('.available-list .label'),
                $numberValue : this.$component.find('.available-list .count'),
                $list : this.$component.find('.list')
            };

            if (this.config.entries) {
                this.update(this.config.entries);
            } else {
                this.setState('empty', true);
                this.setState('loaded', false);
            }
        },

        /**
         * Updates the list of entries
         * @param {Array} entries
         * @returns {entryPoints}
         */
        update : function update(entries) {
            var $list = this.controls && this.controls.$list;
            var $numberValue = this.controls && this.controls.$numberValue;

            this.setLoading(true);
            if ($list) {
                $list.empty();

                if (entries && entries.length) {
                    $list.append(entriesTpl({entries : entries}));
                    $numberValue && $numberValue.text(entries.length);
                    this.setState('empty', false);
                    this.setState('loaded', true);
                } else {
                    this.setState('empty', true);
                    this.setState('loaded', false);
                }
            }
            this.setLoading(false);

            return this;
        },

        /**
         * Sets the loading state
         * @param {Boolean} flag
         * @returns {entryPoints}
         */
        setLoading : function setLoading(flag) {
            if (flag) {
                this.setState('loaded', false);
            }
            return this.setState('loading', flag);
        },

        /**
         * Sets the title of the list.
         * @param {String|Boolean} title - The text to set. If the value is false no title is displayed
         * @returns {entryPoints}
         */
        setTitle : function setTitle(title) {
            var $title = this.controls && this.controls.$title;
            this.config.title = title;
            if ($title) {
                if (false === title) {
                    $title.addClass('hidden');
                } else {
                    $title.html(title).removeClass('hidden');
                }
            }

            return this;
        },

        /**
         * Sets the label of the number of entries.
         * @param {String|Boolean} text - The text to set. If the value is false no label is displayed
         * @returns {entryPoints}
         */
        setTextNumber : function setTextNumber(text) {
            var $numberLabel = this.controls && this.controls.$numberLabel;
            var $textAvailable = this.controls && this.controls.$textAvailable;
            this.config.textNumber = text;
            if ($numberLabel) {
                if (false === text) {
                    $textAvailable && $textAvailable.addClass('hidden');
                } else {
                    $numberLabel.html(text).removeClass('hidden');
                }
            }

            return this;
        },

        /**
         * Sets the label displayed when there no entries available.
         * @param {String|Boolean} text - The text to set. If the value is false no label is displayed
         * @returns {entryPoints}
         */
        setTextEmpty : function setTextNumber(text) {
            var $textEmpty = this.controls && this.controls.$textEmpty;
            this.config.textEmpty = text;
            if ($textEmpty) {
                if (false === text) {
                    $textEmpty.addClass('hidden');
                } else {
                    $textEmpty.html(text).removeClass('hidden');
                }
            }

            return this;
        },

        /**
         * Sets the label displayed when the list is loading.
         * @param {String|Boolean} text - The text to set. If the value is false no label is displayed
         * @returns {entryPoints}
         */
        setTextLoading : function setTextLoading(text) {
            var $textLoading = this.controls && this.controls.$textLoading;
            this.config.textLoading = text;
            if ($textLoading) {
                if (false === text) {
                    $textLoading.addClass('hidden');
                } else {
                    $textLoading.html(text).removeClass('hidden');
                }
            }

            return this;
        }
    };

    /**
     * Builds an instance of the entries manager
     * @param {Object} config
     * @param {String|Boolean} [config.title] - Sets the title of the list. If the value is false no title is displayed (default: false)
     * @param {String|Boolean} [config.textNumber] - Sets the label of the number of entries. If the value is false no label is displayed (default: 'Available')
     * @param {String|Boolean} [config.textEmpty] - Sets the label displayed when there no entries available. If the value is false no label is displayed (default: 'There is nothing to list!')
     * @param {String|Boolean} [config.textLoading] - Sets the label displayed when the list is loading. If the value is false no label is displayed (default: 'Loading')
     * @param {Array} [config.entries] - The list of entries to display
     * @param {jQuery|HTMLElement|String} [config.renderTo] - An optional container in which renders the component
     * @param {Boolean} [config.replace] - When the component is appended to its container, clears the place before
     * @returns {entryPoints}
     */
    var entryPointsFactory = function entryPointsFactory(config) {
        var instance = component(entryPoints, mainTpl, _defaults);
        return instance.init(config);
    };

    return entryPointsFactory;
});
