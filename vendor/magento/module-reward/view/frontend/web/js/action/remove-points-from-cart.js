/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 *
 */
define([
    'jquery',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'mage/translate',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/totals',
    'Magento_Customer/js/customer-data'
], function (
    $,
    storage,
    errorProcessor,
    $t,
    getPaymentInformationAction,
    totals,
    customerData
) {
    'use strict';

    return function (url) {
        var message = $t('You removed the reward points from this order.');

        //delete any existing messages
        customerData.set('messages', {});
        $(document.body).trigger('processStart');
        storage.post(
            url, {}
        ).done(function (response) {
            var deferred;

            if (response) {
                deferred = $.Deferred();
                totals.isLoading(true);
                getPaymentInformationAction(deferred);
            }
            $.when(deferred).done(function () {
                totals.isLoading(false);
            });
            customerData.set('messages', {
                messages: [{
                    type: 'success',
                    text: message
                }]
            });
        }).error(function (response) {
            totals.isLoading(false);
            errorProcessor.process(response);
        }).always(function () {
            $(document.body).trigger('processStop');
        });
    };
});
