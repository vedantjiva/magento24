<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCmsUrlRewrite\Model;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreSwitcherInterface;
use Magento\UrlRewrite\Controller\Adminhtml\Url\Rewrite;
use Magento\VersionsCms\Api\HierarchyNodeRepositoryInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\Framework\HTTP\PhpEnvironment\RequestFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Store switcher for Versions Switcher.
 */
class StoreSwitcher implements StoreSwitcherInterface
{
    /**
     * @var HierarchyNodeRepositoryInterface
     */
    private $nodeRepository;

    /**
     * @var UrlFinderInterface
     */
    private $urlFinder;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param HierarchyNodeRepositoryInterface $nodeRepository
     * @param UrlFinderInterface $urlFinder
     * @param RequestFactory $requestFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        HierarchyNodeRepositoryInterface $nodeRepository,
        UrlFinderInterface $urlFinder,
        RequestFactory $requestFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->nodeRepository = $nodeRepository;
        $this->urlFinder = $urlFinder;
        $this->requestFactory = $requestFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritdoc
     */
    public function switch(StoreInterface $fromStore, StoreInterface $targetStore, string $redirectUrl): string
    {
        $request = $this->requestFactory->create(['uri' => $redirectUrl]);
        $urlPath = ltrim($request->getPathInfo(), '/');

        if ($targetStore->isUseStoreInUrl()) {
            // Remove store code in redirect url for correct rewrite search
            $storeCode = preg_quote($targetStore->getCode() . '/', '/');
            $pattern = "@^(" . $storeCode . ")@";
            $urlPath = preg_replace($pattern, '', $urlPath);
        }

        $searchCriteria = $this->searchCriteriaBuilder->addFilter(HierarchyNodeInterface::REQUEST_URL, $urlPath)
            ->addFilter(HierarchyNodeInterface::SCOPE, 'store')
            ->addFilter(HierarchyNodeInterface::SCOPE_ID, $fromStore->getId())
            ->create();
        $nodes = $this->nodeRepository->getList($searchCriteria)
            ->getItems();
        $node = current($nodes);

        if ($node) {
            $fromStoreUrlRewrite = $this->urlFinder->findOneByData(
                [
                    UrlRewrite::ENTITY_ID => $node['page_id'],
                    UrlRewrite::STORE_ID => $fromStore->getId(),
                    UrlRewrite::ENTITY_TYPE => Rewrite::ENTITY_TYPE_CMS_PAGE
                ]
            );

            if ($fromStoreUrlRewrite) {
                // if in "From Store" was a rewrite,
                // let's change url_path to "Target Store" url rewrite if it is exists.
                $redirectUrl = $targetStore->getBaseUrl();
                // look for url rewrite match on the target store
                $targetStoreUrlRewrite = $this->urlFinder->findOneByData(
                    [
                        UrlRewrite::ENTITY_ID => $node['page_id'],
                        UrlRewrite::STORE_ID => $targetStore->getId(),
                        UrlRewrite::ENTITY_TYPE => Rewrite::ENTITY_TYPE_CMS_PAGE
                    ]
                );

                if ($targetStoreUrlRewrite) {
                    $redirectUrl .= $targetStoreUrlRewrite->getRequestPath();
                }
            }
        }

        return $redirectUrl;
    }
}
