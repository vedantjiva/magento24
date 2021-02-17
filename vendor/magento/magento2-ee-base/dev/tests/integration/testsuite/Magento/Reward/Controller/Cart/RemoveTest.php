<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Controller\Cart;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface as CustomerData;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Test for class \Magento\Reward\Controller\Cart\Remove
 *
 * @magentoAppArea frontend
 * @magentoDataFixture Magento/Reward/_files/quote_with_reward_points.php
 */
class RemoveTest extends AbstractController
{

    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * Class dependencies initialization
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager=Bootstrap::getObjectManager();
        $customerData = $this->getTestFixture();
        /** @var Session $customerSession */
        $this->customerSession = $this->objectManager->get(
            CustomerSession::class
        );
        $this->customerSession->setCustomerDataAsLoggedIn($customerData);
        parent::setUp();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->customerSession->logout();
        parent::tearDown();
    }

    /** Test Remove Reward points
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store magento_reward/general/is_enabled  1
     * @return void
     */
    public function testExecute(): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('reward/cart/remove');
        $this->assertSessionMessages($this->equalTo([(string)__('You removed the reward points from this order.')]));
    }

    /**
     * Test GET request returns 404
     */
    public function test404NotFound(): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_GET);
        $this->dispatch('reward/cart/remove');
        $this->assert404NotFound();
    }

    /** Test with reward points disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @return void
     */
    public function testExecuteWithRewardPointsDisabled(): void
    {
        $quote = $this->objectManager->get(Session::class)->getQuote();
        $quote->setUseRewardPoints(false);
        $quote->save();
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('reward/cart/remove');
        $this->assertSessionMessages($this->equalTo([(string)__('Reward points will not be used in this order.')]));
    }

    /**
     * Gets Test Fixture.
     *
     * @throws NoSuchEntityException If customer with the specified email does not exist.
     * @throws LocalizedException
     * @return CustomerData
     */
    private function getTestFixture(): CustomerData
    {
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        return $customerRepository->get('john_smith@company.com');
    }
}
