ScriptLoader.load('Carousel');

window.addEvent('domready', function() {
	var goodsCarousel = new Carousel(document.getElementById('goodsCarousel'), {
		carousel: {
			NVisibleItems: 3,
			scrollStep: 1
		}
	});
});