module.exports = function () {
    var btnListRefresh = $("#test-tab .btn-list-refresh");
    
    this.get = function () {
        browser.get('/admin#/tests');
    };
    
    this.refreshList = function(){
        return btnListRefresh.click();
    }
};