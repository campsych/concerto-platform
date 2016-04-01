String.prototype.pf = function() {
    var s = this,
        i = arguments.length;

    while (i--) {
        s = s.replace(new RegExp('%7B' + i + '%7D', 'gm'), arguments[i]);
        s = s.replace(new RegExp('\\{' + i + '\\}', 'gm'), arguments[i]);
    }
    return s;
};