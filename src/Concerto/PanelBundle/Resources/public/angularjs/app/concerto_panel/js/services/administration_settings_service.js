concertoPanel.factory('AdministrationSettingsService', function ($http) {
    return {
        settingsMapInitialized: false,
        settingsMapPath: Paths.ADMINISTRATION_SETTINGS_MAP,
        internalSettingsMap: {},
        exposedSettingsMap: {},
        starterContentEditable: false,
        fetchSettingsMap: function (params, callback) {
            let obj = this;
            $http({
                url: obj.settingsMapPath,
                method: "GET",
                params: params
            }).then(function (response) {
                obj.internalSettingsMap = response.data.internal;
                obj.exposedSettingsMap = response.data.exposed;
                obj.starterContentEditable = response.data.internal.editable_starter_content == "1";
                obj.settingsMapInitialized = true;
                if (callback)
                    callback.call(this);
            });
        },
        get: function (key) {
            if (key in this.internalSettingsMap)
                return this.internalSettingsMap[key];
            if (key in this.exposedSettingsMap)
                return this.exposedSettingsMap[key];
            return null;
        }
    };
});
