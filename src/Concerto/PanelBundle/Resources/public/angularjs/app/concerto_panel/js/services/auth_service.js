concertoPanel.factory('AuthService', function ($http) {
    return {
        getAuthUserPath: Paths.ADMINISTRATION_GET_AUTH_USER,
        user: null,
        fetchAuthUser: function (callback) {
            var obj = this;
            $http({
                url: obj.getAuthUserPath,
                method: "GET"
            }).success(function (c) {
                obj.user = c.user;
                if (callback)
                    callback.call(this);
            });
        }
    };
});