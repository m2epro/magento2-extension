<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Marketplaces;

/**
 * Class \Ess\M2ePro\Model\Ebay\Synchronization\Marketplaces\MotorsKtypes
 */
class MotorsKtypes extends AbstractModel
{
    /** @var \Ess\M2ePro\Model\Marketplace */
    protected $marketplace;

    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/motors_ktypes/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Parts Compatibility [kTypes]';
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
        return 100;
    }

    //########################################

    protected function isPossibleToRun()
    {
        if (!parent::isPossibleToRun()) {
            return false;
        }

        $params = $this->getParams();

        $marketplace = $this->ebayFactory->getCachedObjectLoaded('Marketplace', $params['marketplace_id']);
        $this->marketplace = $marketplace;

        return $marketplace->getChildObject()->isKtypeEnabled();
    }

    protected function performActions()
    {
        $partNumber = 1;
        $this->deleteAllKtypes();

        for ($i = 0; $i < 100; $i++) {
            $this->getActualLockItem()->setPercents($this->getPercentsStart());

            $this->getActualOperationHistory()->addTimePoint(
                __METHOD__.'get'.$this->marketplace->getId(),
                'Get kTypes from eBay'
            );
            $response = $this->receiveFromEbay($partNumber);
            $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'get'.$this->marketplace->getId());

            if (empty($response)) {
                break;
            }

            $this->getActualLockItem()->setStatus(
                'Processing kTypes data ('.(int)$partNumber.'/'.(int)$response['total_parts'].')'
            );
            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $this->getPercentsInterval()/2);
            $this->getActualLockItem()->activate();

            $this->getActualOperationHistory()->addTimePoint(
                __METHOD__.'save'.$this->marketplace->getId(),
                'Save kTypes to DB'
            );
            $this->saveKtypesToDb($response['data']);
            $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'save'.$this->marketplace->getId());

            $this->getActualLockItem()->setPercents($this->getPercentsEnd());
            $this->getActualLockItem()->activate();

            $partNumber = $response['next_part'];

            if ($partNumber === null) {
                break;
            }
        }

        $this->logSuccessfulOperation();
    }

    //########################################

    protected function receiveFromEbay($partNumber)
    {
        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector(
            'marketplace',
            'get',
            'motorsKtypes',
            ['part_number' => $partNumber],
            null,
            $this->marketplace->getId()
        );

        $dispatcherObj->process($connectorObj);
        $response = $connectorObj->getResponseData();

        if ($response === null || empty($response['data'])) {
            $response = [];
        }

        $dataCount = isset($response['data']) ? count($response['data']) : 0;
        $this->getActualOperationHistory()->addText("Total received parts from eBay: {$dataCount}");

        return $response;
    }

    protected function deleteAllKtypes()
    {
        $connWrite = $this->resourceConnection->getConnection();
        $tableMotorsKtypes = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_dictionary_motor_ktype');

        $connWrite->delete($tableMotorsKtypes, '`is_custom` = 0');
    }

    protected function saveKtypesToDb(array $data)
    {
        $totalCountItems = count($data['items']);
        if ($totalCountItems <= 0) {
            return;
        }

        $connWrite = $this->resourceConnection->getConnection();
        $tableMotorsKtype = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_dictionary_motor_ktype');

        $iteration            = 0;
        $iterationsForOneStep = 1000;
        $percentsForOneStep   = ($this->getPercentsInterval()/2) / ($totalCountItems/$iterationsForOneStep);

        $temporaryIds   = [];
        $itemsForInsert = [];

        for ($i = 0; $i < $totalCountItems; $i++) {
            $item = $data['items'][$i];

            $temporaryIds[] = (int)$item['ktype'];
            $itemsForInsert[] = [
                'ktype'          => (int)$item['ktype'],
                'make'           => $item['make'],
                'model'          => $item['model'],
                'variant'        => $item['variant'],
                'body_style'     => $item['body_style'],
                'type'           => $item['type'],
                'from_year'      => (int)$item['from_year'],
                'to_year'        => (int)$item['to_year'],
                'engine'         => $item['engine'],
            ];

            if (count($itemsForInsert) >= 100 || $i >= ($totalCountItems - 1)) {
                $connWrite->insertMultiple($tableMotorsKtype, $itemsForInsert);
                $connWrite->delete($tableMotorsKtype, ['is_custom = ?' => 1,
                                                            'ktype IN (?)'  => $temporaryIds]);
                $itemsForInsert = $temporaryIds = [];
            }

            if (++$iteration % $iterationsForOneStep == 0) {
                $percentsShift = ($iteration/$iterationsForOneStep) * $percentsForOneStep;
                $this->getActualLockItem()->setPercents(
                    $this->getPercentsStart() + $this->getPercentsInterval()/2 + $percentsShift
                );
            }
        }
    }

    protected function logSuccessfulOperation()
    {
        // M2ePro_TRANSLATIONS
        // The "Parts Compatibility [kTypes]" Action for eBay Site: "%mrk%" has been successfully completed.

        $tempString = $this->getHelper('Module\Log')->encodeDescription(
            'The "Parts Compatibility [kTypes]" Action for eBay Site: "%mrk%" has been successfully completed.',
            ['mrk' => $this->marketplace->getTitle()]
        );

        $this->getLog()->addMessage(
            $tempString,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
        );
    }

    //########################################
}
