ScriptLoader.load('Carousel');

	var ProductsCarousel = new Class({
		initialize: function (el) {
			this.element = $(el);

			new Carousel($(el), {
				carousel: {
					NVisibleItems: 3,
					scrollStep: 1
				}
			});
		}
	});

