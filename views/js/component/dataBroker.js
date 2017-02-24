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
    'core/promise',
    'core/dataProvider/proxy',
    'core/dataProvider/proxy/ajax',
    'core/dataProvider/dataBroker'
], function (_,
             Promise,
             proxyFactory,
             ajaxProxy,
             dataBrokerFactory) {
    'use strict';

    var _defaults = {};

    /**
     * @param {Object} config - Some optional config entries
     * @param {String} config.scope
     * @param {middlewareHandler} [config.middlewares]
     * @param {Object} [config.providers]
     * @returns {Promise}
     */
    function proctoringDataBrokerFactory(config) {
        var initConfig = _.defaults({}, config, _defaults);
        var providers = initConfig.providers || {};
        var dataBroker = dataBrokerFactory(initConfig);
        var initChain = [];

        _.forEach(providers, function (provider, name) {
            if (provider instanceof Promise) {
                initChain.push(provider.then(function (instance) {
                    providers[name] = instance;
                }));
            }
        });

        return Promise.all(initChain).then(function () {
            _.forEach(providers, function (provider, name) {
                dataBroker.addProvider(name, provider);
            });

            return dataBroker;
        });
    }

    proxyFactory
        .registerProvider('ajax', ajaxProxy);

    return proctoringDataBrokerFactory;
});
