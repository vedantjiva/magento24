define([
    'jquery',
    'mage/url',
], function ($, url) {
    'use strict';

    return function (config, element) {

        let formId = $(element).find('iframe').attr('id');
        let shouldSubscribe = $(element).find('script').data('add-respondent') === 1;

        ecPF.onComplete(function (formData) {
            let hasContactEmail = formData.contactEmail != null && formData.contactEmail.length > 0;

            if (typeof window.dmPt !== 'undefined' && hasContactEmail) {
                window.dmPt('identify', formData.contactEmail);
            }

            if (shouldSubscribe && hasContactEmail) {
                $.post(url.build('newsletter/subscriber/new'), {
                    email: formData.contactEmail
                }).done(function() {
                    window.scrollTo(0,0);
                    window.location.reload();
                });
            }
        }, formId);
    };
});
