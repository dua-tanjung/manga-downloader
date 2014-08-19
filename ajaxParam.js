function ajaxParam() {
    this.value = "";
    
    this.addParam = function(strParam, strValue) {
        if (this.value.length!=0) {
            this.value += '&' + strParam + '=' + encodeURIComponent(strValue);
        } else {
            this.value += strParam + '=' + encodeURIComponent(strValue);
        }
    }
    
    this.clear = function() {
        this.value = "";
    }
}

function ajaxParams() {
    this.values = [];
    
    this.clear = function() {
        this.values = [];
    }
    
    this.addParam = function(strParam, strValue) {
        this.values[strParam] = strValue;
    }
    
    this.toString = function() {
        var hsl = '';
        for (var key in this.values) {
            if (hsl.length!=0) {
                hsl += '&' + key + '=' + encodeURIComponent(this.values[key]);
            } else {
                hsl += key + '=' + encodeURIComponent(this.values[key]);
            }
        }
        return hsl;
    }
}
