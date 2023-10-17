<?php

namespace Ess\M2ePro\Helper\Component\Amazon;

use Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace\CollectionFactory
    as MarketplaceDictionaryCollectionFactory;
use Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType\CollectionFactory
    as ProductTypeDictionaryCollectionFactory;
use Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory as ProductTypeCollectionFactory;

class ProductType
{
    /** @var ProductTypeCollectionFactory */
    private $productTypeCollectionFactory;
    /** @var ProductTypeDictionaryCollectionFactory */
    private $productTypeDictionaryCollectionFactory;
    /** @var MarketplaceDictionaryCollectionFactory */
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
    /** @var \Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping\Suggester */
    private $attributesSuggester;
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory */
    private $marketplaceCollectionFactory;

    public function __construct(
        ProductTypeCollectionFactory $productTypeCollectionFactory,
        ProductTypeDictionaryCollectionFactory $productTypeDictionaryCollectionFactory,
        MarketplaceDictionaryCollectionFactory $marketplaceDictionaryCollectionFactory,
        \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory $amazonConnectorDispatcherFactory,
        \Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping\Suggester $attributesSuggester,
        \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $marketplaceCollectionFactory
    ) {
        $this->productTypeCollectionFactory = $productTypeCollectionFactory;
        $this->productTypeDictionaryCollectionFactory = $productTypeDictionaryCollectionFactory;
        $this->marketplaceDictionaryCollectionFactory = $marketplaceDictionaryCollectionFactory;
        $this->productTypeFactory = $productTypeFactory;
        $this->marketplaceFactory = $marketplaceFactory;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->amazonConnectorDispatcherFactory = $amazonConnectorDispatcherFactory;
        $this->attributesSuggester = $attributesSuggester;
        $this->marketplaceCollectionFactory = $marketplaceCollectionFactory;
    }

    /**
     * @param int $id
     *
     * @return \Ess\M2ePro\Model\Amazon\Dictionary\ProductType
     */
    public function getProductTypeDictionaryById(int $id): \Ess\M2ePro\Model\Amazon\Dictionary\ProductType
    {
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
     * @param bool $onlyRequired
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getProductTypeScheme(int $marketplaceId, string $nick, bool $onlyRequired = false): array
    {
        $item = $this->getProductTypeDictionary($marketplaceId, $nick);
        if (!$item->getId()) {
            return [];
        }

        if (!$onlyRequired) {
            return $item->getScheme();
        }

        $scheme = [];
        foreach ($item->getScheme() as $attribute) {
            if ($attribute['validation_rules']['is_required']) {
                $scheme[] = $attribute;
            }
        }

        return $scheme;
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
     * @param array|null $onlyForAttributes
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getProductTypeGroups(int $marketplaceId, string $nick, array $onlyForAttributes = []): array
    {
        $data = $this->getMarketplaceDictionaryProductType($marketplaceId, $nick);
        if (empty($data)) {
            return [];
        }

        $groups = !empty($data['groups']) && is_array($data['groups']) ? $data['groups'] : [];
        if ($onlyForAttributes === []) {
            return $groups;
        }

        $groupNicks = array_unique(array_column($onlyForAttributes, 'group_nick'));
        $requiredGroups = [];
        foreach ($groups as $group) {
            if (in_array($group['nick'], $groupNicks)) {
                $requiredGroups[] = $group;
            }
        }

        return $requiredGroups;
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
        $collection = $this->productTypeCollectionFactory->create()
            ->appendFilterMarketplaceId($marketplaceId);

        $result = [];
        /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType $item */
        foreach ($collection->getItems() as $item) {
            $result[$item->getNick()] = (int)$item->getId();
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

    public function getSpecificsDefaultSettings(): array
    {
        return $this->attributesSuggester->getSuggestedAttributes();
    }

    public function getMainImageSpecifics(): array
    {
        return [
            'main_product_image_locator#array/media_location',
            'main_offer_image_locator#array/media_location',
        ];
    }

    public function getOtherImagesSpecifics(): array
    {
        return [
            'other_product_image_locator_1#array/media_location',
            'other_offer_image_locator_1#array/media_location',
        ];
    }

    public function getRecommendedBrowseNodesLink(int $marketplaceId): string
    {
        $map = [
            \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_UK
                => 'https://sellercentral.amazon.co.uk/help/hub/reference/G201742570',
            \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_IT
                => 'https://sellercentral.amazon.it/help/hub/reference/G201742570',
            \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_FR
                => 'https://sellercentral.amazon.fr/help/hub/reference/G201742570',
            \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_DE
                => 'https://sellercentral.amazon.de/help/hub/reference/G201742570',
            \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_ES
                => 'https://sellercentral.amazon.es/help/hub/reference/G201742570',
        ];

        if (!array_key_exists($marketplaceId, $map)) {
            return '';
        }

        return __(
            '<a style="display: block; margin-top: -10px" href="%url">View latest Browse Node ID List</a>',
            ['url' => $map[$marketplaceId]]
        );
    }
}
