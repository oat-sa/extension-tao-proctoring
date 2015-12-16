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
 * @author Sam <sam@taotesting.com>
 */
define([
    'lodash',
    'jquery',
    'i18n',
    'helpers',
    'layout/loading-bar',
    'util/encode',
    'ui/feedback',
    'ui/bulkActionPopup',
    'taoProctoring/component/breadcrumbs',
    'taoProctoring/component/proctorForm'
], function (_, $, __, helpers, loadingBar, encode, feedback, bulkActionPopup, breadcrumbsFactory, proctorForm) {
    'use strict';
    
    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.proctorManager-index';
    
    //service urls:
    var proctorsDataUrl = helpers._url('proctorAuthorizations', 'ProctorManager', 'taoProctoring');
    var authorizeUrl = helpers._url('authorize', 'ProctorManager', 'taoProctoring');
    var unauthorizeUrl = helpers._url('unauthorize', 'ProctorManager', 'taoProctoring');
            
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
            var testCenters = $container.data('list');
            var crumbs = $container.data('breadcrumbs');
            var bc = breadcrumbsFactory($container, crumbs);
            
            loadingBar.stop();
            
            //call me to initialier the form
            proctorForm.init($container.find('.proctor-panel'), {testCenters : []});
        }
    };

    return taoProctoringCtlr;
});
