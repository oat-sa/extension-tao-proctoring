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
 * The eligibility provider, interface to communicate eligibility data.
 *
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
define([
    'jquery',
    'lodash',
    'helpers',
    'core/promise'
], function($, _, helpers, Promise){
    'use strict';

    /**
     * Performs an ajax request on the right controller
     * @param {String} action - the action name to call
     * @param {Object} parameters - the data to send
     * @returns {jQuery.Deferred} the ajax call
     */
    var request = function request(action, parameters){
        return $.ajax({
            url : helpers._url(action, 'TestCenterManager', 'taoProctoring'),
            data :  parameters,
            dataType : 'json',
            type : 'POST'
        });
    };


    /**
     * Creates an eligibilityProvider
     * @param {String} testCenterId - every call is contextualized for a test center
     * @returns {eligibilityProvider} the provider
     * @throws {TypeError} is the test center is missing
     */
    return function eligibilityProviderFactory(testCenterId) {
        if(_.isEmpty(testCenterId)){
            throw new TypeError('The eligibility provider needs to be initialized with a test center');
        }

        /**
         * The new provider
         * @typedef eligibilityProvider
         */
        return {

            /**
             * Add a new eligibilities
             *
             * @param {Object} eligibility - the eligibility to add
             * @param {String[]} eligibility.deliveries - the deliveries URIs
             * @param {String[]} eligibility.testTakers - the test takers URIs
             * @returns {Promise} that resolves once the eligibilities are added
             */
            addEligibilities : function addEligibilities (eligibilities){
                return new Promise(function (resolve, reject){
                    if( !_.isPlainObject(eligibilities) ) {
                        return reject(new TypeError('Invalid eligibility'));
                    }

                    request('addEligibilities', {
                        uri : testCenterId,
                        eligibility : eligibilities
                    })
                    .success(resolve)
                    .fail(function(xhr){
                        return reject(new Error(xhr.responseText));
                    });
                });
            },

            /**
             * Edit an eligibility
             *
             * @param {Object} eligibility - the eligibility to add
             * @param {String[]} eligibility.deliveries - the deliveries URIs
             * @param {String[]} eligibility.testTakers - the test takers URIs
             * @returns {Promise} that resolves once the eligibility is edited
             */
            editEligibility : function editEligibility(eligibility){

                return new Promise(function (resolve, reject){
                    if( !_.isPlainObject(eligibility) ) {
                        return reject(new TypeError('Invalid eligibility'));
                    }

                    request('editEligibilities', {
                        uri : testCenterId,
                        eligibility : eligibility
                    })
                    .success(resolve)
                    .fail(function(xhr){
                        return reject(new Error(xhr.responseText));
                    });
                });
            },

            /**
             * Remove an eligibility
             *
             * @param {Object} eligibility - the eligibility to add
             * @param {String[]} eligibility.deliveries - the deliveries URIs
             * @returns {Promise} that resolves once the eligibility is removed
             */
            removeEligibility : function removeEligibility(eligibility){

                return new Promise(function (resolve, reject){
                    if( !_.isPlainObject(eligibility) ) {
                        return reject(new TypeError('Invalid eligibility'));
                    }

                    request('removeEligibility', {
                        uri : testCenterId,
                        eligibility : eligibility
                    })
                    .success(resolve)
                    .fail(function(xhr){
                        return reject(new Error(xhr.responseText));
                    });
                });
            },

            /**
             * Shield an eligibility
             *
             * @param {String} eligibilityId - the eligibility URI
             * @returns {Promise} that resolves once the eligibility is shielded
             */
            shieldEligibility : function shieldEligibility(eligibilityId){
                return new Promise(function (resolve, reject){
                    if( !_.isString(eligibilityId) ) {
                        reject(new TypeError('Invalid eligibility'));
                    }

                    request('shieldEligibility', {
                        uri : testCenterId,
                        eligibility : eligibilityId
                    })
                    .success(resolve)
                    .fail(function(xhr){
                        return reject(new Error(xhr.responseText));
                    });
                });
            },

            /**
             * Unshield an eligibility
             *
             * @param {String} eligibilityId - the eligibility URI
             * @returns {Promise} that resolves once the eligibility is unshielded
             */
            unshieldEligibility : function unshieldEligibility(eligibilityId){
                return new Promise(function (resolve, reject){
                    if( !_.isString(eligibilityId) ) {
                        reject(new TypeError('Invalid eligibility'));
                    }

                    request('unshieldEligibility', {
                        uri : testCenterId,
                        eligibility : eligibilityId
                    })
                    .success(resolve)
                    .fail(function(xhr){
                        return reject(new Error(xhr.responseText));
                    });
                });
            }
        };
    };
});
