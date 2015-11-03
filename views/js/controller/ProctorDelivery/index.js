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
    'i18n',
    'helpers',
    'layout/loading-bar',
    'ui/listbox',
    'ui/breadcrumbs'
], function ($, __, helpers, loadingBar, listBox, breadcrumbs) {
    'use strict';

    /**
     * The polling delay used to refresh the list
     * @type {Number}
     */
    var refreshPolling = 60 * 1000; // once per minute

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.deliveries-listing';

    // the page is always loading data when starting
    loadingBar.start();

    /**
     * Controls the ProctorDelivery index page
     *
     * @type {Object}
     */
    var taoProctoringCtlr = {
        /**
         * Entry point of the page
         */
        start : function start() {
            var $container = $(cssScope);
            var boxes = $container.data('list');
            var crumbs = $container.data('breadcrumbs');
            var list = listBox({
                title: __("My Deliveries"),
                textEmpty: __("No deliveries available"),
                textNumber: __("Available"),
                textLoading: __("Loading"),
                renderTo: $container.find('.content'),
                replace: true,
                list: boxes
            });
            var bc = breadcrumbs({
                breadcrumbs : crumbs,
                renderTo: $container.find('.header'),
                replace: true
            });
            var serviceUrl = helpers._url('index', 'TaoProctoring', 'taoProctoring');
            var pollTo = null;

            // update the index from a JSON array
            var update = function(boxes) {
                if (pollTo) {
                    clearTimeout(pollTo);
                    pollTo = null;
                }

                list.update(boxes);
                loadingBar.stop();

                // poll the server at regular interval to refresh the index
                if (refreshPolling) {
                    pollTo = setTimeout(refresh, refreshPolling);
                }
            };

            // refresh the index
            var refresh = function() {
                loadingBar.start();
                list.setLoading(true);

                $.ajax({
                    url: serviceUrl,
                    cache: false,
                    dataType : 'json',
                    type: 'GET'
                }).done(function(response) {
                    boxes = response && response.list;
                    update(boxes);
                });
            };

            if (!boxes) {
                refresh();
            } else {
                loadingBar.stop();
            }
        }
    };

    return taoProctoringCtlr;
});
