/**
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
(function(window){
    'use strict';

    //the url of the app config is set into the data-config attr of the loader.
    var appConfig = document.getElementById('amd-loader').getAttribute('data-config');
    //loads the config
    require([appConfig], function(){

        require(['jquery', 'context', 'helpers', 'core/historyRouter'],
            function ($, context, helpers, historyRouter){


            var router = historyRouter();

            //dispatch the entry point
            router.dispatch(helpers._url(context.action, context.module, context.extension), true);


            //FIXME move this away!!!
            $.get(helpers._url('user', 'Action', 'taoMpArt'))
             .success(function(response){
                var $actionBar = $('.action-bar');
                var actions = response.reduce(function(acc, action){

                    acc +=
                        '<li class="action btn-info small" title="' + action.desc + '" >' +
                            '<a class="li-inner" href="' + helpers._url(action.action, action.controller, action.extension) + '"><span class="icon-' + action.icon + ' glyph"> ' + action.name + '</span></a>' +
                        '</li>';
                    return acc;
                }, '');

                var $entryList = $('#entry-point-box');
                var entries = response.reduce(function(acc, action){
                    acc +=
                        '<li>' +
                            '<a class="block entry-point  href="#">' +
                                '<h1><span class="icon-' + action.icon + '"> '  + action.name + '</h1>' +
                                '<p>' + action.desc + '</p>' +
                                '<div class="clearfix">' +
                                    '<span class="text-link" href="#"><span class="icon-login"></span> Enter </span>' +
                                '</div>' +
                            '</a>' +
                        '</li>';
                    return acc;
                }, '');

                $actionBar.prepend($(actions));
                $entryList.html(entries);


                $('.action a').click(function(e){
                    e.preventDefault();
                    router.trigger('dispatch', $(this).attr('href'));
                });
             });

        });
    });
}(window));
