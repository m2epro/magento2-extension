<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Marketplaces;

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
        $marketplace = $this->amazonFactory->getObjectLoaded('Marketplace', (int)$params['marketplace_id']);

        $this->deleteAllSpecifics($marketplace);

        $this->getActualOperationHistory()->addText('Starting Marketplace "'.$marketplace->getTitle().'"');

        for ($i = 0; $i < 100; $i++) {
            $this->getActualLockItem()->setPercents($this->getPercentsStart());

            $this->getActualOperationHistory()->addTimePoint(__METHOD__.'get'.$marketplace->getId(),
                'Get specifics from Amazon, part № ' . $partNumber);
            $response = $this->receiveFromAmazon($marketplace, $partNumber);
            $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'get'.$marketplace->getId());

            if (empty($response)) {
                break;
            }

            $this->getActualLockItem()->setStatus(
                'Processing specifics data ('.(int)$partNumber.'/'.(int)$response['total_parts'].')'
            );
            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $this->getPercentsInterval()/2);
            $this->getActualLockItem()->activate();

            $this->getActualOperationHistory()->addTimePoint(__METHOD__.'save'.$marketplace->getId(),
                'Save specifics to DB');
            $this->saveSpecificsToDb($marketplace, $response['data']);
            $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'save'.$marketplace->getId());

            $this->getActualLockItem()->setPercents($this->getPercentsEnd());
            $this->getActualLockItem()->activate();

            $partNumber = $response['next_part'];

            if (is_null($partNumber)) {
                break;
            }
        }

        $this->logSuccessfulOperation($marketplace);
    }

    //########################################

    protected function receiveFromAmazon(\Ess\M2ePro\Model\Marketplace $marketplace, $partNumber)
    {
        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');
        $connectorObj     = $dispatcherObject->getVirtualConnector('marketplace', 'get', 'specifics',
                                                                   array('part_number' => $partNumber,
                                                                         'marketplace' => $marketplace->getNativeId()));

        $dispatcherObject->process($connectorObj);
        $response = $connectorObj->getResponseData();

        if (is_null($response) || empty($response['data'])) {
            $response = array();
        }

        $dataCount = isset($response['data']) ? count($response['data']) : 0;
        $this->getActualOperationHistory()->addText("Total received specifics from Amazon: {$dataCount}");

        return $response;
    }

    protected function deleteAllSpecifics(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        $connWrite = $this->resourceConnection->getConnection();
        $tableSpecifics = $this->resourceConnection->getTableName('m2epro_amazon_dictionary_specific');

        $connWrite->delete($tableSpecifics, array('marketplace_id = ?' => $marketplace->getId()));
    }

    protected function saveSpecificsToDb(\Ess\M2ePro\Model\Marketplace $marketplace, array $specifics)
    {
        $totalCountItems = count($specifics);
        if ($totalCountItems <= 0) {
            return;
        }

        $connWrite = $this->resourceConnection->getConnection();
        $tableSpecifics = $this->resourceConnection->getTableName('m2epro_amazon_dictionary_specific');

        $iteration            = 0;
        $iterationsForOneStep = 1000;
        $percentsForOneStep   = ($this->getPercentsInterval()/2) / ($totalCountItems/$iterationsForOneStep);
        $insertData           = array();

        for ($i = 0; $i < $totalCountItems; $i++) {

            $data = $specifics[$i];

            $insertData[] = array(
                'marketplace_id'     => $marketplace->getId(),
                'specific_id'        => $data['id'],
                'parent_specific_id' => $data['parent_id'],
                'product_data_nick'  => $data['product_data_nick'],
                'title'              => $data['title'],
                'xml_tag'            => $data['xml_tag'],
                'xpath'              => $data['xpath'],
                'type'               => (int)$data['type'],
                'values'             => $this->getHelper('Data')->jsonEncode($data['values']),
                'recommended_values' => $this->getHelper('Data')->jsonEncode($data['recommended_values']),
                'params'             => $this->getHelper('Data')->jsonEncode($data['params']),
                'data_definition'    => $this->getHelper('Data')->jsonEncode($data['data_definition']),
                'min_occurs'         => (int)$data['min_occurs'],
                'max_occurs'         => (int)$data['max_occurs']
            );

            if (count($insertData) >= 100 || $i >= ($totalCountItems - 1)) {
                $connWrite->insertMultiple($tableSpecifics, $insertData);
                $insertData = array();
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
        //->__('The "Specifics" Action for %amazon% Marketplace: "%mrk%" has been successfully completed.');

        $tempString = $this->getHelper('Module\Log')->encodeDescription(
            'The "Specifics" Action for %amazon% Marketplace: "%mrk%" has been successfully completed.',
            array('!amazon' => $this->getHelper('Component\Amazon')->getTitle(),
                  'mrk'     => $marketplace->getTitle())
        );

        $this->getLog()->addMessage($tempString,
                                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
                                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW);
    }

    //########################################
}