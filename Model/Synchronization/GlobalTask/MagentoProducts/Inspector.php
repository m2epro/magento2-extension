<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization\GlobalTask\MagentoProducts;

class Inspector extends AbstractModel
{
    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/inspector/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Inspector';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 80;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 90;
    }

    //########################################

    protected function performActions()
    {
        $this->prepareBaseValues();
        $listingsProducts = $this->getNextListingsProducts();

        if ($listingsProducts === false) {
            return;
        }

        if (count($listingsProducts) <= 0) {

            $lastTime = strtotime($this->getLastTimeStartCircle());
            $interval = $this->getMinIntervalBetweenCircles();

            if ($lastTime + $interval < $this->getHelper('Data')->getCurrentGmtDate(true)) {
                $this->setLastListingProductId(0);
                $this->resetLastTimeStartCircle();
            }

            return;
        }

        $tempIndex = 0;
        $totalItems = count($listingsProducts);

        foreach ($listingsProducts as $listingProduct) {

            $this->updateListingsProductChange($listingProduct);

            if ((++$tempIndex)%20 == 0) {
                $percentsPerOneItem = $this->getPercentsInterval()/$totalItems;
                $this->getActualLockItem()->setPercents($percentsPerOneItem*$tempIndex);
                $this->getActualLockItem()->activate();
            }
        }

        $listingProduct = array_pop($listingsProducts);
        $this->setLastListingProductId($listingProduct->getId());
    }

    //########################################

    private function prepareBaseValues()
    {
        if (is_null($this->getLastListingProductId())) {
            $this->setLastListingProductId(0);
        }

        if (is_null($this->getLastTimeStartCircle())) {
            $this->resetLastTimeStartCircle();
        }
    }

    // ---------------------------------------

    private function getMinIntervalBetweenCircles()
    {
        return (int)$this->getConfigValue($this->getFullSettingsPath(),'min_interval_between_circles');
    }

    private function getMaxCountTimesForFullCircle()
    {
        return (int)$this->getConfigValue($this->getFullSettingsPath(),'max_count_times_for_full_circle');
    }

    // ---------------------------------------

    private function getMinCountItemsPerOneTime()
    {
        return (int)$this->getConfigValue($this->getFullSettingsPath(),'min_count_items_per_one_time');
    }

    private function getMaxCountItemsPerOneTime()
    {
        return (int)$this->getConfigValue($this->getFullSettingsPath(),'max_count_items_per_one_time');
    }

    // ---------------------------------------

    private function getLastListingProductId()
    {
        return $this->getConfigValue($this->getFullSettingsPath(),'last_listing_product_id');
    }

    private function setLastListingProductId($listingProductId)
    {
        $this->setConfigValue($this->getFullSettingsPath(),'last_listing_product_id',(int)$listingProductId);
    }

    // ---------------------------------------

    private function getLastTimeStartCircle()
    {
        return $this->getConfigValue($this->getFullSettingsPath(),'last_time_start_circle');
    }

    private function resetLastTimeStartCircle()
    {
        $this->setConfigValue(
            $this->getFullSettingsPath(),'last_time_start_circle',$this->getHelper('Data')->getCurrentGmtDate()
        );
    }

    //########################################

    private function getCountItemsPerOneTime()
    {
        $totalCount = (int)$this->activeRecordFactory->getObject('Listing\Product')->getCollection()->getSize();
        $perOneTime = (int)($totalCount / $this->getMaxCountTimesForFullCircle());

        if ($perOneTime < $this->getMinCountItemsPerOneTime()) {
            $perOneTime = $this->getMinCountItemsPerOneTime();
        }

        if ($perOneTime > $this->getMaxCountItemsPerOneTime()) {
            $perOneTime = $this->getMaxCountItemsPerOneTime();
        }

        return $perOneTime;
    }

    private function getNextListingsProducts()
    {
        $countOfProductChanges = $this->activeRecordFactory->getObject('ProductChange')->getCollection()->getSize();
        $productChangeMaxPerOneTime = $this->getConfigValue('/settings/product_change/', 'max_count_per_one_time');

        $limit = $productChangeMaxPerOneTime - $countOfProductChanges;

        if ($limit <= 0) {
            return false;
        }

        $limit > $this->getCountItemsPerOneTime() && $limit = $this->getCountItemsPerOneTime();

        $collection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()
            ->where("`id` > ".(int)$this->getLastListingProductId())
            ->order(array('id ASC'))
            ->limit($limit);

        return $collection->getItems();
    }

    //########################################

    private function updateListingsProductChange(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->activeRecordFactory->getObject('ProductChange')
                                  ->addUpdateAction(
                                      $listingProduct->getProductId(),
                                      \Ess\M2ePro\Model\ProductChange::INITIATOR_INSPECTOR
                                  );

        foreach ($listingProduct->getVariations(true) as $variation) {

            /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */

            foreach ($variation->getOptions(true) as $option) {

                /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */

                $this->activeRecordFactory->getObject('ProductChange')
                                          ->addUpdateAction(
                                              $option->getProductId(),
                                              \Ess\M2ePro\Model\ProductChange::INITIATOR_INSPECTOR
                                          );
            }
        }
    }

    //########################################
}