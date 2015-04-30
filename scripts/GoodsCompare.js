var GoodsCompare = new Class({

	initialize:function (el) {
		this.element = $(el);
		// инициализация информера в шапке + мини-папапа информера
		var url = this.element.getProperty('data-informer-url')+'?html&' + Math.floor((Math.random()*10000));
		if(url){
			this.element.set(
				'load',
				{
					method: 'get',
					'onFailure': this.error.bind(this),
					'onComplete': this.init.bind(this)
				});
			this.element.load(url);
		}
	},

	init: function() {
		// показ мини-попапа сравнения по клику на кнопку информера
		this.element.getElement('.compare_link').addEvent('click', function(e) {
			e.stop();
			this.element.getElement('.popup_compare').show(); // todo: toggle show/hide ?
		}.bind(this));

		// показ попапа сравнения по клику на кнопку внутри мини-попапа сравнения
		this.element.getElements('.compare-toggle').addEvent('click', function(e) {
			e.stop();
			var goods_ids = e.target.getProperty('data-goods-ids');
			var url = this.element.getProperty('data-compare-url') + goods_ids + '/?html';
			console.log(url);
			// todo: popup
		}.bind(this));

		// очистка сравнения
		this.element.getElement('.clear_compare_list').addEvent('click', function(e) {
			e.stop();
			var url = this.element.getProperty('data-clear-url')+'?html&' + Math.floor((Math.random()*10000));
			if(url){
				this.element.set(
					'load',
					{
						method: 'get',
						'onFailure': this.error.bind(this),
						'onComplete': this.init.bind(this)
					});
				this.element.load(url);
			}
		}.bind(this));

		// todo:
		// 1. получить все ids-шки, которые находятся в сравнении (из информера)
		// 2. найти на странице все ссылки "сравнить" (по name=to_compare, например)
		// 3. заменить ссылку "сравнить" на "убрать из сравнения" (если уже есть в списке сравнения)
		// 4. забиндить все клики на эти кнопки на обработчик и ajax методы add / remove
	},

	error: function() {
		this.element.empty();
		this.element.set('html', '');
	}
});