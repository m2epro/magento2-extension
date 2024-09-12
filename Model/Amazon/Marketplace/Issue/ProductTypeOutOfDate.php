<?php

namespace Ess\M2ePro\Model\Amazon\Marketplace\Issue;

class ProductTypeOutOfDate implements \Ess\M2ePro\Model\Issue\LocatorInterface
{
    private \Magento\Backend\Model\UrlInterface $urlBuilder;
    private \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper;
    private \Ess\M2ePro\Model\Issue\DataObjectFactory $issueFactory;
    private \Ess\M2ePro\Helper\Component\Amazon $amazonComponentHelper;
    private \Ess\M2ePro\Model\Amazon\Dictionary\ProductType\Repository $dictionaryProductTypeRepository;
    private \Ess\M2ePro\Model\Amazon\Marketplace\Repository $amazonMarketplaceRepository;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Marketplace\Issue\ProductTypeOutOfDate\Cache
     */
    private ProductTypeOutOfDate\Cache $cache;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Dictionary\ProductType\Repository $dictionaryProductTypeRepository,
        \Ess\M2ePro\Model\Amazon\Marketplace\Repository $amazonMarketplaceRepository,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper,
        \Ess\M2ePro\Model\Issue\DataObjectFactory $issueFactory,
        \Ess\M2ePro\Helper\Component\Amazon $amazonComponentHelper,
        \Ess\M2ePro\Model\Amazon\Marketplace\Issue\ProductTypeOutOfDate\Cache $cache
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->amazonViewHelper = $amazonViewHelper;
        $this->issueFactory = $issueFactory;
        $this->amazonComponentHelper = $amazonComponentHelper;
        $this->dictionaryProductTypeRepository = $dictionaryProductTypeRepository;
        $this->amazonMarketplaceRepository = $amazonMarketplaceRepository;
        $this->cache = $cache;
    }

    public function getIssues(): array
    {
        if (!$this->isNeedProcess()) {
            return [];
        }

        if (!$this->isExistOutOfDateProductTypes()) {
            return [];
        }

        return [
            $this->issueFactory->createNoticeDataObject(
                __(
                    'M2E Pro requires action: Amazon marketplace data needs to be synchronized.
            Please update Amazon marketplaces.'
                ),
                __(
                    'Data for some Product Types was changed on Amazon.
 To avoid errors and have access to the latest updates,
please use the <b>Refresh Amazon Data</b> button in Amazon > <a href="%url" target="_blank">Product Types</a>
and re-save the Product Types you have configured.',
                    ['url' => $this->urlBuilder->getUrl('m2epro/amazon_template_productType/index')]
                ),
                null
            ),
        ];
    }

    private function isExistOutOfDateProductTypes(): bool
    {
        $outdatedMarketplaces = $this->cache->get();
        if ($outdatedMarketplaces !== null) {
            return $outdatedMarketplaces;
        }

        $activeMarketplaces = [];
        foreach ($this->amazonMarketplaceRepository->findWithAccounts() as $marketplace) {
            $activeMarketplaces[(int)$marketplace->getId()] = $marketplace;
        }

        $outdatedMarketplaces = [];
        foreach ($this->dictionaryProductTypeRepository ->findValidOutOfDate() as $productType) {
            if (!isset($activeMarketplaces[$productType->getMarketplaceId()])) {
                continue;
            }

            if (isset($outdatedMarketplaces[$productType->getMarketplaceId()])) {
                continue;
            }

            $outdatedMarketplaces[$productType->getMarketplaceId()] = true;
        }

        $this->cache->set($result = !empty($outdatedMarketplaces));

        return $result;
    }

    public function isNeedProcess(): bool
    {
        return $this->amazonViewHelper->isInstallationWizardFinished() &&
            $this->amazonComponentHelper->isEnabled();
    }
}
