module.exports = function(grunt) {

    var requirejs   = grunt.config('requirejs') || {};
    var clean       = grunt.config('clean') || {};
    var copy        = grunt.config('copy') || {};

    var root        = grunt.option('root');
    var libs        = grunt.option('mainlibs');
    var ext         = require(root + '/tao/views/build/tasks/helpers/extensions')(grunt, root);
    var out         = 'output';

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
            paths : { 'taoProctoring' : root + '/taoProctoring/views/js'},
            modules : [{
                name: 'taoProctoring/controller/routes',
                include : ext.getExtensionsControllers(['taoProctoring']),
                exclude : ['mathJax', 'mediaElement'].concat(libs)
            }]
        }
    };

    /**
     * copy the bundles to the right place
     */
    copy.taoproctoringbundle = {
        files: [
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
