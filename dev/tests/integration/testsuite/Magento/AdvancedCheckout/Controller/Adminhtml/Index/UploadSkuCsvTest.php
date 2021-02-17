<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Controller\Adminhtml\Index;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Filesystem;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Checks upload sku csv controller
 *
 * @see \Magento\AdvancedCheckout\Controller\Adminhtml\Index\UploadSkuCsv
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class UploadSkuCsvTest extends AbstractBackendController
{
    /** @var Session */
    private $session;

    /** @var Data */
    private $helper;

    /** @var CartRepositoryInterface */
    private $cartRepository;

    /** @var Filesystem */
    private $filesystem;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->session = $this->_objectManager->get(Session::class);
        $this->helper = $this->_objectManager->get(Data::class);
        $this->cartRepository = $this->_objectManager->get(CartRepositoryInterface::class);
        $this->filesystem = $this->_objectManager->get(Filesystem::class);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     *
     * @return void
     */
    public function testUploadItemsFromRequest(): void
    {
        $postData = [
            'sku_file_uploaded' => '0',
            'add_by_sku' => [
                'simple2' => ['qty' => 1],
            ],
            'customer' => 1,
            'store' => 1,
        ];
        $this->session->loginById(1);
        $this->dispatchRequestWithData($postData);
        $this->assertQuoteItems(1, ['simple2']);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testUploadWithError(): void
    {
        $postData = [
            'sku_file_uploaded' => '0',
            'add_by_sku' => [
                'unexisting_sku' => ['qty' => 1],
            ],
            'customer' => 1,
            'store' => 1,
        ];
        $this->session->loginById(1);
        $this->dispatchRequestWithData($postData);
        $items = $this->helper->getFailedItems();
        $this->assertNotNull($items);
        $item = reset($items);
        $this->assertEquals('unexisting_sku', $item->getSku());
        $this->assertEquals(Data::ADD_ITEM_STATUS_FAILED_SKU, $item->getCode());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple_xss.php
     *
     * @return void
     */
    public function testUploadFile(): void
    {
        $_FILES['sku_file'] = $this->prepareFile('order_by_sku.csv', 'text/csv');
        $this->session->loginById(1);
        $this->dispatchRequestWithData(
            [Data::REQUEST_PARAMETER_SKU_FILE_IMPORTED_FLAG => true],
            ['customer' => 1, 'store' => 1]
        );
        $this->assertQuoteItems(1, ['simple2', 'product-with-xss']);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testUploadInvalidExtension(): void
    {
        $this->markTestSkipped('Blocked by MC-30527');
        //please provide correct message after bug fixing
        $expectedErrorMessage = '';
        $this->session->loginById(1);
        $_FILES['sku_file'] = $this->prepareFile('image.jpg', 'image/jpeg');
        $this->dispatchRequestWithData(
            [Data::REQUEST_PARAMETER_SKU_FILE_IMPORTED_FLAG => true],
            ['customer' => 1, 'store' => 1]
        );
        $this->assertSessionMessages($this->stringContains($expectedErrorMessage));
    }

    /**
     * @return void
     */
    public function testWithoutCustomer(): void
    {
        $this->dispatchRequestWithData([]);
        $this->assertRedirect($this->stringContains('customer/index'));
    }

    /**
     * Dispatch request with params
     *
     * @param array $post
     * @param array $params
     * @return void
     */
    private function dispatchRequestWithData(array $post, array $params = []): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setParams($params);
        $this->getRequest()->setPostValue($post);
        $this->dispatch('backend/checkout/index/uploadSkuCsv/');
    }

    /**
     * Prepare file
     *
     * @param string $fileName
     * @param string $type
     * @return array
     */
    private function prepareFile(string $fileName, string $type): array
    {
        $tmpDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::SYS_TMP);
        $fixtureDir = realpath(__DIR__ . '/../../../_files');
        $filePath = $tmpDirectory->getAbsolutePath($fileName);
        copy($fixtureDir . DIRECTORY_SEPARATOR . $fileName, $filePath);

        return [
            'name' => $fileName,
            'type' => $type,
            'tmp_name' => $filePath,
            'error' => 0,
            'size' => filesize($filePath),
        ];
    }

    /**
     * Assert quote items
     *
     * @param int $customerId
     * @param array $itemsSkus
     * @return void
     */
    private function assertQuoteItems(int $customerId, array $itemsSkus): void
    {
        $quote = $this->cartRepository->getForCustomer($customerId);
        $quoteItems = $quote->getItemsCollection()->addFieldToFilter(ProductInterface::SKU, ['in' => $itemsSkus]);
        $this->assertCount(count($itemsSkus), $quoteItems);
    }
}
