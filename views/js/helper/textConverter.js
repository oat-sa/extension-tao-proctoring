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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA ;
 */
/**
 * @author Jean-Sébastien Conan <jean-sebastien.conan@vesperiagroup.com>
 */
define([
    'jquery',
    'lodash',
    'core/format',
    'core/promise',
    'util/url'
], function ($, _, format, Promise, url) {
    'use strict';

    /**
     * The loaded labels
     * @type {Object}
     */
    var labels = null;

    /**
     * The wrapper that resolve the labels from the loaded list
     * @type {Object}
     */
    var proctoringLabels = {
        /**
         * Proctoring translation method.
         *
         * @param {String} message should be the string in the default language (usually english) used as the key in the gettext translations
         * @returns {String} translated message
         */
        get: function get(message) {
            if (labels[message] == undefined) {
                console.log(message);
            }
            var localized = !labels[message] ? message : labels[message];

            if (arguments.length > 1) {
                arguments[0] = localized;
                localized = format.apply(null, arguments);
            }

            return localized;
        }
    };

    /**
     * The promise that is pending to load the labels
     * @type {Promise}
     */
    var pendingPromise;

    /**
     * Gets the API that access to the proctoring labels
     * @returns {Promise}
     */
    function proctoringLabelsLoader() {
        if (!pendingPromise) {
            pendingPromise = new Promise(function (resolve, reject) {
                $.ajax({
                    url: url.route('getAll', 'TextConverter', 'taoProctoring'),
                    type: 'POST',
                    dataType: 'json'
                })
                    .done(function (data) {
                        labels = data.translations;
                        resolve(proctoringLabels);
                    })
                    .fail(reject);
            });
        }

        return pendingPromise;
    }

    return proctoringLabelsLoader;
});
