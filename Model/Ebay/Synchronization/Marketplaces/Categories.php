<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Marketplaces;

/**
 * Class \Ess\M2ePro\Model\Ebay\Synchronization\Marketplaces\Categories
 */
class Categories extends AbstractModel
{
    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/categories/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Categories';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 25;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    protected function initialize()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues(
            \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay::CACHE_TAG
        );
    }

    protected function performActions()
    {
        $partNumber = 1;
        $params     = $this->getParams();

        /** @var $marketplace \Ess\M2ePro\Model\Marketplace **/
        $marketplace = $this->ebayFactory->getObjectLoaded('Marketplace', (int)$params['marketplace_id']);

        $this->deleteAllCategories($marketplace);

        $this->getActualOperationHistory()->addText('Starting Marketplace "'.$marketplace->getTitle().'"');

        for ($i = 0; $i < 100; $i++) {
            $this->getActualLockItem()->setPercents($this->getPercentsStart());

            $this->getActualOperationHistory()->addTimePoint(
                __METHOD__.'get'.$marketplace->getId(),
                'Get Categories from eBay, part № ' . $partNumber
            );
            $response = $this->receiveFromEbay($marketplace, $partNumber);
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

    protected function receiveFromEbay(\Ess\M2ePro\Model\Marketplace $marketplace, $partNumber)
    {
        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj  = $dispatcherObj->getVirtualConnector(
            'marketplace',
            'get',
            'categories',
            ['part_number' => $partNumber],
            null,
            $marketplace->getId()
        );

        $dispatcherObj->process($connectorObj);
        $response = $connectorObj->getResponseData();

        if ($response === null || empty($response['data'])) {
            $response = [];
        }

        $dataCount = isset($response['data']) ? count($response['data']) : 0;
        $this->getActualOperationHistory()->addText("Total received Categories from eBay: {$dataCount}");

        return $response;
    }

    protected function deleteAllCategories(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        $connWrite = $this->resourceConnection->getConnection();
        $tableCategories = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_dictionary_category');

        $connWrite->delete($tableCategories, ['marketplace_id = ?' => $marketplace->getId()]);
    }

    protected function saveCategoriesToDb(\Ess\M2ePro\Model\Marketplace $marketplace, array $categories)
    {
        if (count($categories) <= 0) {
            return;
        }

        $connWrite = $this->resourceConnection->getConnection();
        $tableCategories = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_dictionary_category');

        $iteration            = 0;
        $iterationsForOneStep = 1000;
        $categoriesCount      = count($categories);
        $percentsForOneStep   = ($this->getPercentsInterval()/2) / ($categoriesCount/$iterationsForOneStep);
        $insertData           = [];

        for ($i = 0; $i < $categoriesCount; $i++) {
            $data = $categories[$i];

            $insertData[] = [
                'marketplace_id'     => $marketplace->getId(),
                'category_id'        => $data['category_id'],
                'parent_category_id' => $data['parent_id'],
                'title'              => $data['title'],
                'path'               => $data['path'],
                'is_leaf'            => $data['is_leaf'],
                'features'           => (
                    $data['is_leaf'] ? $this->getHelper('Data')->jsonEncode($data['features']) : null
                )
            ];

            if (count($insertData) >= 100 || $i >= ($categoriesCount - 1)) {
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
        // The "Categories" Action for Marketplace: "%mrk%" has been successfully completed.

        $tempString = $this->getHelper('Module\Log')->encodeDescription(
            'The "Categories" Action for Marketplace: "%mrk%" has been successfully completed.',
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
