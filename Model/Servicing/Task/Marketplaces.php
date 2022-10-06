<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

class Marketplaces implements \Ess\M2ePro\Model\Servicing\TaskInterface
{
    public const NAME = 'marketplaces';

    /** @var bool */
    private $needToCleanCache = false;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory */
    private $parentFactory;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resource;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $cachePermanent;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseStructure;
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $componentAmazon;

    /**
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent
     * @param \Ess\M2ePro\Helper\Module\Database\Structure $databaseStructure
     * @param \Ess\M2ePro\Helper\Component\Amazon $componentAmazon
     */
    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseStructure,
        \Ess\M2ePro\Helper\Component\Amazon $componentAmazon
    ) {
        $this->parentFactory = $parentFactory;
        $this->resource = $resource;
        $this->cachePermanent = $cachePermanent;
        $this->databaseStructure = $databaseStructure;
        $this->componentAmazon = $componentAmazon;
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getServerTaskName(): string
    {
        return self::NAME;
    }

    // ----------------------------------------

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        return [];
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    public function isAllowed(): bool
    {
        return true;
    }

    // ----------------------------------------

    /**
     * @param array $data
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function processResponseData(array $data): void
    {
        if (isset($data['ebay_last_update_dates']) && is_array($data['ebay_last_update_dates'])) {
            $this->processEbayLastUpdateDates($data['ebay_last_update_dates']);
        }

        if (isset($data['amazon_last_update_dates']) && is_array($data['amazon_last_update_dates'])) {
            $this->processAmazonLastUpdateDates($data['amazon_last_update_dates']);
        }

        if (isset($data['walmart_last_update_dates']) && is_array($data['walmart_last_update_dates'])) {
            $this->processWalmartLastUpdateDates($data['walmart_last_update_dates']);
        }

        if ($this->needToCleanCache) {
            $this->cachePermanent->removeTagValues('marketplace');
        }
    }

    /**
     * @param array $lastUpdateDates
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
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

    /**
     * @param array $lastUpdateDates
     *
     * @return void
     */
    private function processAmazonLastUpdateDates(array $lastUpdateDates): void
    {
        $enabledMarketplaces = $this->componentAmazon
            ->getMarketplacesAvailableForApiCreation();

        $connection = $this->resource->getConnection();
        $dictionaryTable = $this->databaseStructure
            ->getTableNameWithPrefix('m2epro_amazon_dictionary_marketplace');

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

    /**
     * @param array $lastUpdateDates
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
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
