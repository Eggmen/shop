var ProductList = new Class({
    initialize: function (el) {
        this.element = $(el);
        this.productList = this.element.getElement('.goods_list');
        this.element.getElements('.goods_view_type a').addEvent('click', function (e) {
            e.stop();
            $(e.target).addClass('active');
            this.productList.toggleClass('wide_list');
        }.bind(this));
    }
});