<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Amazon;

use Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace\CollectionFactory
    as MarketplaceDictionaryCollectionFactory;
use Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType\CollectionFactory
    as ProductTypeDictionaryCollectionFactory;
use Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory as ProductTypeCollectionFactory;

class ProductType
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory */
    private $productTypeCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType\CollectionFactory */
    private $productTypeDictionaryCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace\CollectionFactory */
    private $marketplaceDictionaryCollectionFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory */
    private $productTypeFactory;
    /** @var \Ess\M2ePro\Model\MarketplaceFactory */
    private $marketplaceFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory */
    private $listingProductCollectionFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory */
    private $amazonConnectorDispatcherFactory;

    /** @var array */
    private $marketplaceDictionaryCache = [];
    /** @var array */
    private $marketplaceDictionaryProductTypeCache = [];

    /**
     * @param ProductTypeCollectionFactory $productTypeCollectionFactory
     * @param ProductTypeDictionaryCollectionFactory $productTypeDictionaryCollectionFactory
     * @param MarketplaceDictionaryCollectionFactory $marketplaceDictionaryCollectionFactory
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory
     * @param \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory
     * @param \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory $amazonConnectorDispatcherFactory
     */
    public function __construct(
        ProductTypeCollectionFactory $productTypeCollectionFactory,
        ProductTypeDictionaryCollectionFactory $productTypeDictionaryCollectionFactory,
        MarketplaceDictionaryCollectionFactory $marketplaceDictionaryCollectionFactory,
        \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory $amazonConnectorDispatcherFactory
    ) {
        $this->productTypeCollectionFactory = $productTypeCollectionFactory;
        $this->productTypeDictionaryCollectionFactory = $productTypeDictionaryCollectionFactory;
        $this->marketplaceDictionaryCollectionFactory = $marketplaceDictionaryCollectionFactory;
        $this->productTypeFactory = $productTypeFactory;
        $this->marketplaceFactory = $marketplaceFactory;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->amazonConnectorDispatcherFactory = $amazonConnectorDispatcherFactory;
    }

    /**
     * @param int $id
     *
     * @return \Ess\M2ePro\Model\Amazon\Dictionary\ProductType
     */
    public function getProductTypeDictionaryById(int $id): \Ess\M2ePro\Model\Amazon\Dictionary\ProductType
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType\Collection $collection */
        $collection = $this->productTypeDictionaryCollectionFactory->create();
        $collection->getSelect()->where('id = ?', $id);

        /** @var \Ess\M2ePro\Model\Amazon\Dictionary\ProductType $item */
        $item = $collection->getFirstItem();

        return $item;
    }

    /**
     * @param int $marketplaceId
     * @param string $nick
     * @param bool $allowReceive
     *
     * @return \Ess\M2ePro\Model\Amazon\Dictionary\ProductType
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getProductTypeDictionary(
        int $marketplaceId,
        string $nick,
        bool $allowReceive = true
    ): \Ess\M2ePro\Model\Amazon\Dictionary\ProductType {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType\Collection $collection */
        $collection = $this->productTypeDictionaryCollectionFactory->create()
            ->appendFilterNick($nick)
            ->appendFilterMarketplaceId($marketplaceId);

        /** @var \Ess\M2ePro\Model\Amazon\Dictionary\ProductType $item */
        $item = $collection->getFirstItem();

        if (!$item->getId() && $allowReceive) {
            $this->updateProductTypeDictionary($item, $marketplaceId, $nick);
            return $this->getProductTypeDictionary($marketplaceId, $nick, false);
        }

        return $item;
    }

    /**
     * @param int $marketplaceId
     * @param string $nick
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getProductTypeScheme(int $marketplaceId, string $nick): array
    {
        $item = $this->getProductTypeDictionary($marketplaceId, $nick);
        if (!$item->getId()) {
            return [];
        }

        return $item->getScheme();
    }

    /**
     * @param int $marketplaceId
     *
     * @return \Ess\M2ePro\Model\Amazon\Dictionary\Marketplace
     */
    public function getMarketplaceDictionary(int $marketplaceId): \Ess\M2ePro\Model\Amazon\Dictionary\Marketplace
    {
        if (isset($this->marketplaceDictionaryCache[$marketplaceId])) {
            return $this->marketplaceDictionaryCache[$marketplaceId];
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace\Collection $collection */
        $collection = $this->marketplaceDictionaryCollectionFactory->create()
            ->appendFilterMarketplaceId($marketplaceId);

        /** @var \Ess\M2ePro\Model\Amazon\Dictionary\Marketplace $item */
        $item = $collection->getFirstItem();
        $this->marketplaceDictionaryCache[$marketplaceId] = $item;

        return $item;
    }

    /**
     * @param int $marketplaceId
     * @param string $nick
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getMarketplaceDictionaryProductType(int $marketplaceId, string $nick): array
    {
        if (isset($this->marketplaceDictionaryProductTypeCache[$marketplaceId][$nick])) {
            return $this->marketplaceDictionaryProductTypeCache[$marketplaceId][$nick];
        }

        $marketplaceDictionary = $this->getMarketplaceDictionary($marketplaceId);
        if (!$marketplaceDictionary->getId()) {
            $this->marketplaceDictionaryProductTypeCache[$marketplaceId][$nick] = [];
            return [];
        }

        $productTypes = $marketplaceDictionary->getProductTypes();
        $data = !empty($productTypes[$nick]) ? $productTypes[$nick] : [];

        $this->marketplaceDictionaryProductTypeCache[$marketplaceId][$nick] = $data;
        return $data;
    }

    /**
     * @param int $marketplaceId
     * @param string $nick
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getProductTypeGroups(int $marketplaceId, string $nick): array
    {
        $data = $this->getMarketplaceDictionaryProductType($marketplaceId, $nick);
        if (empty($data)) {
            return [];
        }

        return !empty($data['groups']) && is_array($data['groups']) ?
            $data['groups'] : [];
    }

    /**
     * @param int $marketplaceId
     * @param string $nick
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getProductTypeTitle(int $marketplaceId, string $nick): string
    {
        $data = $this->getMarketplaceDictionaryProductType($marketplaceId, $nick);

        return !empty($data['title']) && is_string($data['title']) ?
            $data['title'] : 'unknown';
    }

    /**
     * @param int $id
     *
     * @return \Ess\M2ePro\Model\Amazon\Template\ProductType
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getProductTypeById(int $id): \Ess\M2ePro\Model\Amazon\Template\ProductType
    {
        $productType = $this->productTypeFactory->create();
        $productType->load($id);

        return $productType;
    }

    /**
     * @param int $marketplaceId
     * @param string $nick
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProductTypeSettings(int $marketplaceId, string $nick): array
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\Collection $collection */
        $collection = $this->productTypeCollectionFactory->create()
            ->appendFilterNick($nick)
            ->appendFilterMarketplaceId($marketplaceId);

        /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType $item */
        $item = $collection->getFirstItem();
        if (!$item->getId()) {
            return [];
        }

        return $item->getSettings('settings');
    }

    /**
     * @param \Ess\M2ePro\Model\Amazon\Dictionary\ProductType $productTypeDictionary
     * @param int $marketplaceId
     * @param string $nick
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function updateProductTypeDictionary(
        \Ess\M2ePro\Model\Amazon\Dictionary\ProductType $productTypeDictionary,
        int $marketplaceId,
        string $nick
    ): void {
        /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcher */
        $dispatcher = $this->amazonConnectorDispatcherFactory->create();

        /** @var \Ess\M2ePro\Model\Amazon\Connector\Marketplace\Get\Specifics $connector */
        $connector = $dispatcher->getConnector(
            'marketplace',
            'get',
            'specifics',
            [
                'marketplace' => $this->getMarketplaceNativeId($marketplaceId),
                'product_type_nick' => $nick,
            ]
        );

        $dispatcher->process($connector);
        $responseData = $connector->getResponseData();

        if (!$productTypeDictionary->getId()) {
            $productTypeDictionary->setMarketplaceId($marketplaceId)
                ->setNick($nick);
        }

        $productTypeDictionary->setScheme($responseData['specifics'])
            ->setTitle($this->getProductTypeTitle($marketplaceId, $nick));
        $productTypeDictionary->save();
    }

    /**
     * @param int $marketplaceId
     * @param bool $onlyValid
     *
     * @return array
     */
    public function getProductTypesInDictionary(int $marketplaceId, bool $onlyValid = false): array
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType\Collection $collection */
        $collection = $this->productTypeDictionaryCollectionFactory->create()
            ->appendFilterMarketplaceId($marketplaceId);

        if ($onlyValid) {
            $collection->appendFilterInvalid(false);
        }

        return $collection->getItems();
    }

    /**
     * @param int $marketplaceId
     *
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getMarketplaceNativeId(int $marketplaceId): int
    {
        /** @var \Ess\M2ePro\Model\Marketplace $a */
        $marketplace = $this->marketplaceFactory->create();
        $marketplace->load($marketplaceId);

        return $marketplace->getNativeId();
    }

    /**
     * @param int $marketplaceId
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getConfiguredProductTypesList(int $marketplaceId): array
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\Collection $collection */
        $collection = $this->productTypeCollectionFactory->create()
            ->appendFilterMarketplaceId($marketplaceId);

        $result = [];
        /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType $item */
        foreach ($collection->getItems() as $item) {
            $result[$item->getNick()] = true;
        }

        return $result;
    }

    /**
     * @param int $templateProductTypeId
     *
     * @return bool
     */
    public function isProductTypeUsingInProducts(int $templateProductTypeId): bool
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->listingProductCollectionFactory->create([
            'childMode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
        ]);

        $collection->getSelect()->where('template_product_type_id = ?', $templateProductTypeId);

        return (bool)$collection->getSize();
    }

    /**
     * @param int $marketplaceId
     * @param array $nicks
     *
     * @return void
     */
    public function markProductTypeDictionariesInvalid(int $marketplaceId, array $nicks): void
    {
        if (empty($nicks)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType\Collection $collection */
        $collection = $this->productTypeDictionaryCollectionFactory->create()
            ->appendFilterMarketplaceId($marketplaceId)
            ->appendFilterNicks($nicks);

        $collection->setDataToAll('invalid', 1)
            ->save();
    }

    /**
     * @param int $marketplaceId
     * @param array $nicks
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function removeProductTypeDictionaries(int $marketplaceId, array $nicks): void
    {
        if (empty($nicks)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType\Collection $collection */
        $collection = $this->productTypeDictionaryCollectionFactory->create()
            ->appendFilterMarketplaceId($marketplaceId)
            ->appendFilterNicks($nicks);

        /** @var \Ess\M2ePro\Model\Amazon\Dictionary\ProductType $item */
        foreach ($collection->getItems() as $item) {
            $item->delete();
        }
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getTimezoneShift(): int
    {
        $dateLocal = \Ess\M2ePro\Helper\Date::createDateInCurrentZone('2022-01-01');
        $dateUTC = \Ess\M2ePro\Helper\Date::createDateGmt($dateLocal->format('Y-m-d H:i:s'));

        return $dateUTC->getTimestamp() - $dateLocal->getTimestamp();
    }
}
