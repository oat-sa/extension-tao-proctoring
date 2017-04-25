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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA ;
 */
/**
 * @author Jean-Sébastien Conan <jean-sebastien@taotesting.com>
 */
define([
    'lodash',
    'i18n',
    'ui/dialog/confirm',
    'ui/button'
], function (_, __, dialogConfirm, buttonFactory) {
    'use strict';

    /**
     * Manages an exit button. You must provide a callback to make an action when the user accept the exit message.
     * The button is not rendered by default.
     * @param {Function} exitAction
     * @param {Object} [config]
     */
    function exitButtonFactory(exitAction, config) {
        return buttonFactory(_.merge({
            id: 'exit',
            type: 'info',
            label: __('Exit'),
            cls: 'exit-button'
        }, config)).on('click', function () {
            dialogConfirm(__('You are about to leave this page. Continue?'), exitAction);
        });
    }

    return exitButtonFactory;
});
