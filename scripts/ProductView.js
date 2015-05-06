ScriptLoader.load('http://cdn.jsdelivr.net/jquery.slick/1.5.0/slick.min.js');
var ProductView;
(function ($, window, document) {
    ProductView = function (el) {
        el = document.id(el)
        $('.single-item', el).slick({
            asNavFor: '.multiple-items'
        });
        $('.multiple-items', el).slick({
            slidesToShow: 3,
            slidesToScroll: 1,
            asNavFor: '.single-item',
            /*centerMode: true,
             centerPadding: '25%',*/
            focusOnSelect: true
        });
    }
}(window.jQuery, window, document));



