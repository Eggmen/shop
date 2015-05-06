ScriptLoader.load('http://code.jquery.com/ui/1.11.4/jquery-ui.js');
Asset.css('http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css');
var ProductFilter = function (el) {
    jQuery('.range', $(el)).each(function(idx, range){
        jQuery(range).slider();
    });
}
