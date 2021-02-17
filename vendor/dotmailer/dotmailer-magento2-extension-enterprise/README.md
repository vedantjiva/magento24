 Engagement Cloud for Magento Commerce
 ======
 
[![license](https://img.shields.io/github/license/mashape/apistatus.svg)](LICENSE.md)

## Description

This extension provides additional features for merchants running Magento Commerce (previously Magento Enterprise Edition). It must be used alongside the main [Engagement Cloud for Magento 2 extension](https://github.com/dotmailer/dotmailer-magento2-extension). 

- [Full support documentation and setup guides](https://support.dotmailer.com/hc/en-gb/articles/216701227-Enterprise-data-sync-options)

## Compatibility

The latest version of this module is compatible with:

- Magento >= 2.3
- Dotdigitalgroup Email >= 4.8.0

## 1.2.0-RC1

###### What's new
- Merchants can now use Page Builder to embed pages and forms from Engagement Cloud. Form submissions can be captured and stored as Magento newsletter subscribers. 

_This is a preview release. It requires v4.7.0-RC2 of our Email module to function correctly. v1.2.0 of this Enterprise module will no longer be compatible with Magento 2.2._

## 1.0.7 

###### Fixes

- We've optimised the plugin that is triggered when creating new customer segments, to resolve possible 'out of memory' errors for merchants with large customer databases.

## 1.0.6

###### Fixes
- We've fixed a regression introduced in 1.0.5, which could cause an error when running the syncs for merchants who had not mapped enterprise data fields.

## 1.0.5

###### What's new
- We've refactored the plugin that sends enterprise data fields to Engagement Cloud, in line with improvements made to our core module.
- We've made some minor improvements to the code, as per Magento coding standards. 

## 1.0.4

###### Bug fix
- We've fixed a bug with the syncing of customer reward points and segment data.
- We've updated a misspelled config key. Merchants are advised to auto-map data fields again following this change.

## V1.0.3

###### Improvements
- We've added support for Magento 2.3.1

## V1.0.2

###### Improvements
- We've added the Magento_Store module as a new dependency
- We've added a foreign key to the email_order entity using Magento's foreign key framework

###### Fixes
- We've fixed a method name that had been changed on the Community version

## V1.0.1

###### Bug fix
- We've fixed the method name that had been changed on the Community version

## V1.0.0

Available additional data fields to be mapped: 

- Reward Points
- Reward Amount
- Reward Expiration Date 		
- Reward Last Used Date 
- Customer Segments
