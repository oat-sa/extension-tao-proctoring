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
    'core/format',
    'core/encoder/time'
], function (format, timeEncoder) {
    'use strict';

    /**
     * Encodes the extra time in a human readable way
     * @param {Number} extraTime - The extra time to encode
     * @param {Number} consumedTime - The already consumed time
     * @param {String} [pattern] - The display pattern used when no time is consumed (default: %s)
     * @param {Number} [unit] - The time unit
     * @returns {String}
     */
    function encodeExtraTime(extraTime, consumedTime, pattern, unit) {
        var encoded = '';

        pattern = pattern || '%s';
        unit = unit || 1;

        if (extraTime) {
            if (consumedTime) {
                encoded = timeEncoder.encode(Math.min(consumedTime, extraTime)) + '/' + timeEncoder.encode(extraTime);
            } else if (pattern === 'HH:mm:ss') {
                encoded = timeEncoder.encode(extraTime);
            } else {
                encoded = format(pattern, extraTime / unit);
            }

            encoded = ' +' + encoded;
        }

        return encoded;
    }

    return encodeExtraTime;
});
