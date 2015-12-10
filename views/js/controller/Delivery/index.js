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
    'lodash',
    'jquery',
    'i18n',
    'helpers',
    'moment',
    'layout/loading-bar',
    'ui/listbox',
    'util/encode',
    'ui/feedback',
    'ui/bulkActionPopup',
    'taoProctoring/helper/status',
    'taoProctoring/component/breadcrumbs',
    'tpl!taoProctoring/templates/delivery/listBoxActions',
    'tpl!taoProctoring/templates/delivery/listBoxStats'
], function (_, $, __, helpers, moment, loadingBar, listBox, encode, feedback, bulkActionPopup, _status, breadcrumbsFactory, actionsTpl, statsTpl) {
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
    var cssScope = '.delivery-index';

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
            var categories = $container.data('categories');
            var testCenterId = $container.data('testcenter');
            var list = listBox({
                title: __("Sessions"),
                textEmpty: __("No sessions available"),
                textNumber: __("Available"),
                textLoading: __("Loading"),
                renderTo: $container.find('.content'),
                replace: true,
                list: format(boxes),
                width:12,

                // discard the "all sessions" box from available count
                countRenderer: function(count) {
                    return count - 1;
                }
            });
            var bc = breadcrumbsFactory($container, crumbs);
            var serviceUrl = helpers._url('deliveries', 'Delivery', 'taoProctoring', {testCenter : testCenterId});
            var pollTo = null;
            
            function format(boxes){
                
                _.each(boxes, function(box){

                    var props = box.properties;
                    var tplData = {
                        locked : box.stats.awaitingApproval,
                        inProgress : box.stats.inProgress,
                        paused : box.stats.paused
                    };

                    if(props && props.periodStart && props.periodEnd){
                        tplData.showProperties = true;
                        tplData.periodStart = moment(props.periodStart).toString();
                        tplData.periodEnd = moment(props.periodEnd).toString();

                        //add a special class for boxes that have more information to display
                        box.cls = 'has-properties-displayed';
                    }
                    box.html = actionsTpl({
                        id : box.id
                    });
                    box.content = statsTpl(tplData);
                });

                return boxes;
            }

            // update the index from a JSON array
            function update(boxes) {
                
                if (pollTo) {
                    clearTimeout(pollTo);
                    pollTo = null;
                }

                list.update(format(boxes));
                loadingBar.stop();

                // poll the server at regular interval to refresh the index
                if (refreshPolling) {
                    pollTo = setTimeout(refresh, refreshPolling);
                }
            };

            // refresh the index
            function refresh() {
                loadingBar.start();
                list.setLoading(true);

                $.ajax({
                    url: serviceUrl,
                    cache: false,
                    dataType : 'json',
                    type: 'GET'
                }).done(function(boxes) {
                    update(boxes);
                });
            };
            
            /**
             * Exec 
             * @param {String} actionName
             * @param {String} actionTitle
             * @param {Array} selection
             * @param {Function} cb
             * @returns {undefined}
             */
            function pause(deliveryId, selection){
                
                var allowed  = _.map(selection, function(data){
                    return {
                        id : data.id,
                        label : data.testTaker.firstName + ' ' + data.testTaker.lastName
                    };
                });
                
                bulkActionPopup({
                    renderTo : $container,
                    actionName : __('Pause Session'),
                    reason : true,
                    resourceType : 'test taker',
                    allowedResources : allowed,
                    categoriesDefinitions : categories.pause.categoriesDefinitions,
                    categories : categories.pause.categories
                }).on('ok', function(reason){
                    //execute callback
                    $.ajax({
                        url: helpers._url('pauseExecutions', 'Delivery', 'taoProctoring'),
                        data: {
                            delivery : deliveryId,
                            testCenter : testCenterId,
                            execution: _.pluck(selection, 'id'),
                            reason: reason
                        },
                        dataType : 'json',
                        type: 'POST',
                        error: function() {
                            loadingBar.stop();
                        }
                    }).done(function(response) {
                        loadingBar.stop();
                        if (response && response.success) {
                            feedback().success('Selected deliveries successfully paused');
                            refresh();
                        } else {
                            feedback().warning(__('Something went wrong ...') + '<br>' + encode.html(response.error), {encodeHtml: false});
                        }
                    });
                });
            }
            
            $container.on('click', '.pause', function(e){
                
                var deliveryId = $(this).data('delivery');
                var pauseUrl = (deliveryId === 'all') ? 
                    helpers._url('allDeliveriesExecutions', 'Delivery', 'taoProctoring', {testCenter : testCenterId}) :
                    helpers._url('deliveryExecutions', 'Delivery', 'taoProctoring', {delivery : deliveryId, testCenter : testCenterId});
                
                //prevent clicking the parent link that goes to the monitoring screen
                e.stopPropagation();
                e.preventDefault();
                
                //get list of all test taker for the selected delivery
                $.get(pauseUrl, function(res){
                    if(_.isPlainObject(res) && _.isArray(res.data)){
                        var inProgressExecs = _.filter(res.data, function(data){
                            return (data.state && data.state.status === _status.getStatus('inprogress').code);
                        });
                        if(inProgressExecs.length){
                            pause(deliveryId, inProgressExecs);
                        }else{
                            feedback().info(__('There is no delivery in progress'));
                        }
                    }
                });
            });
            
            if (!boxes) {
                refresh();
            } else {
                loadingBar.stop();
            }
        }
    };

    return taoProctoringCtlr;
});
