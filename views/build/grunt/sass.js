module.exports = function(grunt) {
    'use strict';

    var sass    = grunt.config('sass') || {};
    var watch   = grunt.config('watch') || {};
    var notify  = grunt.config('notify') || {};
    var root    = grunt.option('root') + '/taoProctoring/views/';

    sass.taoproctoring = { };
    sass.taoproctoring.files = { };
    sass.taoproctoring.files[root + 'css/proctoring.css']        = root + 'scss/proctoring.scss';
    sass.taoproctoring.files[root + 'css/deliveryServer.css']    = root + 'scss/deliveryServer.scss';
    sass.taoproctoring.files[root + 'css/printReport.css']       = root + 'scss/printReport.scss';
    sass.taoproctoring.files[root + 'css/tools.css']       = root + 'scss/tools.scss';

    watch.taoproctoringsass = {
        files : [root + 'scss/*.scss'],
        tasks : ['sass:taoproctoring', 'notify:taoproctoringsass'],
        options : {
            debounceDelay : 1000
        }
    };

    notify.taoproctoringsass = {
        options: {
            title: 'Grunt SASS',
            message: 'SASS files compiled to CSS'
        }
    };

    grunt.config('sass', sass);
    grunt.config('watch', watch);
    grunt.config('notify', notify);

    //register an alias for main build
    grunt.registerTask('taoproctoringsass', ['sass:taoproctoring']);
};
