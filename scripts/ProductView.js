ScriptLoader.load('Carousel');

var ProductView = new Class({
    initialize: function (el) {
        this.element = $(el);

	    var galleryLarge = new Carousel(document.getElementById('goodsGalleryLarge'));
	    var gallerySmall = new Carousel(document.getElementById('goodsGallerySmall'), {
		    carousel: {
			    NVisibleItems: 3,
			    scrollStep: 1
		    }
	    });
	    var carCon = new CarouselConnector([galleryLarge, gallerySmall]);
    }
});



