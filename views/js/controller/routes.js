define(function(){
    return {
        'TestCenter' : {
            'actions' : {
                'index' : 'controller/TestCenter/index',
                'testCenter' : 'controller/TestCenter/testCenter'
            }
        },
        'Diagnostic' : {
            'actions' : {
                'index' : 'controller/Diagnostic/index'
            }
        },
        'Reporting' : {
            'actions' : {
                'index' : 'controller/Reporting/index'
            }
        },
        'Delivery' : {
            'actions' : {
                'index' : 'controller/Delivery/index',
                'monitoring' : 'controller/Delivery/monitoring',
                'testTakers' : 'controller/Delivery/testTakers'
            }
        }
    };
});
