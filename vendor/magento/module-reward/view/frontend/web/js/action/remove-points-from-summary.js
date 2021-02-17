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
    'Magento_Ui/js/model/messageList',
    'mage/translate',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/totals'
], function (
    $,
    storage,
    errorProcessor,
    messageList,
    $t,
    fullScreenLoader,
    getPaymentInformationAction,
    totals
) {
    'use strict';

    return function (url) {
        var message = $t('You removed the reward points from this order.');

        messageList.clear();
        fullScreenLoader.startLoader();
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
            messageList.addSuccessMessage({
                'message': message
            });
        }).error(function (response) {
            totals.isLoading(false);
            errorProcessor.process(response);
        }).always(function () {
            fullScreenLoader.stopLoader();
        });
    };
});
