module.exports = function(grunt) { 

    var sass    = grunt.config('sass') || {};
    var watch   = grunt.config('watch') || {};
    var notify  = grunt.config('notify') || {};
    var root    = grunt.option('root') + '/taoProctoring/views/';

    sass.taoproctoring = { };
    sass.taoproctoring.files = { };
    sass.taoproctoring.files[root + 'css/proctoring.css'] = root + 'scss/proctoring.scss';
    sass.taoproctoring.files[root + 'css/deliveryServer.css'] = root + 'scss/deliveryServer.scss';
    sass.taoproctoring.files[root + 'css/testCenterManager.css'] = root + 'scss/testCenterManager.scss';
    sass.taoproctoring.files[root + 'css/printReport.css'] = root + 'scss/printReport.scss';

    watch.taoproctoringsass = {
        files : [root + 'views/scss/*.scss', root + 'views/scss/**/*.scss'],
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
