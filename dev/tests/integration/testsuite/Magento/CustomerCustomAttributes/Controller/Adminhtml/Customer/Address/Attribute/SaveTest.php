<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 */
class SaveTest extends AbstractBackendController
{
    private static $regionFrontendLabel = 'New region label';

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->attributeRepository = $this->_objectManager->get(AttributeRepositoryInterface::class);
    }

    /**
     * Tests that RegionId frontend label equal to Region frontend label.
     *
     * RegionId is hidden frontend input attribute and isn't available for updating via admin panel,
     * but frontend label of this attribute is visible in address forms as Region label.
     * So frontend label for RegionId should be synced with frontend label for Region attribute, which is
     * available for updating.
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testRegionFrontendLabelUpdate(): void
    {
        $params = $this->getRequestData();
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPostValue($params);

        $this->dispatch('backend/admin/customer_address_attribute/save');

        /**
         * Check that errors was generated and set to session
         */
        self::assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);

        $regionIdAttribute = $this->attributeRepository->get(
            'customer_address',
            AddressInterface::REGION_ID
        );

        self::assertEquals(self::$regionFrontendLabel, $regionIdAttribute->getDefaultFrontendLabel());
    }

    /**
     * Tests that controller validate file extensions.
     *
     * @var string $fileExtension
     *
     * @return void
     * @dataProvider fileExtensionsDataProvider
     */
    public function testFileExtensions(string $fileExtension): void
    {
        $params = $this->getRequestNewAttributeData($fileExtension);
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPostValue($params);

        $this->dispatch('backend/admin/customer_address_attribute/save');

        $this->assertSessionMessages(
            $this->equalTo(['Please correct the value for file extensions.'])
        );
    }

    /**
     * @return array
     */
    public function fileExtensionsDataProvider(): array
    {
        return [
            ['php'],
            ['svg'],
            ['php3'],
            ['php4'],
            ['php5'],
            ['php7'],
            ['htaccess'],
            ['jsp'],
            ['pl'],
            ['py'],
            ['asp'],
            ['sh'],
            ['cgi'],
            ['htm'],
            ['html'],
            ['phtml'],
            ['shtml'],
            ['phpt'],
            ['pht'],
            ['xml'],
        ];
    }

    /**
     * Gets request params.
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getRequestData(): array
    {
        $regionAttribute = $this->attributeRepository->get(
            'customer_address',
            AddressInterface::REGION
        );

        return [
            'attribute_id' => $regionAttribute->getAttributeId(),
            'frontend_label' => [self::$regionFrontendLabel],
            'form_key' => $this->_objectManager->get(FormKey::class)->getFormKey(),
        ];
    }

    /**
     * Gets request params.
     *
     * @var string $fileExtension
     *
     * @return array
     */
    private function getRequestNewAttributeData(string $fileExtension): array
    {
        return [
            'attribute_code' => 'new_file',
            'frontend_label' => ['new_file'],
            'frontend_input' => 'file',
            'file_extensions' => $fileExtension,
            'sort_order' => 1,
            'form_key' => $this->_objectManager->get(FormKey::class)->getFormKey(),
        ];
    }

    /**
     * Tests that controller validate unique option values for attribute.
     *
     * @return void
     */
    public function testUniqueOption()
    {
        $params = $this->getRequestNewAttributeDataWithNotUniqueOptions();
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPostValue($params);

        $this->dispatch('backend/admin/customer_address_attribute/save');

        $this->assertSessionMessages(
            $this->equalTo(['The value of Admin must be unique.'])
        );
    }

    /**
     * @return array
     */
    private function getRequestNewAttributeDataWithNotUniqueOptions(): array
    {
        return [
            'attribute_code' => 'test_dropdown',
            'frontend_label' => ['test_dropdown'],
            'frontend_input' => 'select',
            //@codingStandardsIgnoreStart
            'serialized_options' => '["option%5Border%5D%5Boption_0%5D=1&option%5Bvalue%5D%5Boption_0%5D%5B0%5D=1&option%5Bvalue%5D%5Boption_0%5D%5B1%5D=1&option%5Bdelete%5D%5Boption_0%5D=","option%5Border%5D%5Boption_1%5D=2&option%5Bvalue%5D%5Boption_1%5D%5B0%5D=1&option%5Bvalue%5D%5Boption_1%5D%5B1%5D=1&option%5Bdelete%5D%5Boption_1%5D="]',
            //@codingStandardsIgnoreEnd
            'sort_order' => 1,
        ];
    }

    /**
     * Tests that controller validate empty option values for attribute.
     *
     * @return void
     */
    public function testEmptyOption()
    {
        $params = $this->getRequestNewAttributeDataWithEmptyOption();
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPostValue($params);

        $this->dispatch('backend/admin/customer_address_attribute/save');

        $this->assertSessionMessages(
            $this->equalTo(['The value of Admin scope can\'t be empty.'])
        );
    }

    /**
     * @return array
     */
    private function getRequestNewAttributeDataWithEmptyOption(): array
    {
        return [
            'attribute_code' => 'test_dropdown',
            'frontend_label' => ['test_dropdown'],
            'frontend_input' => 'select',
            //@codingStandardsIgnoreStart
            'serialized_options' => '["option%5Border%5D%5Boption_0%5D=1&option%5Bvalue%5D%5Boption_0%5D%5B0%5D=&option%5Bvalue%5D%5Boption_0%5D%5B1%5D=&option%5Bdelete%5D%5Boption_0%5D="]',
            //@codingStandardsIgnoreEnd
            'sort_order' => 1,
        ];
    }

    /**
     * Tests postcode input validation
     *
     * When postcode input validation set to null the associated attribute max_length
     * and min_length also become null
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testPostcodeInputValidation(): void
    {
        $params = $this->getRequestPostcodeAttributeData();
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPostValue($params);

        $this->dispatch('backend/admin/customer_address_attribute/save');

        /**
         * Check that errors was generated and set to session
         */
        self::assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);

        $postcodeAttribute = $this->attributeRepository->get(
            'customer_address',
            AddressInterface::POSTCODE
        );

        self::assertEmpty($postcodeAttribute->getValidationRules());
    }

    /**
     * Gets request postcode attribute data
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getRequestPostcodeAttributeData(): array
    {
        $postcodeAttribute = $this->attributeRepository->get(
            'customer_address',
            AddressInterface::POSTCODE
        );

        return [
            'attribute_id' => $postcodeAttribute->getAttributeId(),
            'attribute_code' => $postcodeAttribute->getAttributeCode(),
            'frontend_label' => ['Zip/Postal Code'],
            'frontend_input' => $postcodeAttribute->getFrontendInput(),
            'sort_order' => 110,
            'input_validation' => '',
            'min_text_length' => 4,
            'max_text_length' => 7,
            'form_key' => $this->_objectManager->get(FormKey::class)->getFormKey(),
        ];
    }
}
