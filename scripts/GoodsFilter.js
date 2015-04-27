var GoodsFilter = new Class({
    initialize: function (el) {
        this.element = $(el);
        if (this.form = this.element.getParent('form')) {
            this.form.getElementById('reset').addEvent('click', function(){
                document.location=this.form.getProperty('action');
            }.bind(this));
        }
    }
});