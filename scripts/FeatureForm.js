ScriptLoader.load('Form');
var FeatureForm = new Class({
    Extends: Form,

    initialize: function (el) {
        this.parent(el);
        this.prepareFilterProperties();
        this.componentElement.getElementById('feature_is_filter').addEvent('change', this.prepareFilterProperties.bind(this));
		this.componentElement.getElementById('feature_type').addEvent('change', this.prepareFilterProperties.bind(this));
    },

    prepareFilterProperties: function () {
        var filter, feature_type;

        if ((filter = this.componentElement.getElementById('feature_is_filter')) && filter.checked) {
            this.componentElement.getElementById('feature_sysname').getParent('.field').show();
            this.componentElement.getElementById('feature_filter_type').getParent('.field').show();
        }
        else {
            this.componentElement.getElementById('feature_sysname').getParent('.field').hide();
            this.componentElement.getElementById('feature_filter_type').getParent('.field').hide();
        }

		if (feature_type = this.componentElement.getElementById('feature_type')) {
			if (['OPTION','MULTIOPTION','VARIANT'].indexOf(feature_type.get('value')) >= 0) {
				// показываем вкладку Перечисляемые значения
				//console.log('show options for ' + feature_type.get('value'));
			} else {
				// прячем вкладку Перечисляемые значения
				//console.log('hide options for ' + feature_type.get('value'));
			}
		}
    }
});