module.exports = function(grunt) {

    var requirejs   = grunt.config('requirejs') || {};
    var clean       = grunt.config('clean') || {};
    var copy        = grunt.config('copy') || {};

    var root        = grunt.option('root');
    var libs        = grunt.option('mainlibs');
    var ext         = require(root + '/tao/views/build/tasks/helpers/extensions')(grunt, root);
    var out         = 'output';

    var paths = {
        'taoProctoring'          : root + '/taoProctoring/views/js',
        'taoProctoringCss'       :  root + '/taoProctoring/views/css',
        'taoClientDiagnostic'    : root + '/taoClientDiagnostic/views/js',
        'taoClientDiagnosticCss' : root + '/taoClientDiagnostic/views/css',
        'taoQtiItem'             : root + '/taoQtiItem/views/js',
        'taoItems'               : root + '/taoItems/views/js',
        'taoQtiTest'             : root + '/taoQtiTest/views/js'
    };

    /**
     * Remove bundled and bundling files
     */
    clean.taoproctoringbundle = [out];

    /**
     * Compile tao files into a bundle
     */
    requirejs.taoproctoringbundle = {
        options: {
            baseUrl : '../js',
            dir : out,
            mainConfigFile : './config/requirejs.build.js',
            paths : paths,
            modules : [{
                name: 'taoProctoring/controller/app',
                include: ['lib/require', 'loader/bootstrap'].concat(libs),
                exclude : ['json!i18ntr/messages.json']
            }, {
                name: 'taoProctoring/controller/routes',
                include : ext.getExtensionsControllers(['taoProctoring']),
                exclude : ['mathJax'].concat(libs)
            }]
        }
    };

    /**
     * copy the bundles to the right place
     */
    copy.taoproctoringbundle = {
        files: [
            { src: [out + '/taoProctoring/controller/app.js'],       dest: root + '/taoProctoring/views/js/loader/app.min.js' },
            { src: [out + '/taoProctoring/controller/app.js.map'],   dest: root + '/taoProctoring/views/js/loader/app.min.js.map' },
            { src: [out + '/taoProctoring/controller/routes.js'],  dest: root + '/taoProctoring/views/js/controllers.min.js' },
            { src: [out + '/taoProctoring/controller/routes.js.map'],  dest: root + '/taoProctoring/views/js/controllers.min.js.map' }
        ]
    };

    grunt.config('clean', clean);
    grunt.config('requirejs', requirejs);
    grunt.config('copy', copy);

    // bundle task
    grunt.registerTask('taoproctoringbundle', ['clean:taoproctoringbundle', 'requirejs:taoproctoringbundle', 'copy:taoproctoringbundle']);
};
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
 * Copyright (c) 2014-2018 (original work) Open Assessment Technologies SA;
 */

/**
 * configure the extension bundles
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
module.exports = function(grunt) {
    'use strict';

    grunt.config.merge({
        bundle : {
            taoproctoring : {
                options : {
                    extension : 'taoProctoring',
                    outputDir : 'loader',
                    dependencies : [
                        'taoProctoring',
                        'taoClientDiagnostic',
                        'taoQtiItem',
                        'taoItems',
                        'taoQtiTest'
                    ],
                    bundles : [{
                        name : 'taoProctoring',
                        default : true,
                        bootstrap : true
                    }]
                }
            }
        }
    });

    // bundle task
    grunt.registerTask('taoproctoringbundle', ['bundle:taoproctoring']);
};
