define(['jquery', 'helpers', 'util/url', 'ui/feedback'], function($, helpers, url, feedback){
	return {
        start : function(){
            $('.js-pause').on('click', function() {
            	$.ajax({
                    type: "POST",
                    url: url.route('pauseActiveExecutions', 'Tools', 'taoProctoring'),
                    dataType: 'json',
                    success: function(data) {
                        helpers.loaded();
                        if (data.success) {
                            feedback().success(data.message);
                        } else {
                        	feedback().error(data.message);
                        }
                    }
            	});
            });
        }
    };

});
