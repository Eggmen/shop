/**
 * @file Contain the description of the next classes:
 * <ul>
 *     <li>[ShopDivForm]{@link DivForm}</li>
 * </ul>
 *
 * @requires DivForm
 * @requires Form
 * @requires ModalBox
 *
 * @author Andy Karpov
 *
 * @version 1.0.0
 */

ScriptLoader.load('DivForm', 'Form', 'ModalBox');

/**
 * ShopDivForm.
 *
 * @augments Form
 *
 * @borrows Form.Label.setLabel as ShopDivForm#setLabel
 * @borrows Form.Label.prepareLabel as ShopDivForm#prepareLabel
 * @borrows Form.Label.restoreLabel as ShopDivForm#restoreLabel
 * @borrows Form.Label.showTree as ShopDivForm#showTree
 *
 * @constructor
 * @param {Element|string} element The form element.
 */
var ShopDivForm = new Class(/** @lends ShopDivForm# */{
    Extends: DivForm,

    // constructor
    initialize: function (element) {
        this.parent(element);
		// todo: инициализация слушателя изменения pid - обновлять вкладку характеристик при изменении родителя
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
