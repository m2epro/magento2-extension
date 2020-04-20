<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

/**
 * Class \Ess\M2ePro\Model\Servicing\Task\Marketplaces
 */
class Marketplaces extends \Ess\M2ePro\Model\Servicing\Task
{
    private $needToCleanCache = false;

    //########################################

    /**
     * @return string
     */
    public function getPublicNick()
    {
        return 'marketplaces';
    }

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [];
    }

    public function processResponseData(array $data)
    {
        if (isset($data['ebay_last_update_dates']) && is_array($data['ebay_last_update_dates'])) {
            $this->processEbayLastUpdateDates($data['ebay_last_update_dates']);
        }

        if (isset($data['amazon_last_update_dates']) && is_array($data['amazon_last_update_dates'])) {
            $this->processAmazonLastUpdateDates($data['amazon_last_update_dates']);
        }

        if ($this->needToCleanCache) {
            $this->getHelper('Data_Cache_Permanent')->removeTagValues('marketplace');
        }
    }

    //########################################

    protected function processEbayLastUpdateDates($lastUpdateDates)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\Collection $accountCollection */
        $enabledMarketplaces = $this->parentFactory
            ->getObject(\Ess\M2ePro\Helper\Component\Ebay::NICK, 'Marketplace')->getCollection()
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);

        $connection = $this->resource->getConnection();
        $dictionaryTable = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_dictionary_marketplace');

        /** @var $marketplace \Ess\M2ePro\Model\Marketplace */
        foreach ($enabledMarketplaces as $marketplace) {
            if (!isset($lastUpdateDates[$marketplace->getNativeId()])) {
                continue;
            }

            $serverLastUpdateDate = $lastUpdateDates[$marketplace->getNativeId()];

            $select = $connection->select()
                ->from($dictionaryTable, [
                    'client_details_last_update_date'
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
                    'client_details_last_update_date' => $clientLastUpdateDate
                ],
                ['marketplace_id = ?' => $marketplace->getId()]
            );
        }
    }

    protected function processAmazonLastUpdateDates($lastUpdateDates)
    {
        $enabledMarketplaces = $this->getHelper('Component\Amazon')
            ->getMarketplacesAvailableForApiCreation();

        $connection = $this->resource->getConnection();
        $dictionaryTable = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_amazon_dictionary_marketplace');

        /** @var $marketplace \Ess\M2ePro\Model\Marketplace */
        foreach ($enabledMarketplaces as $marketplace) {
            if (!isset($lastUpdateDates[$marketplace->getNativeId()])) {
                continue;
            }

            $serverLastUpdateDate = $lastUpdateDates[$marketplace->getNativeId()];

            $select = $connection->select()
                ->from($dictionaryTable, [
                    'client_details_last_update_date'
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
                    'client_details_last_update_date' => $clientLastUpdateDate
                ],
                ['marketplace_id = ?' => $marketplace->getId()]
            );
        }
    }

    //########################################
}
