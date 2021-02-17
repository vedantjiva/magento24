define([
    'Magento_Ui/js/form/element/select',
    'jquery',
    'mage/url'
], function (Select, $, url) {
    'use strict';

    return Select.extend({
        defaults: {
            disabled: true,
            caption: '-- Please Select --',
            imports: {
                accessToken: '${ $.provider }:data.magento_api_access_token',
                baseUrl: '${ $.provider }:data.base_url',
                accountId: '${ $.provider }:data.account_select',
            },
            listens: {
                accessToken: 'setApiAccessToken',
                baseUrl: 'setBaseUrl',
                accountId: 'fetchECForms',
            },
            previouslySelectedValue: '',
        },

        /**
         * Dependently display dropdown component if it contains more than one option
         *
         * @returns {Object} Chainable
         */
        setOptions: function (data) {
            this._super(data);
            if (this.options().length) {
                this.value(this.previouslySelectedValue);
                this.setDisabled(false);
            }

            return this;
        },

        fetchECForms: function (websiteId) {
            var _this2 = this;

            if (!websiteId || typeof websiteId === "undefined") {
                return;
            }

            if (this.source.data) {
                this.previouslySelectedValue = this.source.data.form_select;
            }

            $.ajax({
                url: url.build('rest/V1/dotdigital/forms/' + websiteId),
                method: 'GET',
                dataType: 'JSON',
                contentType: 'application/json',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader ('Authorization', 'Bearer ' + _this2.token);
                },
            }).done(function (response) {
                _this2.setOptions(response);
            });
        },

        setApiAccessToken: function (token) {
            this.token = token;
        },

        setBaseUrl: function (baseUrl) {
            url.setBaseUrl(baseUrl);
        }
    });
});
