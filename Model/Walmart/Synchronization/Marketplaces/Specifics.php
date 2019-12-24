<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\Marketplaces;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\Marketplaces\Specifics
 */
class Specifics extends AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/specifics/';
    }

    protected function getTitle()
    {
        return 'Specifics';
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
        $partNumber = 1;
        $params     = $this->getParams();

        /** @var $marketplace \Ess\M2ePro\Model\Marketplace **/
        $marketplace = $this->walmartFactory->getObjectLoaded('Marketplace', (int)$params['marketplace_id']);

        $this->deleteAllSpecifics($marketplace);

        $this->getActualOperationHistory()->addText('Starting Marketplace "'.$marketplace->getTitle().'"');

        for ($i = 0; $i < 100; $i++) {
            $this->getActualLockItem()->setPercents($this->getPercentsStart());

            $this->getActualOperationHistory()->addTimePoint(
                __METHOD__.'get'.$marketplace->getId(),
                'Get specifics from Walmart, part № ' . $partNumber
            );
            $response = $this->receiveFromWalmart($marketplace, $partNumber);
            $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'get'.$marketplace->getId());

            if (empty($response)) {
                break;
            }

            $this->getActualLockItem()->setStatus(
                'Processing specifics data ('.(int)$partNumber.'/'.(int)$response['total_parts'].')'
            );
            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $this->getPercentsInterval()/2);
            $this->getActualLockItem()->activate();

            $this->getActualOperationHistory()->addTimePoint(
                __METHOD__.'save'.$marketplace->getId(),
                'Save specifics to DB'
            );
            $this->saveSpecificsToDb($marketplace, $response['data']);
            $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'save'.$marketplace->getId());

            $this->getActualLockItem()->setPercents($this->getPercentsEnd());
            $this->getActualLockItem()->activate();

            $partNumber = $response['next_part'];

            if ($partNumber === null) {
                break;
            }
        }

        $this->logSuccessfulOperation($marketplace);
    }

    //########################################

    protected function receiveFromWalmart(\Ess\M2ePro\Model\Marketplace $marketplace, $partNumber)
    {
        $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');
        $connectorObj     = $dispatcherObject->getVirtualConnector(
            'marketplace',
            'get',
            'specifics',
            ['part_number' => $partNumber,
            'marketplace' => $marketplace->getNativeId()]
        );

        $dispatcherObject->process($connectorObj);
        $response = $connectorObj->getResponseData();

        if ($response === null || empty($response['data'])) {
            $response = [];
        }

        $dataCount = isset($response['data']) ? count($response['data']) : 0;
        $this->getActualOperationHistory()->addText("Total received specifics from Walmart: {$dataCount}");

        return $response;
    }

    protected function deleteAllSpecifics(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        $connWrite = $this->resourceConnection->getConnection();
        $tableSpecifics = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_walmart_dictionary_specific');

        $connWrite->delete($tableSpecifics, ['marketplace_id = ?' => $marketplace->getId()]);
    }

    protected function saveSpecificsToDb(\Ess\M2ePro\Model\Marketplace $marketplace, array $specifics)
    {
        $totalCountItems = count($specifics);
        if ($totalCountItems <= 0) {
            return;
        }

        $connWrite = $this->resourceConnection->getConnection();
        $tableSpecifics = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_walmart_dictionary_specific');

        $iteration            = 0;
        $iterationsForOneStep = 1000;
        $percentsForOneStep   = ($this->getPercentsInterval()/2) / ($totalCountItems/$iterationsForOneStep);
        $insertData           = [];

        for ($i = 0; $i < $totalCountItems; $i++) {
            $data = $specifics[$i];

            $insertData[] = [
                'marketplace_id'     => $marketplace->getId(),
                'specific_id'        => $data['id'],
                'parent_specific_id' => $data['parent_id'],
                'product_data_nick'  => $data['product_data_nick'],
                'title'              => $data['title'],
                'xml_tag'            => $data['xml_tag'],
                'xpath'              => $data['xpath'],
                'type'               => (int)$data['type'],
                'values'             => $this->getHelper('Data')->jsonEncode($data['values']),
                'params'             => $this->getHelper('Data')->jsonEncode($data['params']),
                'data_definition'    => $this->getHelper('Data')->jsonEncode($data['data_definition']),
                'min_occurs'         => (int)$data['min_occurs'],
                'max_occurs'         => (int)$data['max_occurs']
            ];

            if (count($insertData) >= 100 || $i >= ($totalCountItems - 1)) {
                $connWrite->insertMultiple($tableSpecifics, $insertData);
                $insertData = [];
            }

            if (++$iteration % $iterationsForOneStep == 0) {
                $percentsShift = ($iteration/$iterationsForOneStep) * $percentsForOneStep;
                $this->getActualLockItem()->setPercents(
                    $this->getPercentsStart() + $this->getPercentsInterval()/2 + $percentsShift
                );
            }
        }
    }

    protected function logSuccessfulOperation(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        //->__('The "Specifics" Action for %Walmart% Marketplace: "%mrk%" has been successfully completed.');

        $tempString = $this->getHelper('Module\Log')->encodeDescription(
            'The "Specifics" Action for %Walmart% Marketplace: "%mrk%" has been successfully completed.',
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
