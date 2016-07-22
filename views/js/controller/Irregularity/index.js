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

define([
    'lodash',
    'jquery',
    'i18n',
    'ui/feedback',
    'layout/loading-bar',
    'helpers',
    'jquery.fileDownload'
], function (_, $, __, feedback, loadingBar, helpers){
    'use strict';

    /**
     * Controls the ProctorDelivery index page
     *
     * @type {Object}
     */
    var IrregularityCtlr = {
        /**
         * Entry point of the page
         */
        start : function start(){
            var $form = $('#export-form');

            $form.on('submit', function (e) {

                e.stopPropagation();
                e.preventDefault();
                loadingBar.start();
                loadingBar.stop();
                var self = $(this),
                    uri = $('[name="uri"]', self).val(),
                    from = $('[name="from"]', self).val(),
                    to = $('[name="to"]', self).val(),
                    params = {'uri': uri, 'from': from, 'to': to},
                    exportUrl = helpers._url('exportIrregularities', 'Irregularity', 'taoProctoring', params);

                console.log(exportUrl);
                $.fileDownload(exportUrl, {
                    successCallback : function () {
                        loadingBar.stop();
                    },
                    failCallback : function (jqXHR) {
                        loadingBar.stop();
                        var response = $.parseJSON($(jqXHR).text());
                        if (response) {
                            feedback().error(new Error(response.message));
                        }
                    }
                });

            });
        }
    };

    return IrregularityCtlr;
});
