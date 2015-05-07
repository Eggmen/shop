ScriptLoader.load('http://cdn.jsdelivr.net/jquery.slick/1.5.0/slick.min.js');
var ProductView;
(function ($, window, document) {
    ProductView = function (el) {
        this.el = $('#' + el);
        $('.single-item', this.el).slick({
            asNavFor: '.multiple-items'
        });
        $('.multiple-items', this.el).slick({
            slidesToShow: 3,
            slidesToScroll: 1,
            asNavFor: '.single-item',
            /*centerMode: true,
             centerPadding: '25%',*/
            focusOnSelect: true
        });
        this.productID = this.el.prop('id');

        $('#wishlist').click($.proxy(function(e){
            e.preventDefault();
            this.addToWishlist(this.productID);
        }, this));
    }
    ProductView.prototype.addToWishlist = function(productID){
        console.log(this.el.prop('wishlist-url') + productID +'/');
    }
}(window.jQuery, window, document));



