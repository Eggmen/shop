var WishlistDaemon = new Class({
    initialize: function (el) {
        this.el = $(el);
        this.request = new Request.HTML({
            'method': 'get',
            'onSuccess': function(){
                console.log(arguments)
            }
        });
    },
    add: function(event, productID){
        event = new DOMEvent(event);
        event.stop();
        this.request.send({url:this.el.getProperty('data-url') + productID + '/'});
    }

});