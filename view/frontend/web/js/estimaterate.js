define([
    'underscore',
    'jquery',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/quote',
    'ko',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/cart/totals-processor/default'
], function (_, $, storage, errorProcessor, quote, ko, totalsService, shippingService, rateRegistry, resourceUrlManager, totalsDefaultProvider) {
    'use strict';
    $.widget('mage.estimateRate', {
        options: {},
        _create: function () {
            var self = this;
            $(document).ready(function () {
                $('[name=country_id], [name=region_id], [name=postcode], [name=city]').each(function (index) {
                    ko.cleanNode(this);
                });
                var btndata = "<button id='getrate' class='getrate'>Get Shipping Quotes</button>";
                jQuery("#block-shipping").after(btndata);
            });
            $('#getrate').on('click', function (e) {
                var address = getShippingAddress1();
                totalsService.isLoading(true);
                shippingService.isLoading(true);
                self.getRates(address, self);
            });
        },

        /**
         * Get shipping rates for specified address.
         * @param {Object} address
         */
        getRates: function (address, self) {
            var /*cache,*/ serviceUrl, payload;
            serviceUrl = resourceUrlManager.getUrlForEstimationShippingMethodsForNewAddress(quote);
            payload = JSON.stringify({
                address: {
                    'city': address.city,
                    'region_id': address.regionId,
                    'region': address.region,
                    'country_id': address.countryId,
                    'postcode': address.postcode
                }
                });

            storage.post(
                serviceUrl,
                payload,
                false
            ).done(function (result) {
                //rateRegistry.set(address.getKey(), null);
                rateRegistry.set(quote.shippingAddress().getCacheKey(), result);
                shippingService.setShippingRates(result);
                $("#co-shipping-method-form [type=radio]").on('click', function (e) {
                    var interval = null;
                    interval = setInterval(setTotals, 500);

                    function setTotals()
                    {
                        if (!totalsService.isLoading()) {
                            var quoteShipAmnt = parseFloat(quote.shippingMethod()['amount']);
                            var parsedQuoteShipAmnt = quoteShipAmnt.toFixed(2);
                            var shipAmt = parsedQuoteShipAmnt.toString();

                            var quoteTotal = parseFloat(quote.totals()['subtotal']);

                            var total = quoteTotal + quoteShipAmnt;
                            var parsedtotal = total.toFixed(2);
                            var total1 = parsedtotal.toString();
                            var gndTotal = jQuery('.grand > td.amount').find('.price').text();

                            var shipPrice = jQuery('.shipping > td.amount').find('.price').text();
                            var currency = shipPrice.substring(0, 1);
                            var updatedShipPrice = jQuery('.shipping > td.amount').find('.price').text(currency + shipAmt);

                            var updatedgndTotal = jQuery('.grand > td.amount').find('.price').text(currency + total1);
                            clearInterval(interval);
                        }
                    }
                });


                shippingService.isLoading(false);
                totalsService.isLoading(false);
            }).fail(function (response) {
                shippingService.setShippingRates([]);
                errorProcessor.process(response);
                shippingService.isLoading(false);
                totalsService.isLoading(false);
            })
        },
    });
    return $.mage.estimateRate;

    /**
     * @return {estimaterateL#12.getShippingAddress1.address}
     */
    function getShippingAddress1()
    {
        var address = {
            'city': $('input[name="city"]').val(),
            'countryId': $('select[name="country_id"]').val(),
            'postcode': $('input[name="postcode"]').val(),
            'regionId': ($('select[name="region_id"]').val()) ? $('select[name="region_id"]').val() : 0,
            'region': $('select[name="region"]').val()
        };

        return address;
    }
});
