<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Staging;

use Magento\Integration\Api\AdminTokenServiceInterface;
use Magento\Staging\Model\ResourceModel\Update;
use Magento\Staging\Model\UpdateFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Preview Simple product
 */
class PreviewSimpleProductTest extends GraphQlAbstract
{
    /** @var AdminTokenServiceInterface */
    private $tokenService;

    /** @var UpdateFactory */
    private $updateFactory;

    /** @var Update */
    private $updateResourceModel;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->tokenService = $objectManager->get(AdminTokenServiceInterface::class);
        $this->updateFactory = $objectManager->get(UpdateFactory::class);
        $this->updateResourceModel = $objectManager->get(Update::class);
    }

    /**
     * Preview simple product with a price change and name change with admin token and preview version
     *
     * @magentoApiDataFixture Magento/CatalogStaging/_files/simple_product_staged_changes.php
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testPreviewSimpleProductWithAdminToken()
    {
        $update = $this->updateFactory->create();
        $this->updateResourceModel->load($update, 'Product Update Test', 'name');
        $version = $update->getId();

        $headerMap = $this->getHeaderMapWithAdminToken(
            'customRoleUser',
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

        //preview version header
        $headerMap['Preview-Version'] = $version;

        $query
            = <<<QUERY
{
   products
   (filter:{sku:{eq:"simple"}})
    {
      items{
        sku
        name
        price_range{
          maximum_price{
            final_price{
              value
            }
            regular_price{
              value
            }
        }
        minimum_price{
          final_price{
            value
          }
          regular_price{
            value
          }
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $this->assertArrayHasKey('products', $response);
        $product = $response['products']['items'][0];
        $this->assertEquals('Updated Product', $product['name']);
        $this->assertEquals(5.99, $product['price_range']['maximum_price']['final_price']['value']);
        $this->assertEquals(5.99, $product['price_range']['minimum_price']['regular_price']['value']);
    }

    /**
     * Preview a product in multiple stores
     *
     * @magentoApiDataFixture Magento/CatalogStaging/_files/simple_product_staged_changes_second_store.php
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testPreviewSimpleProductSecondStore()
    {
        $update = $this->updateFactory->create();
        $this->updateResourceModel->load($update, 'Product Update Second Store', 'name');
        $version = $update->getId();

        $headerMap = $this->getHeaderMapWithAdminToken(
            'customRoleUser',
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

        //preview version header
        $headerMap['Preview-Version'] = $version;

        //pass store in the header
        $headerMap['Store'] = 'fixture_second_store';

        $query
            = <<<QUERY
{
   products
   (filter:{sku:{eq:"simplep1"}})
    {
      items{
        sku
        name
        price_range{
          maximum_price{
            final_price{
              value
            }
            regular_price{
              value
            }
        }
        minimum_price{
          final_price{
            value
          }
          regular_price{
            value
          }
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $this->assertArrayHasKey('products', $response);
        $product = $response['products']['items'][0];
        $this->assertEquals('Updated Product Name store2', $product['name']);
        $this->assertEquals(40, $product['price_range']['maximum_price']['final_price']['value']);
        $this->assertEquals(40, $product['price_range']['minimum_price']['regular_price']['value']);

        //verify for default store view
        $headerMap['Store'] = 'default';
        $response = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $this->assertArrayHasKey('products', $response);
        $product = $response['products']['items'][0];
        $this->assertEquals('Simple Product 1', $product['name']);
    }

    /**
     * Search is not supported in the preview context
     *
     * @magentoApiDataFixture Magento/CatalogStaging/_files/simple_product_staged_changes.php
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testProductSearchInPreviewMode()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Search is not supported in preview mode.');

        $update = $this->updateFactory->create();
        $this->updateResourceModel->load($update, 'Product Update Test', 'name');
        $version = $update->getId();

        $headerMap = $this->getHeaderMapWithAdminToken(
            'customRoleUser',
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

        //preview version header
        $headerMap['Preview-Version'] = $version;

        $query
            = <<<QUERY
{
    products(
     search : "simple"
        filter:
        {
          price:{from:"5"}
        }
        pageSize:2
        currentPage:1
        sort:
       {
        price:DESC
       }
    )
    {
        items
         {
           sku
           price_range
           {maximum_price
            {final_price{value}}
          }
           name
          id
         }
        total_count
        page_info
        {
          page_size
          current_page
        }
    }
}
QUERY;
        $this->graphQlQuery($query, [], '', $headerMap);
    }

    /**
     * Attempt preview without proper authentication
     *
     * @magentoApiDataFixture Magento/CatalogStaging/_files/simple_product_staged_changes.php
     */
    public function testPreviewSimpleProductWithNoAdminToken()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current user isn\'t authorized.');

        $update = $this->updateFactory->create();
        $this->updateResourceModel->load($update, 'Product Update Test', 'name');
        $version = $update->getId();

        $headers = ['Preview-Version' => $version];

        $query
            = <<<QUERY
{
  products(filter: {sku: {eq: "simple"}}) {
    items {
      sku
      name
      price_range {
        minimum_price {
          final_price {
            value
          }
        }
      }
    }
  }
}
QUERY;
        $this->graphQlQuery($query, [], '', $headers);
    }

    /**
     * Query product without preview header and get current version
     *
     * @magentoApiDataFixture Magento/CatalogStaging/_files/simple_product_staged_changes.php
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testQuerySimpleProductWithoutPreview()
    {
        $headerMap = $this->getHeaderMapWithAdminToken(
            'customRoleUser',
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

        $query = <<<QUERY
{
   products
   (filter:{sku:{eq:"simple"}})
    {
      items{
        sku
        name
        price_range{
          maximum_price{
            final_price{
              value
            }
            regular_price{
              value
            }
        }
        minimum_price{
          final_price{
            value
          }
          regular_price{
            value
          }
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query, [], '', $headerMap);

        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $this->assertArrayHasKey('products', $response);
        $product = $response['products']['items'][0];
        $this->assertEquals('Simple Product 1', $product['name']);
        $this->assertEquals(10.00, $product['price_range']['maximum_price']['final_price']['value']);
        $this->assertEquals(10.00, $product['price_range']['minimum_price']['regular_price']['value']);
    }

    /**
     * Preview a disabled product that gets enabled in a future update
     *
     * @magentoApiDataFixture Magento/CatalogStaging/_files/disabled_simple_product_staged_for_changes.php
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testPreviewDisabledStagedSimpleProduct()
    {
        $this->markTestSkipped(
            'This test will stay skipped until MC-29898 and or MC-29862 is resolved'
        );
        $update = $this->updateFactory->create();
        $this->updateResourceModel->load($update, 'Disabled Product Staging Test', 'name');
        $version = $update->getId();

        $headerMap = $this->getHeaderMapWithAdminToken(
            'customRoleUser',
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

        //preview version header
        $headerMap['Preview-Version'] = $version;

        $query = <<<QUERY
{
   products
   (filter:{sku:{eq:"disabled-simple"}})
    {
      items{
        sku
        name
        stock_status
        price_range{
          maximum_price{
            final_price{value}
            regular_price{value}
        }
        minimum_price{
          final_price{value}
          regular_price{value}
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query, [], '', $headerMap);

        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $this->assertArrayHasKey('products', $response);
        $this->assertNotEmpty($response['products']['items'], 'No products returned');
        $product = $response['products']['items'][0];
        $this->assertEquals('Enabled Simple Product 1', $product['name']);
        $this->assertEquals(45, $product['price_range']['maximum_price']['final_price']['value']);
        $this->assertEquals(45, $product['price_range']['minimum_price']['regular_price']['value']);
        $this->assertEquals('IN_STOCK', $product['stock_status']);
    }

    /**
     * Get admin access token for Authorization
     *
     * @param string $username
     * @param string $password
     * @return array
     */
    private function getHeaderMapWithAdminToken(
        string $username,
        string $password
    ): array {
        $adminToken = $this->tokenService->createAdminAccessToken(
            $username,
            $password
        );
        $headerMap = ['Authorization' => 'Bearer ' . $adminToken];
        return $headerMap;
    }
}
