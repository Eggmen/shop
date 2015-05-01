ScriptLoader.load('Carousel');

	var ProductsCarousel = new Class({
		initialize: function (el) {
			this.element = $(el);
			var carouselSize = $(el).getSize();

			if (carouselSize.x > 550) {
				new Carousel($(el), {
					carousel: {
						NVisibleItems: 4,
						scrollStep: 1
					}
				});
			}
			else if (carouselSize.x > 450) {
				new Carousel($(el), {
					carousel: {
						NVisibleItems: 3,
						scrollStep: 1
					}
				});
			}
			else {
				new Carousel($(el), {
					carousel: {
						NVisibleItems: 1,
						scrollStep: 1
					}
				});
			}
		}
	});

