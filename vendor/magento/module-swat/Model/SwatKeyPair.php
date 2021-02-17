<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Swat\Model;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Base64Json;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Swat\Api\Data\SwatKeyPairInterface;
use phpseclib\Crypt\RSA;

/**
 * Model class for SWAT key-pair
 */
class SwatKeyPair implements SwatKeyPairInterface
{
    const CONFIG_RSA_PAIR_PATH = 'swat/rsa_keypair';
    const CONFIG_JWT_ALG = 'swat/jwt/alg';
    const CONFIG_JWKS_PATH = 'swat/jwks';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var WriterInterface  */
    private $configWriter;

    /** @var EncryptorInterface */
    private $encryptor;

    /** @var TypeListInterface */
    private $cacheTypeList;

    /** @var Base64Json */
    private $base64Json;

    /** @var Json */
    private $json;

    /** @var array */
    private $keyPair;

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param EncryptorInterface $encryptor
     * @param TypeListInterface $cacheTypeList
     * @param Base64Json $base64Json
     * @param Json $json
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        EncryptorInterface $encryptor,
        TypeListInterface $cacheTypeList,
        Base64Json $base64Json,
        Json $json
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->encryptor = $encryptor;
        $this->cacheTypeList = $cacheTypeList;
        $this->base64Json = $base64Json;
        $this->json = $json;
    }

    /**
     * @inheritDoc
     */
    public function getPublicKey(): string
    {
        if ($this->keyPair === null) {
            $this->loadKeyPair();
        }
        return $this->keyPair['publickey'];
    }

    /**
     * @inheritDoc
     */
    public function getPrivateKey(): string
    {
        if ($this->keyPair === null) {
            $this->loadKeyPair();
        }
        return $this->keyPair['privatekey'];
    }

    /**
     * @inheritDoc
     */
    public function getJwks(): array
    {
        if ($this->scopeConfig->isSetFlag(self::CONFIG_JWKS_PATH)) {
            $jwksJson = $this->encryptor->decrypt($this->scopeConfig->getValue(self::CONFIG_JWKS_PATH));
            return $this->json->unserialize($jwksJson);
        }

        $rsa = new Rsa();
        $parsePublicKey = $rsa->_parseKey($this->getPublicKey(), RSA::PUBLIC_FORMAT_PKCS1);
        $jwks = [
            'keys' => [
                [
                    'kid' => 1,
                    'alg' => $this->scopeConfig->getValue(self::CONFIG_JWT_ALG),
                    'kty' => 'RSA',
                    'n' => $parsePublicKey['modulus']->toHex(),
                    'e' => $parsePublicKey['publicExponent']->toHex()
                ]
            ]
        ];
        $jwksJson = $this->json->serialize($jwks);
        $this->configWriter->save(self::CONFIG_JWKS_PATH, $this->encryptor->encrypt($jwksJson));
        // clear config cache so new values will be loaded
        $this->cacheTypeList->cleanType('config');

        return $jwks;
    }

    /**
     * Loads the key pair
     */
    private function loadKeyPair()
    {
        // Check config for rsa key pair and create if necessary
        if ($this->scopeConfig->isSetFlag(self::CONFIG_RSA_PAIR_PATH)) {
            $keyPairJson = $this->encryptor->decrypt($this->scopeConfig->getValue(self::CONFIG_RSA_PAIR_PATH));
            $this->keyPair = $this->base64Json->unserialize($keyPairJson);
        } else {
            $rsa = new RSA();
            $this->keyPair = $rsa->createKey();
            $keyPairJson = $this->base64Json->serialize($this->keyPair);
            $this->configWriter->save(self::CONFIG_RSA_PAIR_PATH, $this->encryptor->encrypt($keyPairJson));
            $this->cacheTypeList->cleanType('config');
        }
    }
}
