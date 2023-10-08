/*jshint jquery:true*/
define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/url'
    ],
    function ($, quote, fullScreenLoader,urlBuilder) {
        'use strict';
        
        return function (payload,messageContainer) {
 
		
             var td = new Date();
            // Get information from Magento checkout to load Checkout
            const id = quote.getQuoteId();
            const amount = quote.totals().grand_total.toString();

            const milisec = td.getMilliseconds();
            const add = td.getMinutes() + td.getSeconds() + milisec;


            var paymentData = quote.paymentMethod();

            var customLink = urlBuilder.build('argenmagento');
            
            var vEmail;  
				  if(quote.guestEmail) 
				        vEmail = quote.guestEmail;
                else 
				    vEmail = window.checkoutConfig.customerData.email;
				    
			var prx = btoa(vEmail);

            fullScreenLoader.startLoader();

              $.mage.redirect(customLink + "/Iframe/index?prx=" + prx);

        };
    }
);