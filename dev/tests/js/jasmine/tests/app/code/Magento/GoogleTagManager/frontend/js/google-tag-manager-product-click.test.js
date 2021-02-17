/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_GoogleTagManager/js/google-analytics-universal'
], function (GaUniversal) {
    'use strict';

    describe('GoogleTagManager/js/google-analytics-universal', function () {
        var ga;

        beforeAll(function () {
            var config = {
                blockNames: [
                    'category.products.list',
                    'product.info.upsell',
                    'catalog.product.related',
                    'checkout.cart.crosssell',
                    'search_result_list'
                ]
            };

            ga = new GaUniversal(config);
        });

        it('Check for proper selector to be called', function () {
            expect(window.$$).toBeDefined('$$ must be used to join coma-separated values properly');
            spyOn(window, '$$').and.returnValue([]);

            ga.bindImpressionClick(
                'product_configurable_1',
                'configurable',
                'Configurable Product',
                'test-category',
                'Catalog Page',
                '1',
                'category.products.list',
                '0'
            );

            expect(window.$$).toHaveBeenCalledTimes(2);
        });
    });
});
