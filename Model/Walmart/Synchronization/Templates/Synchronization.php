<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\Templates;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization
 */
class Synchronization extends \Ess\M2ePro\Model\Walmart\Synchronization\Templates\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner
     */
    private $runner = null;

    /**
     * @var \Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization\Inspector
     */
    private $inspector = null;

    private $pendingListingProducts = [];

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner
     */
    public function getRunner()
    {
        return $this->runner;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization\Inspector
     */
    public function getInspector()
    {
        return $this->inspector;
    }

    // ---------------------------------------

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getPendingListingProducts()
    {
        return $this->pendingListingProducts;
    }

    //########################################

    protected function getNick()
    {
        return null;
    }

    protected function getTitle()
    {
        return 'Inventory';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 20;
    }

    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    protected function beforeStart()
    {
        parent::beforeStart();

        $this->runner = $this->modelFactory->getObject('Synchronization_Templates_Synchronization_Runner');

        $this->runner->setConnectorModel('Walmart_Connector_Product_Dispatcher');
        $this->runner->setMaxProductsPerStep(100);

        $this->runner->setLockItem($this->getActualLockItem());
        $this->runner->setPercentsStart($this->getPercentsStart() + $this->getPercentsInterval()/2);
        $this->runner->setPercentsEnd($this->getPercentsEnd());

        $this->inspector =
            $this->modelFactory->getObject('Walmart_Synchronization_Templates_Synchronization_Inspector');

        $this->pendingListingProducts = $this->getPendingListingProductsItems();
    }

    protected function afterEnd()
    {
        $this->executeRunner();
        parent::afterEnd();
    }

    // ---------------------------------------

    protected function performActions()
    {
        $result = true;

        $result = !$this->processTask('Synchronization\ListActions') ? false : $result;
        $result = !$this->processTask('Synchronization\Relist') ? false : $result;
        $result = !$this->processTask('Synchronization\Stop') ? false : $result;
        $result = !$this->processTask('Synchronization\Revise') ? false : $result;

        return $result;
    }

    protected function makeTask($taskPath)
    {
        $task = parent::makeTask($taskPath);

        $task->setRunner($this->getRunner());
        $task->setInspector($this->getInspector());
        $task->setProductChangesManager($this->getProductChangesManager());
        $task->setPendingListingProducts($this->getPendingListingProducts());

        return $task;
    }

    //########################################

    private function executeRunner()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__, 'Apply Products changes on Walmart');

        $this->getRunner()->execute();

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################

    private function getPendingListingProductsItems()
    {
        $collection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->where('additional_data LIKE \'%"recheck_after_list_date"%\'');

        $items = $collection->getItems();

        $result = [];
        foreach ($items as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $additionalData = $listingProduct->getAdditionalData();

            if (!isset($additionalData['recheck_after_list_date'])) {
                continue;
            }

            $recheckDate = new \DateTime($additionalData['recheck_after_list_date'], new \DateTimeZone('UTC'));
            $now         = new \DateTime('now', new \DateTimeZone('UTC'));

            if ((int)$now->format('U') >= (int)$recheckDate->format('U')) {
                $result[] = $listingProduct;

                unset($additionalData['recheck_after_list_date']);
                $listingProduct->setSettings('additional_data', $additionalData);
                $listingProduct->save();
            }
        }

        return $result;
    }

    //########################################
}
