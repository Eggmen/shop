/**
 * @file Contain the description of the next classes:
 * <ul>
 *     <li>[OrderForm]{@link DivForm}</li>
 * </ul>
 *
 * @requires Form
 * @requires ModalBox
 *
 * @author Andy Karpov
 *
 * @version 1.0.0
 */

ScriptLoader.load('Form', 'ModalBox');

/**
 * OrderForm.
 *
 * @augments Form
 *
 * @constructor
 * @param {Element|string} element The form element.
 */
var OrderForm = new Class(/** @lends OrderForm# */{
	Extends: Form,

	// constructor
	initialize: function (element) {
        //Asset.css('order_editor.css');
		this.parent(element);

		$(window).addEvent('orderTabMain', this.onOrderTabMain.bind(this));
		$(window).addEvent('orderTabGoods', this.onOrderTabGoods.bind(this));

		this.element.getElement('[name=shop_orders[order_discount]]').addEvent('keyup', this.recalculateTotals.bind(this));
		this.element.getElement('[name=shop_orders[order_discount]]').addEvent('change', this.recalculateTotals.bind(this));
		this.element.getElement('[name=shop_orders[order_amount]]').setAttribute('readonly', 'readonly');
		this.element.getElement('[name=shop_orders[order_total]]').setAttribute('readonly', 'readonly');

		this.element.getElementById('u_id').addEvent('change', this.fetchUserDetails.bind(this));

		$(window).fireEvent('orderTabMain');

	},

	onTabChange: function () {
		// warning: контекст this тут не формы, а TabPane !!!

		// вкладка "товары заказа"
		if (this.currentTab.hasAttribute('data-src') && this.currentTab.getProperty('data-src').test("goods")) {
			$(window).fireEvent('orderTabGoods');
			this.parent();
		}
		// вкладка "основная"
		else {
			$(window).fireEvent('orderTabMain');
			this.parent();
		}
	},

	fetchUserDetails: function() {

		var uID = this.element.getElementById('u_id').get('value');

		var order_city = this.element.getElement('[name=shop_orders[order_city]]');
		var order_address = this.element.getElement('[name=shop_orders[order_address]]');
		var order_phone = this.element.getElement('[name=shop_orders[order_phone]]');
		var order_email = this.element.getElement('[name=shop_orders[order_email]]');
		var order_user_name = this.element.getElement('[name=shop_orders[order_user_name]]');

		console.log('uid', uID);

		if (uID) {
			var url = [this.singlePath, uID, '/user-details/'].join('');

			// ajax request
			Energine.request(
				url,
				null,
				function (data) {
					if (data.result) {
						order_city.set('value', data.city);
						order_address.set('value', data.address);
						order_phone.set('value', data.phone);
						order_email.set('value', data.email);
						order_user_name.set('value', data.user_name);
					}
				}.bind(this),
				this.processServerError.bind(this),
				this.processServerError.bind(this)
			);
		} else {
			order_city.set('value', '');
			order_address.set('value', '');
			order_phone.set('value', '');
			order_email.set('value', '');
			order_user_name.set('value', '');
		}
	},

	recalculateTotals: function() {

		var orderID = this.element.getElementById('order_id').get('value');

		var order_amount = this.element.getElement('[name=shop_orders[order_amount]]');
		var order_discount = this.element.getElement('[name=shop_orders[order_discount]]');
		var order_total = this.element.getElement('[name=shop_orders[order_total]]');

		var url = (orderID) ? [this.singlePath, orderID, '/order-total/'].join('') : [this.singlePath, 'order-total/'].join('');

		// ajax request
		Energine.request(
			url,
			{
				'order_discount': order_discount.get('value')
			},
			function (data) {
				if (data.result) {
					order_amount.set('value', data.amount);
					order_total.set('value', data.total);
				}
			}.bind(this),
			this.processServerError.bind(this),
			this.processServerError.bind(this)
		);
	},

	onOrderTabMain: function(e) {
		this.recalculateTotals();
	},

	onOrderTabGoods: function(e) {
		this.recalculateTotals();
	},

	/**
	 * Overridden parent [save]{@link Form#save} action.
	 * @function
	 * @public
	 */
	save: function () {
		return this.parent();
	}

});
