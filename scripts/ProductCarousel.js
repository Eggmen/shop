ScriptLoader.load('http://cdn.jsdelivr.net/jquery.slick/1.5.0/slick.min.js');
Asset.css('http://cdn.jsdelivr.net/jquery.slick/1.5.0/slick.css');
Asset.css('http://cdn.jsdelivr.net/jquery.slick/1.5.0/slick-theme.css');

var ProductCarousel = function (el) {
    jQuery('.multiple-items').slick({
        dots:false,
        infinite: true,
        slidesToShow: 3,
        slidesToScroll: 3
    });
}

