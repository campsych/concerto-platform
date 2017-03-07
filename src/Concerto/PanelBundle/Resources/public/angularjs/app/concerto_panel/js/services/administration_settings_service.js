concertoPanel.factory('AdministrationSettingsService', function ($http) {
    return {
        settingsMapInitialized: false,
        settingsMapPath: Paths.ADMINISTRATION_SETTINGS_MAP,
        settingsMap: {},
        fetchSettingsMap: function (params, callback) {
            var obj = this;
            $http({
                url: obj.settingsMapPath,
                method: "GET",
                params: params
            }).success(function (c) {
                obj.settingsMap = c;
                obj.settingsMapInitialized = true;
                if (callback)
                    callback.call(this);
            });
        },
        get: function (key) {
            return this.settingsMap[key];
        }
    };
});
