define([
    'M2ePro/Grid'
], function () {
    window.ListingOtherGrid = Class.create(Grid, {

        // ---------------------------------------

        productTitleCellIndex: 2,

        // ---------------------------------------

        prepareActions: function()
        {
            this.movingHandler      = new ListingMoving(this);
            this.autoMappingHandler = new ListingOtherAutoMapping(this);
            this.removingHandler    = new ListingOtherRemoving(this);
            this.unmappingHandler   = new ListingOtherUnmapping(this);

            this.actions = {
                movingAction: this.movingHandler.run.bind(this.movingHandler),
                autoMappingAction: this.autoMappingHandler.run.bind(this.autoMappingHandler),
                removingAction: this.removingHandler.run.bind(this.removingHandler),
                unmappingAction: this.unmappingHandler.run.bind(this.unmappingHandler)
            };
        },

        // ---------------------------------------

        afterInitPage: function()
        {
            var submitButton = $$('#'+this.gridId+'_massaction-form .admin__grid-massaction-form button');

            submitButton.each((function(s) {
                s.writeAttribute("onclick",'');
                s.observe('click', (function() {
                    this.massActionSubmitClick();
                }).bind(this));
            }).bind(this));
        }

        // ---------------------------------------
    });
});