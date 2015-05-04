ScriptLoader.load('http://cdn.jsdelivr.net/jquery.slick/1.5.0/slick.min.js');
Asset.css('http://cdn.jsdelivr.net/jquery.slick/1.5.0/slick.css');
Asset.css('http://cdn.jsdelivr.net/jquery.slick/1.5.0/slick-theme.css');
var ProductView = function (el) {
    jQuery('.single-item', $(el)).slick({
        dots: true
    });

}





