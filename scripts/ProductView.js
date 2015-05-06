ScriptLoader.load('http://cdn.jsdelivr.net/jquery.slick/1.5.0/slick.min.js');
Asset.css('http://cdn.jsdelivr.net/jquery.slick/1.5.0/slick.css');
Asset.css('http://cdn.jsdelivr.net/jquery.slick/1.5.0/slick-theme.css');
var ProductView = function (el) {
    jQuery('.single-item', $(el)).slick({
        asNavFor: '.multiple-items',
        fade: true
    });
    jQuery('.multiple-items').slick({
        slidesToShow: 3,
        slidesToScroll: 1,
        asNavFor: '.single-item',
        centerMode: true,
        centerPadding: '25%',
        focusOnSelect: true
    });

}





