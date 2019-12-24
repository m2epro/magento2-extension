<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Marketplaces;

/**
 * Class \Ess\M2ePro\Model\Ebay\Synchronization\Marketplaces\Details
 */
class Details extends AbstractModel
{
    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/details/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Details';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 0;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 25;
    }

    //########################################

    protected function performActions()
    {
        $params = $this->getParams();

        /** @var $marketplace \Ess\M2ePro\Model\Marketplace **/
        $marketplace = $this->ebayFactory->getObjectLoaded('Marketplace', (int)$params['marketplace_id']);

        $this->getActualOperationHistory()->addText('Starting Marketplace "'.$marketplace->getTitle().'"');

        $this->getActualOperationHistory()->addTimePoint(
            __METHOD__.'get'.$marketplace->getId(),
            'Get Details from eBay'
        );
        $details = $this->receiveFromEbay($marketplace);
        $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'get'.$marketplace->getId());

        $this->getActualLockItem()->setPercents($this->getPercentsStart() + $this->getPercentsInterval()/2);
        $this->getActualLockItem()->activate();

        $this->getActualOperationHistory()->addTimePoint(__METHOD__.'save'.$marketplace->getId(), 'Save Details to DB');
        $this->saveDetailsToDb($marketplace, $details);
        $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'save'.$marketplace->getId());

        $this->logSuccessfulOperation($marketplace);
    }

    //########################################

    protected function receiveFromEbay(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector(
            'marketplace',
            'get',
            'info',
            ['include_details' => 1],
            'info',
            $marketplace->getId(),
            null
        );

        $dispatcherObj->process($connectorObj);
        $details = $connectorObj->getResponseData();

        if ($details === null) {
            return [];
        }

        $details['details']['last_update'] = $details['last_update'];
        return $details['details'];
    }

    protected function saveDetailsToDb(\Ess\M2ePro\Model\Marketplace $marketplace, array $details)
    {
        $connWrite = $this->resourceConnection->getConnection();

        $tableMarketplaces = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_dictionary_marketplace');
        $tableShipping = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_dictionary_shipping');

        // Save marketplaces
        // ---------------------------------------
        $connWrite->delete($tableMarketplaces, ['marketplace_id = ?' => $marketplace->getId()]);

        $insertData = [
            'marketplace_id'                  => $marketplace->getId(),
            'client_details_last_update_date' => isset($details['last_update']) ? $details['last_update'] : null,
            'server_details_last_update_date' => isset($details['last_update']) ? $details['last_update'] : null,
            'dispatch'                        => $this->getHelper('Data')->jsonEncode($details['dispatch']),
            'packages'                        => $this->getHelper('Data')->jsonEncode($details['packages']),
            'return_policy'                   => $this->getHelper('Data')->jsonEncode($details['return_policy']),
            'listing_features'                => $this->getHelper('Data')->jsonEncode($details['listing_features']),
            'payments'                        => $this->getHelper('Data')->jsonEncode($details['payments']),
            'shipping_locations'              => $this->getHelper('Data')->jsonEncode($details['shipping_locations']),
            'shipping_locations_exclude'      => $this->getHelper('Data')->jsonEncode(
                $details['shipping_locations_exclude']
            ),
            'tax_categories'                  => $this->getHelper('Data')->jsonEncode($details['tax_categories']),
            'charities'                       => $this->getHelper('Data')->jsonEncode($details['charities']),
        ];

        if (isset($details['additional_data'])) {
            $insertData['additional_data'] = $this->getHelper('Data')->jsonEncode($details['additional_data']);
        }

        unset($details['categories_version']);
        $connWrite->insert($tableMarketplaces, $insertData);
        // ---------------------------------------

        // Save shipping
        // ---------------------------------------
        $connWrite->delete($tableShipping, ['marketplace_id = ?' => $marketplace->getId()]);

        foreach ($details['shipping'] as $data) {
            $insertData = [
                'marketplace_id'   => $marketplace->getId(),
                'ebay_id'          => $data['ebay_id'],
                'title'            => $data['title'],
                'category'         => $this->getHelper('Data')->jsonEncode($data['category']),
                'is_flat'          => $data['is_flat'],
                'is_calculated'    => $data['is_calculated'],
                'is_international' => $data['is_international'],
                'data'             => $this->getHelper('Data')->jsonEncode($data['data']),
            ];
            $connWrite->insert($tableShipping, $insertData);
        }
        // ---------------------------------------
    }

    protected function logSuccessfulOperation(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        // M2ePro\TRANSLATIONS
        // The "Details" Action for Marketplace: "%mrk%" has been successfully completed.

        $tempString = $this->getHelper('Module\Log')->encodeDescription(
            'The "Details" Action for Marketplace: "%mrk%" has been successfully completed.',
            ['mrk' => $marketplace->getTitle()]
        );

        $this->getLog()->addMessage(
            $tempString,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
        );
    }

    //########################################
}
