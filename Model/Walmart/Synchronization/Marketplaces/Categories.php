<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\Marketplaces;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\Marketplaces\Categories
 */
class Categories extends AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/categories/';
    }

    protected function getTitle()
    {
        return 'Categories';
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

        $this->deleteAllCategories($marketplace);

        $this->getActualOperationHistory()->addText('Starting Marketplace "'.$marketplace->getTitle().'"');

        for ($i = 0; $i < 100; $i++) {
            $this->getActualLockItem()->setPercents($this->getPercentsStart());

            $this->getActualOperationHistory()->addTimePoint(
                __METHOD__.'get'.$marketplace->getId(),
                'Get Categories from Walmart, part № ' . $partNumber
            );
            $response = $this->receiveFromWalmart($marketplace, $partNumber);
            $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'get'.$marketplace->getId());

            if (empty($response)) {
                break;
            }

            $this->getActualLockItem()->setStatus(
                'Processing Categories data ('.(int)$partNumber.'/'.(int)$response['total_parts'].')'
            );
            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $this->getPercentsInterval()/2);
            $this->getActualLockItem()->activate();

            $this->getActualOperationHistory()->addTimePoint(
                __METHOD__.'save'.$marketplace->getId(),
                'Save Categories to DB'
            );
            $this->saveCategoriesToDb($marketplace, $response['data']);
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
        $dispatcherObj = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');
        $connectorObj  = $dispatcherObj->getVirtualConnector(
            'marketplace',
            'get',
            'categories',
            ['part_number' => $partNumber,
                                                                   'marketplace' => $marketplace->getNativeId()],
            null,
            null
        );

        $dispatcherObj->process($connectorObj);
        $response = $connectorObj->getResponseData();

        if ($response === null || empty($response['data'])) {
            $response = [];
        }

        $dataCount = isset($response['data']) ? count($response['data']) : 0;
        $this->getActualOperationHistory()->addText("Total received Categories from Walmart: {$dataCount}");

        return $response;
    }

    protected function deleteAllCategories(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        $connWrite = $this->resourceConnection->getConnection();
        $tableCategories = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_walmart_dictionary_category');

        $connWrite->delete($tableCategories, ['marketplace_id = ?' => $marketplace->getId()]);
    }

    protected function saveCategoriesToDb(\Ess\M2ePro\Model\Marketplace $marketplace, array $categories)
    {
        $totalCountCategories = count($categories);
        if ($totalCountCategories <= 0) {
            return;
        }

        $connWrite = $this->resourceConnection->getConnection();
        $tableCategories = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_walmart_dictionary_category');

        $iteration            = 0;
        $iterationsForOneStep = 1000;
        $percentsForOneStep   = ($this->getPercentsInterval()/2) / ($totalCountCategories/$iterationsForOneStep);
        $insertData           = [];

        for ($i = 0; $i < $totalCountCategories; $i++) {
            $data = $categories[$i];

            $isLeaf = $data['is_leaf'];
            $insertData[] = [
                'marketplace_id'     => $marketplace->getId(),
                'category_id'        => $data['id'],
                'parent_category_id' => $data['parent_id'],
                'browsenode_id'      => ($isLeaf ? $data['browsenode_id'] : null),
                'product_data_nicks' => (
                    $isLeaf ? $this->getHelper('Data')->jsonEncode($data['product_data_nicks']) : null
                ),
                'title'              => $data['title'],
                'path'               => $data['path'],
                'is_leaf'            => $isLeaf,
            ];

            if (count($insertData) >= 100 || $i >= ($totalCountCategories - 1)) {
                $connWrite->insertMultiple($tableCategories, $insertData);
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
        // M2ePro\TRANSLATIONS
        // The "Categories" Action for %Walmart% Marketplace: "%mrk%" has been successfully completed.

        $tempString = $this->getHelper('Module\Log')->encodeDescription(
            'The "Categories" Action for %walmart% Marketplace: "%mrk%" has been successfully completed.',
            ['!walmart' => $this->getHelper('Component\Walmart')->getTitle(),
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
