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
 * Copyright (c) 2016  (original work) Open Assessment Technologies SA;
 *
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

define(['util/url', 'core/dataProvider/request'], function (url, request) {
    'use strict';

    /**
     * @typedef {Object} reportingProvider
     */

    /**
     * Creates an reportingProvider
     * @returns {reportingProvider} the provider
     */
    var reportingProviderFactory = {
        hasReport: function hasReport(id) {
            return request(url.route('hasReport', 'Reporting', 'taoProctoring'), {id: id}, 'POST');
        }
    };

    return reportingProviderFactory;
});
