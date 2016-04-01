angular.module('concertoPanel').filter('capitalize', function () {
    return function (input) {
        var words = input.split("_");
        var result = "";
        for (var i = 0; i < words.length; i++) {
            var word = words[i];
            if (word) {
                if (result)
                    result += " ";
                result += word.charAt(0).toUpperCase() + word.substr(1).toLowerCase();
            }
        }
        return result;
    };
});