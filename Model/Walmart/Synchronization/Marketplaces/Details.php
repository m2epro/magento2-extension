<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\Marketplaces;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\Marketplaces\Details
 */
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
        $marketplace = $this->walmartFactory->getObjectLoaded('Marketplace', (int)$params['marketplace_id']);

        $this->getActualOperationHistory()->addText('Starting Marketplace "'.$marketplace->getTitle().'"');

        $this->getActualLockItem()->setPercents($this->getPercentsStart());

        $this->getActualOperationHistory()->addTimePoint(
            __METHOD__.'get'.$marketplace->getId(),
            'Get details from Walmart'
        );
        $details = $this->receiveFromWalmart($marketplace);
        $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'get'.$marketplace->getId());

        $this->getActualLockItem()->setPercents($this->getPercentsStart() + $this->getPercentsInterval()/2);
        $this->getActualLockItem()->activate();

        $this->getActualOperationHistory()->addTimePoint(__METHOD__.'save'.$marketplace->getId(), 'Save details to DB');
        $this->saveDetailsToDb($marketplace, $details);
        $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'save'.$marketplace->getId());

        $this->getActualLockItem()->setPercents($this->getPercentsEnd());
        $this->getActualLockItem()->activate();

        $this->logSuccessfulOperation($marketplace);
    }

    //########################################

    protected function receiveFromWalmart(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        $dispatcherObj = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');
        $connectorObj  = $dispatcherObj->getVirtualConnector(
            'marketplace',
            'get',
            'info',
            [
                'include_details' => true, 'marketplace' => $marketplace->getNativeId()
            ],
            'info',
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
        $connection = $this->resourceConnection->getConnection();
        $tableMarketplaces = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_walmart_dictionary_marketplace');

        $connection->delete($tableMarketplaces, ['marketplace_id = ?' => $marketplace->getId()]);

        $data = [
            'marketplace_id' => $marketplace->getId(),
            'client_details_last_update_date' => isset($details['last_update']) ? $details['last_update'] : null,
            'server_details_last_update_date' => isset($details['last_update']) ? $details['last_update'] : null,
            'product_data'   => isset($details['product_data']) ?
                                $this->getHelper('Data')->jsonEncode($details['product_data']) : null,
            'tax_codes'      => isset($details['tax_codes']) ?
                                $this->getHelper('Data')->jsonEncode($details['tax_codes']) : null
        ];

        $connection->insert($tableMarketplaces, $data);
    }

    protected function logSuccessfulOperation(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        // M2ePro\TRANSLATIONS
        // The "Details" Action for %Walmart% Marketplace: "%mrk%" has been successfully completed.

        $tempString = $this->getHelper('Module\Log')->encodeDescription(
            'The "Details" Action for %Walmart% Marketplace: "%mrk%" has been successfully completed.',
            ['!Walmart' => $this->getHelper('Component\Walmart')->getTitle(),
                  'mrk'     => $marketplace->getTitle()]
        );

        $this->getLog()->addMessage(
            $tempString,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
        );
    }

    //########################################
}
