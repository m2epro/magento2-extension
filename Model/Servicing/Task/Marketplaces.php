<?php

namespace Ess\M2ePro\Model\Servicing\Task;

class Marketplaces implements \Ess\M2ePro\Model\Servicing\TaskInterface
{
    public const NAME = 'marketplaces';

    private bool $needToCleanCache = false;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory */
    private $parentFactory;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resource;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $cachePermanent;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseStructure;
    private \Ess\M2ePro\Model\Amazon\Dictionary\ProductType\Repository $amazonDictionaryPTRepository;
    private \Ess\M2ePro\Model\Amazon\Marketplace\Repository $amazonMarketplaceRepository;
    private \Ess\M2ePro\Model\Amazon\Marketplace\Issue\ProductTypeOutOfDate\Cache $issueOutOfDateCache;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Dictionary\ProductType\Repository $amazonDictionaryProductTypeRepository,
        \Ess\M2ePro\Model\Amazon\Marketplace\Repository $amazonMarketplaceRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseStructure,
        \Ess\M2ePro\Model\Amazon\Marketplace\Issue\ProductTypeOutOfDate\Cache $issueOutOfDateCache
    ) {
        $this->parentFactory = $parentFactory;
        $this->resource = $resource;
        $this->cachePermanent = $cachePermanent;
        $this->databaseStructure = $databaseStructure;
        $this->amazonDictionaryPTRepository = $amazonDictionaryProductTypeRepository;
        $this->amazonMarketplaceRepository = $amazonMarketplaceRepository;
        $this->issueOutOfDateCache = $issueOutOfDateCache;
    }

    // ----------------------------------------

    public function getServerTaskName(): string
    {
        return self::NAME;
    }

    public function isAllowed(): bool
    {
        return true;
    }

    public function getRequestData(): array
    {
        return [
            'amazon' => $this->buildAmazonMarketplaceData(),
        ];
    }

    private function buildAmazonMarketplaceData(): array
    {
        $result = [];
        $marketplacePtMap = $this->amazonDictionaryPTRepository->getValidNickMapByMarketplaceNativeId();
        foreach ($marketplacePtMap as $nativeMarketplaceId => $productTypesNicks) {
            $result[] = [
                'marketplace' => $nativeMarketplaceId,
                'product_types' => $productTypesNicks
            ];
        }

        return $result;
    }

    // ----------------------------------------

    public function processResponseData(array $data): void
    {
        if (isset($data['ebay_last_update_dates']) && is_array($data['ebay_last_update_dates'])) {
            $this->processEbayLastUpdateDates($data['ebay_last_update_dates']);
        }

        if (isset($data['amazon']) && is_array($data['amazon'])) {
            $this->processAmazonLastUpdateDates($data['amazon']);
        }

        if (isset($data['walmart_last_update_dates']) && is_array($data['walmart_last_update_dates'])) {
            $this->processWalmartLastUpdateDates($data['walmart_last_update_dates']);
        }

        if ($this->needToCleanCache) {
            $this->cachePermanent->removeTagValues('marketplace');
        }
    }

    private function processEbayLastUpdateDates(array $lastUpdateDates): void
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\Collection $accountCollection */
        $enabledMarketplaces = $this->parentFactory
            ->getObject(\Ess\M2ePro\Helper\Component\Ebay::NICK, 'Marketplace')->getCollection()
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);

        $connection = $this->resource->getConnection();
        $dictionaryTable = $this->databaseStructure
            ->getTableNameWithPrefix('m2epro_ebay_dictionary_marketplace');

        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        foreach ($enabledMarketplaces as $marketplace) {
            if (!isset($lastUpdateDates[$marketplace->getNativeId()])) {
                continue;
            }

            $serverLastUpdateDate = $lastUpdateDates[$marketplace->getNativeId()];

            $select = $connection->select()
                                 ->from($dictionaryTable, [
                                     'client_details_last_update_date',
                                 ])
                                 ->where('marketplace_id = ?', $marketplace->getId());

            $clientLastUpdateDate = $connection->fetchOne($select);

            if ($clientLastUpdateDate === null) {
                $clientLastUpdateDate = $serverLastUpdateDate;
            }

            if ($clientLastUpdateDate < $serverLastUpdateDate) {
                $this->needToCleanCache = true;
            }

            $connection->update(
                $dictionaryTable,
                [
                    'server_details_last_update_date' => $serverLastUpdateDate,
                    'client_details_last_update_date' => $clientLastUpdateDate,
                ],
                ['marketplace_id = ?' => $marketplace->getId()]
            );
        }
    }

    private function processAmazonLastUpdateDates(array $lastUpdateDatesByProductTypes): void
    {
        foreach ($lastUpdateDatesByProductTypes as $row) {
            $nativeMarketplaceId = (int)$row['marketplace'];
            $productTypesLastUpdateByNick = [];
            foreach ($row['product_types'] as ['name' => $productTypeNick, 'last_update' => $lastUpdateDate]) {
                $productTypesLastUpdateByNick[$productTypeNick] = \Ess\M2ePro\Helper\Date::createDateGmt(
                    $lastUpdateDate
                );
            }

            $marketplace = $this->amazonMarketplaceRepository->findByNativeId($nativeMarketplaceId);
            if ($marketplace === null) {
                continue;
            }

            foreach ($this->amazonDictionaryPTRepository->findByMarketplace($marketplace) as $productType) {
                if (!isset($productTypesLastUpdateByNick[$productType->getNick()])) {
                    continue;
                }

                $productType->setServerDetailsLastUpdateDate($productTypesLastUpdateByNick[$productType->getNick()]);

                $this->amazonDictionaryPTRepository->save($productType);
            }
        }

        $this->issueOutOfDateCache->clear();
    }

    private function processWalmartLastUpdateDates(array $lastUpdateDates): void
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\Collection $accountCollection */
        $enabledMarketplaces = $this->parentFactory
            ->getObject(\Ess\M2ePro\Helper\Component\Walmart::NICK, 'Marketplace')->getCollection()
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);

        $connection = $this->resource->getConnection();
        $dictionaryTable = $this->databaseStructure
            ->getTableNameWithPrefix('m2epro_walmart_dictionary_marketplace');

        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        foreach ($enabledMarketplaces as $marketplace) {
            if (!isset($lastUpdateDates[$marketplace->getNativeId()])) {
                continue;
            }

            $serverLastUpdateDate = $lastUpdateDates[$marketplace->getNativeId()];

            $select = $connection->select()
                                 ->from($dictionaryTable, [
                                     'client_details_last_update_date',
                                 ])
                                 ->where('marketplace_id = ?', $marketplace->getId());

            $clientLastUpdateDate = $connection->fetchOne($select);

            if ($clientLastUpdateDate === null) {
                $clientLastUpdateDate = $serverLastUpdateDate;
            }

            if ($clientLastUpdateDate < $serverLastUpdateDate) {
                $this->needToCleanCache = true;
            }

            $connection->update(
                $dictionaryTable,
                [
                    'server_details_last_update_date' => $serverLastUpdateDate,
                    'client_details_last_update_date' => $clientLastUpdateDate,
                ],
                ['marketplace_id = ?' => $marketplace->getId()]
            );
        }
    }
}
