var TestTabPage = require("./../page_objects/test_tab.js");

describe('Concerto Panel - Test tab', function () {
    var testTabPage = new TestTabPage();
    testTabPage.get();
    
    testTabPage.refreshList();
});