<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Swat\Controller\Key;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Swat\Model\SwatKeyPair;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;
use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;

/**
 * Test class for \Magento\Swat\Controller\Key\Index
 */
class IndexTest extends AbstractController
{

    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Json */
    private $json;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->json = $this->objectManager->get(Json::class);
        parent::setUp();
    }

    public function testExecuteWithoutJwksWithoutKey()
    {
        $this->dispatch('swat/key');
        $jsonBody = $this->getResponse()->getBody();
        $response = $this->json->unserialize($jsonBody);
        $this->assertArrayHasKey('keys', $response);
        $this->assertCount(1, $response['keys']);
        $this->assertCount(5, $response['keys'][0]);
        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $scopeConfig->clean();
        $this->assertTrue($scopeConfig->isSetFlag(SwatKeyPair::CONFIG_JWKS_PATH));
    }

    public function testExecuteWithoutJwksWithKey()
    {
        // create and store key pair
        $rsa = new RSA();
        $keyPair = $rsa->createKey();
        $keyPairJson = $this->objectManager->get(Json::class)->serialize($keyPair);
        $encryptedKeyPair = $this->objectManager->get(Encryptor::class)->encrypt($keyPairJson);
        $this->objectManager->get(WriterInterface::class)->save(SwatKeyPair::CONFIG_RSA_PAIR_PATH, $encryptedKeyPair);
        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $scopeConfig->clean();

        $this->dispatch('swat/key');
        $jsonBody = $this->getResponse()->getBody();
        $response = $this->json->unserialize($jsonBody);
        $this->assertArrayHasKey('keys', $response);
        $this->assertCount(1, $response['keys']);
        $this->assertCount(5, $response['keys'][0]);
        $scopeConfig->clean();
        $this->assertTrue($scopeConfig->isSetFlag(SwatKeyPair::CONFIG_JWKS_PATH));

        // verify created key and returned key are the same
        $e = $response['keys'][0]['e'];
        $n = $response['keys'][0]['n'];
        $retrieveRsa = new RSA();
        $retrieveRsa->loadKey([
            'e' => new BigInteger($e, 16),
            'n' => new BigInteger($n, 16)
        ]);
        $retrieveRsa->setPublicKey();
        $this->assertEquals($keyPair['publickey'], $retrieveRsa->getPublicKey());
    }

    public function testExecuteWithJwks()
    {
        $jwksJson = $this->objectManager->get(Json::class)->serialize(['test' => 'jwks']);
        $configWriter = $this->objectManager->get(WriterInterface::class);
        $configWriter->save(
            SwatKeyPair::CONFIG_JWKS_PATH,
            $this->objectManager->get(Encryptor::class)->encrypt($jwksJson)
        );
        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $scopeConfig->clean();
        $this->dispatch('swat/key');
        $jsonBody = $this->getResponse()->getBody();
        $response = $this->json->unserialize($jsonBody);
        $this->assertArrayHasKey('test', $response);
        $this->assertEquals('jwks', $response['test']);
    }
}
