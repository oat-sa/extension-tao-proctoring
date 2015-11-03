define(function(){
    return {
        'TestCenter' : {
            'actions' : {
                'index' : 'controller/TaoProctoring/index',
                'testCenter' : 'controller/TaoProctoring/testsite'
            }
        },
        'Diagnostic' : {
            'actions' : {
                'index' : 'controller/TaoProctoring/diagnostic'
            }
        },
        'Reporting' : {
            'actions' : {
                'index' : 'controller/TaoProctoring/report'
            }
        },
        'Delivery' : {
            'actions' : {
                'index' : 'controller/ProctorDelivery/index',
                'monitoring' : 'controller/ProctorDelivery/delivery',
                'testTakers' : 'controller/ProctorDelivery/testTakers'
            }
        }
    };
});
