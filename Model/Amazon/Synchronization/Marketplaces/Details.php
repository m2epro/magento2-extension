<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Marketplaces;

class Details extends AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/details/';
    }

    protected function getTitle()
    {
        return 'Details';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    protected function performActions()
    {
        $params = $this->getParams();

        /** @var $marketplace \Ess\M2ePro\Model\Marketplace **/
        $marketplace = $this->amazonFactory->getObjectLoaded('Marketplace', (int)$params['marketplace_id']);

        $this->getActualOperationHistory()->addText('Starting Marketplace "'.$marketplace->getTitle().'"');

        $this->getActualLockItem()->setPercents($this->getPercentsStart());

        $this->getActualOperationHistory()->addTimePoint(__METHOD__.'get'.$marketplace->getId(),
                                                         'Get details from Amazon');
        $details = $this->receiveFromAmazon($marketplace);
        $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'get'.$marketplace->getId());

        $this->getActualLockItem()->setPercents($this->getPercentsStart() + $this->getPercentsInterval()/2);
        $this->getActualLockItem()->activate();

        $this->getActualOperationHistory()->addTimePoint(__METHOD__.'save'.$marketplace->getId(),'Save details to DB');
        $this->saveDetailsToDb($marketplace,$details);
        $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'save'.$marketplace->getId());

        $this->getActualLockItem()->setPercents($this->getPercentsEnd());
        $this->getActualLockItem()->activate();

        $this->logSuccessfulOperation($marketplace);
    }

    //########################################

    protected function receiveFromAmazon(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        $dispatcherObj = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');
        $connectorObj  = $dispatcherObj->getVirtualConnector(
            'marketplace','get','info',
            array(
                'include_details' => true, 'marketplace' => $marketplace->getNativeId()
            ),
            'info',NULL
        );

        $dispatcherObj->process($connectorObj);
        $details = $connectorObj->getResponseData();

        if (is_null($details)) {
            return array();
        }

        $details['details']['last_update'] = $details['last_update'];
        return $details['details'];
    }

    protected function saveDetailsToDb(\Ess\M2ePro\Model\Marketplace $marketplace, array $details)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableMarketplaces = $this->resourceConnection->getTableName('m2epro_amazon_dictionary_marketplace');
        $tableShippingOverride = $this->resourceConnection->getTableName('m2epro_amazon_dictionary_shipping_override');

        $connection->delete($tableMarketplaces,array('marketplace_id = ?' => $marketplace->getId()));

        $data = array(
            'marketplace_id' => $marketplace->getId(),
            'client_details_last_update_date' => isset($details['last_update']) ? $details['last_update'] : NULL,
            'server_details_last_update_date' => isset($details['last_update']) ? $details['last_update'] : NULL,
            'product_data'   =>
                isset($details['product_data']) ? $this->getHelper('Data')->jsonEncode($details['product_data']) : NULL,
        );

        $connection->insert($tableMarketplaces, $data);

        $this->getHelper('Component\Amazon\Vocabulary')->setServerData($details['vocabulary']);

        $connection->delete($tableShippingOverride, array('marketplace_id = ?' => $marketplace->getId()));

        foreach ($details['shipping_overrides'] as $data) {
            $insertData = array(
                'marketplace_id'   => $marketplace->getId(),
                'location'         => $data['location'],
                'service'          => $data['service'],
                'option'           => $data['option']
            );
            $connection->insert($tableShippingOverride, $insertData);
        }
    }

    protected function logSuccessfulOperation(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        // M2ePro\TRANSLATIONS
        // The "Details" Action for %amazon% Marketplace: "%mrk%" has been successfully completed.

        $tempString = $this->getHelper('Module\Log')->encodeDescription(
            'The "Details" Action for %amazon% Marketplace: "%mrk%" has been successfully completed.',
            array('!amazon' => $this->getHelper('Component\Amazon')->getTitle(),
                  'mrk'     => $marketplace->getTitle())
        );

        $this->getLog()->addMessage($tempString,
                                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
                                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW);
    }

    //########################################
}