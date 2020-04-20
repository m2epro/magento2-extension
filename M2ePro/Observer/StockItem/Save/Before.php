<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\StockItem\Save;

/**
 * Class \Ess\M2ePro\Observer\StockItem\Save\Before
 */
class Before extends \Ess\M2ePro\Observer\StockItem\AbstractStockItem
{
    //########################################

    public function beforeProcess()
    {
        parent::beforeProcess();
        $this->clearStoredStockItem();
    }

    public function afterProcess()
    {
        parent::afterProcess();
        $this->storeStockItem();
    }

    // ---------------------------------------

    public function process()
    {
        if ($this->isAddingStockItemProcess()) {
            return;
        }

        $this->reloadStockItem();
    }

    //########################################

    protected function isAddingStockItemProcess()
    {
        return (int)$this->stockItemId <= 0;
    }

    //########################################

    private function clearStoredStockItem()
    {
        if ($this->isAddingStockItemProcess()) {
            return;
        }

        $key = $this->getStockItemId().'_'.$this->getStoreId();
        $this->registry->unregister($key);
    }

    private function storeStockItem()
    {
        if ($this->isAddingStockItemProcess()) {
            return;
        }

        $key = $this->getStockItemId().'_'.$this->getStoreId();
        $this->getRegistry()->register($key, $this->getStockItem());
    }

    //########################################
}
