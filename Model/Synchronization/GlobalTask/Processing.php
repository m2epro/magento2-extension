<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization\GlobalTask;

use Ess\M2ePro\Model\Processing\Runner;

class Processing extends AbstractModel
{
    private $resourceConnection;

    //####################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory);
    }

    //####################################

    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::PROCESSING;
    }

    protected function getNick()
    {
        return NULL;
    }

    // -----------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 60;
    }

    //####################################

    protected function performActions()
    {
        $this->processExpired();
        $this->processCompleted();
    }

    //####################################

    private function processExpired()
    {
        $processingCollection = $this->activeRecordFactory->getObject('Processing')->getCollection();
        $processingCollection->setOnlyExpiredItemsFilter();
        $processingCollection->addFieldToFilter('is_completed', 0);

        /** @var \Ess\M2ePro\Model\Processing[] $processingObjects */
        $processingObjects = $processingCollection->getItems();

        foreach ($processingObjects as $processingObject) {

            $this->getActualLockItem()->activate();

            try {
                /** @var Runner $processingRunner */
                $processingRunner = $this->modelFactory->getObject($processingObject->getModel());
                $processingRunner->setProcessingObject($processingObject);

                $processingRunner->processExpired();
                $processingRunner->complete();
            } catch (\Exception $exception) {
                $this->forceRemoveProcessing($processingObject);
                $this->helperFactory->getObject('Module\Exception')->process($exception);
            }
        }
    }

    private function processCompleted()
    {
        $processingCollection = $this->activeRecordFactory->getObject('Processing')->getCollection();
        $processingCollection->addFieldToFilter('is_completed', 1);

        /** @var \Ess\M2ePro\Model\Processing[] $processingObjects */
        $processingObjects = $processingCollection->getItems();

        foreach ($processingObjects as $processingObject) {

            $this->getActualLockItem()->activate();

            try {
                /** @var Runner $processingRunner */
                $processingRunner = $this->modelFactory->getObject($processingObject->getModel());
                $processingRunner->setProcessingObject($processingObject);

                $processingRunner->processSuccess() && $processingRunner->complete();
            } catch (\Exception $exception) {
                $this->forceRemoveProcessing($processingObject);
                $this->helperFactory->getObject('Module\Exception')->process($exception);
            }
        }
    }

    //####################################

    private function forceRemoveProcessing(\Ess\M2ePro\Model\Processing $processing)
    {
        $table = $this->activeRecordFactory->getObject('Processing\Lock')->getResource()->getMainTable();
        $this->resourceConnection->getConnection()->delete(
            $table, array('`processing_id` = ?' => (int)$processing->getId())
        );

        $table = $this->activeRecordFactory->getObject('Processing')->getResource()->getMainTable();
        $this->resourceConnection->getConnection('core_write')->delete(
            $table, array('`id` = ?' => (int)$processing->getId())
        );
    }

    //####################################
}