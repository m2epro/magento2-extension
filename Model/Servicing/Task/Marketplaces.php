<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

class Marketplaces extends \Ess\M2ePro\Model\Servicing\Task
{
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
        return array();
    }

    public function processResponseData(array $data)
    {
        if (isset($data['ebay_last_update_dates']) && is_array($data['ebay_last_update_dates'])) {
            $this->processEbayLastUpdateDates($data['ebay_last_update_dates']);
        }

        if (isset($data['amazon_last_update_dates']) && is_array($data['amazon_last_update_dates'])) {
            $this->processAmazonLastUpdateDates($data['amazon_last_update_dates']);
        }
    }

    //########################################

    protected function processEbayLastUpdateDates($lastUpdateDates)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\Collection $accountCollection */
        $enabledMarketplaces = $this->parentFactory
            ->getObject(\Ess\M2ePro\Helper\Component\Ebay::NICK,'Marketplace')->getCollection()
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);

        $connection = $this->resource->getConnection();
        $dictionaryTable = $this->resource->getTableName('m2epro_ebay_dictionary_marketplace');

        /* @var $marketplace \Ess\M2ePro\Model\Marketplace */
        foreach ($enabledMarketplaces as $marketplace) {

            if (!isset($lastUpdateDates[$marketplace->getNativeId()])) {
                continue;
            }

            $serverLastUpdateDate = $lastUpdateDates[$marketplace->getNativeId()];

            $expr = "IF(client_details_last_update_date is NULL, '{$serverLastUpdateDate}',
                                                                 client_details_last_update_date)";

            $connection->update(
                $dictionaryTable,
                array(
                    'server_details_last_update_date' => $serverLastUpdateDate,
                    'client_details_last_update_date' => new \Zend_Db_Expr($expr)
                ),
                array('marketplace_id = ?' => $marketplace->getId())
            );
        }
    }

    protected function processAmazonLastUpdateDates($lastUpdateDates)
    {
        $enabledMarketplaces = $this->getHelper('Component\Amazon')
            ->getMarketplacesAvailableForApiCreation();

        $connection = $this->resource->getConnection();
        $dictionaryTable = $this->resource->getTableName('m2epro_amazon_dictionary_marketplace');

        /* @var $marketplace \Ess\M2ePro\Model\Marketplace */
        foreach ($enabledMarketplaces as $marketplace) {

            if (!isset($lastUpdateDates[$marketplace->getNativeId()])) {
                continue;
            }

            $serverLastUpdateDate = $lastUpdateDates[$marketplace->getNativeId()];

            $expr = "IF(client_details_last_update_date is NULL, '{$serverLastUpdateDate}',
                                                                 client_details_last_update_date)";

            $connection->update(
                $dictionaryTable,
                array(
                    'server_details_last_update_date' => $serverLastUpdateDate,
                    'client_details_last_update_date' => new \Zend_Db_Expr($expr)
                ),
                array('marketplace_id = ?' => $marketplace->getId())
            );
        }
    }

    //########################################
}