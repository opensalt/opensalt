const utilSalt = (function () {
    function simplify(string) {
        return string.match(/[a-zA-Z]*/g).join("").toLowerCase();
    }

    function capitalize(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    function titleize(string) {
        return capitalize(string.replace(/([A-Z]+)/g, " $1").replace(/([A-Z][a-z])/g, " $1"));
    }

    function chunk(array, n) {
        if (!this.length) {
            return [];
        }
        return [this.slice(0, n)].concat(this.slice(n).chunk(n));
    }

    function spinnerHtml(msg) {
        return '<div class="spinnerOuter"><span class="glyphicon glyphicon-cog spinning spinnerCog"></span><span class="spinnerText">' + msg + '</span></div>';
    }

    return {
        simplify: simplify,
        titleize: titleize,
        spinner: spinnerHtml
    };
})();

module.exports = utilSalt;
