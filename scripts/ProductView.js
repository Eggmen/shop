ScriptLoader.load('Carousel');

var ProductView = new Class({
    initialize: function (el) {
        this.element = $(el);

	    var galleryLarge = new Carousel(document.getElementById('goodsGalleryLarge'),
		    {carousel:{
			    controls:{
				    styles:{
					    forward:{
						    marginLeft:''
					    }
				    }
			    }
		    }});
    }
});



