concertoPanel.factory('AuthService', function ($http) {
    return {
        getAuthUserPath: Paths.ADMINISTRATION_GET_AUTH_USER,
        user: null,
        fetchAuthUser: function (callback) {
            let obj = this;
            $http({
                url: obj.getAuthUserPath,
                method: "GET"
            }).then(function (httpResponse) {
                obj.user = httpResponse.data.user;
                if (callback)
                    callback.call(this);
            });
        }
    };
});