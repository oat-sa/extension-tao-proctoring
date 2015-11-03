define(function(){
    return {
        'TestCenter' : {
            'actions' : {
                'testCenters' : 'controller/TaoProctoring/index',
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
                'index' : 'controller/TaoProctoring/reporting'
            }
        },
        'Delivery' : {
            'actions' : {
                'deliveries' : 'controller/ProctorDelivery/index',
                'monitoring' : 'controller/ProctorDelivery/delivery',
                'testTakers' : 'controller/ProctorDelivery/testTakers'
            }
        },
        'Main' : {
            'actions' : {
                'index' : 'controller/Main/index'
            }
        }
    };
});
