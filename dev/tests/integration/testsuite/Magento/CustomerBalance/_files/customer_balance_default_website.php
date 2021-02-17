<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Customer;

$objectManager = Bootstrap::getObjectManager();
$customer = $objectManager->create(\Magento\Customer\Model\Customer::class);
/** @var CustomerRegistry $customerRegistry */
$customerRegistry = $objectManager->get(CustomerRegistry::class);
/** @var Customer $customer */
$customer->setWebsiteId(1)
    ->setId(1)
    ->setEmail('customer@example.com')
    ->setPassword('password')
    ->setGroupId(1)
    ->setStoreId(1)
    ->setIsActive(1)
    ->setPrefix('Mr.')
    ->setFirstname('John')
    ->setMiddlename('A')
    ->setLastname('Smith')
    ->setSuffix('Esq.')
    ->setDefaultBilling(1)
    ->setDefaultShipping(1)
    ->setTaxvat('12')
    ->setGender(0);

$customer->isObjectNew(true);
$customer->save();
$customerRegistry->remove($customer->getId());

/** @var $customerBalance Magento\CustomerBalance\Model\Balance */
$customerBalance = $objectManager->create(
    \Magento\CustomerBalance\Model\Balance::class
);
$customerBalance->setCustomerId(
    $customer->getId()
);

$customerBalance->setAmountDelta(50);
$customerBalance->setWebsiteId(
    Bootstrap::getObjectManager()->get(
        \Magento\Store\Model\StoreManagerInterface::class
    )->getStore()->getWebsiteId()
);
$customerBalance->save();
