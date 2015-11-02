define(function(){
    return {
        'TaoProctoring' : {
            'actions' : {
                'index' : 'controller/TaoProctoring/index',
                'testSite' : 'controller/TaoProctoring/testsite',
                'diagnostic' : 'controller/TaoProctoring/diagnostic',
                'report' : 'controller/TaoProctoring/report'
        }
    },
        'ProctorDelivery' : {
            'actions' : {
                'index' : 'controller/ProctorDelivery/index',
                'delivery' : 'controller/ProctorDelivery/delivery',
                'testTakers' : 'controller/ProctorDelivery/testTakers'
            }
        },
        'Main': {
            'actions': {
                'index' : 'controller/Main/index'
            }
        }
    };
});
