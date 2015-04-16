ScriptLoader.load('Form');
var FeatureForm = new Class({
    Extends: Form,
    initialize: function (el) {
        this.parent(el);
        this.prepareFilterProperties();
        this.componentElement.getElementById('feature_is_filter').addEvent('change', this.prepareFilterProperties.bind(this));
    },
    prepareFilterProperties: function () {
        var filter;

        if ((filter = this.componentElement.getElementById('feature_is_filter')) && filter.checked) {
            this.componentElement.getElementById('feature_sysname').getParent('.field').show();
            this.componentElement.getElementById('feature_filter_type').getParent('.field').show();
        }
        else {
            this.componentElement.getElementById('feature_sysname').getParent('.field').hide();
            this.componentElement.getElementById('feature_filter_type').getParent('.field').hide();
        }

    }
});