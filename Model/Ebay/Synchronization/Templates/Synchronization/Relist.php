<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Templates\Synchronization;

final class Relist extends AbstractModel
{
    private $cacheConfig;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig
    ) {
        parent::__construct($resourceConnection, $ebayFactory, $activeRecordFactory, $helperFactory, $modelFactory);

        $this->cacheConfig = $cacheConfig;
    }

    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/synchronization/relist/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Relist';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 55;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 80;
    }

    //########################################

    protected function performActions()
    {
        $this->immediatelyChangedProducts();
        $this->executeScheduled();
    }

    //########################################

    private function immediatelyChangedProducts()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Immediately when Product was changed');

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstances(
            array(\Ess\M2ePro\Model\ProductChange::UPDATE_ATTRIBUTE_CODE)
        );

        foreach ($changedListingsProducts as $listingProduct) {

            try {
                $action = $this->getAction($listingProduct);

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');

                $this->prepareConfigurator($listingProduct, $configurator, $action);

                $isExistInRunner = $this->getRunner()->isExistProduct(
                    $listingProduct, $action, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetRelistRequirements($listingProduct)) {
                    continue;
                }

                /**
                 * @var $synchronizationTemplate \Ess\M2ePro\Model\Ebay\Template\Synchronization
                 */
                $synchronizationTemplate = $listingProduct->getChildObject()->getEbaySynchronizationTemplate();
                if ($synchronizationTemplate->isScheduleEnabled() &&
                    (!$synchronizationTemplate->isScheduleIntervalNow() ||
                        !$synchronizationTemplate->isScheduleWeekNow())
                ) {
                    $additionalData = $listingProduct->getAdditionalData();

                    if (!isset($additionalData['add_to_schedule'])) {
                        $additionalData['add_to_schedule'] = true;
                        $listingProduct->setSettings('additional_data', $additionalData)->save();
                    }

                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, $action, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################

    private function executeScheduled()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Execute scheduled');

        /** @var \Ess\M2ePro\Model\Template\Synchronization $synchTemplateCollection */
        $synchTemplateCollection = $this->ebayFactory->getObject('Template\Synchronization')->getCollection();

        foreach ($synchTemplateCollection as $synchTemplate) {

            /* @var $ebaySynchTemplate \Ess\M2ePro\Model\Ebay\Template\Synchronization */
            $ebaySynchTemplate = $synchTemplate->getChildObject();

            if (!$ebaySynchTemplate->isScheduleEnabled()) {
                continue;
            }

            if (!$ebaySynchTemplate->isScheduleIntervalNow() ||
                !$ebaySynchTemplate->isScheduleWeekNow()) {
                continue;
            }

            $listingsProducts = array();
            $affectedListingsProducts = NULL;

            do {

                $tempListingsProducts = $this->getNextScheduledListingsProducts($synchTemplate->getId());

                if (count($tempListingsProducts) <= 0) {
                    break;
                }

                if (is_null($affectedListingsProducts)) {
                    $affectedListingsProducts = $ebaySynchTemplate->getAffectedListingsProducts(true);
                }

                if (count($affectedListingsProducts) <= 0) {
                    break;
                }

                foreach ($tempListingsProducts as $tempListingProduct) {

                    $found = false;
                    foreach ($affectedListingsProducts as $affectedListingProduct) {
                        if ((int)$tempListingProduct->getId() == $affectedListingProduct['id']) {
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        continue;
                    }

                    $listingsProducts[] = $tempListingProduct;
                }

            } while (count($listingsProducts) < 100);

            foreach ($listingsProducts as $listingProduct) {

                try {
                    /* @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
                    $listingProduct->getMagentoProduct()->enableCache();

                    $action = $this->getAction($listingProduct);

                    /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                    $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');

                    $this->prepareConfigurator($listingProduct, $configurator, $action);

                    $isExistInRunner = $this->getRunner()->isExistProduct(
                        $listingProduct, $action, $configurator
                    );

                    if ($isExistInRunner) {
                        continue;
                    }

                    if (!$this->getInspector()->isMeetRelistRequirements($listingProduct)) {
                        continue;
                    }

                    $this->getRunner()->addProduct(
                        $listingProduct, $action, $configurator
                    );
                } catch (\Exception $exception) {

                    $this->logError($listingProduct, $exception);
                    continue;
                }
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function getNextScheduledListingsProducts($synchTemplateId)
    {
        $cacheConfigGroup = '/ebay/template/synchronization/'.$synchTemplateId.'/schedule/relist/';

        $yearMonthDay = $this->getHelper('Data')->getCurrentGmtDate(false,'Y-m-d');
        $configData = $this->cacheConfig->getGroupValue($cacheConfigGroup,'last_listing_product_id');

        if (is_null($configData)) {
            $configData = array();
        } else {
            $configData = json_decode($configData,true);
        }

        $lastListingProductId = 0;
        if (isset($configData[$yearMonthDay])) {
            $lastListingProductId = (int)$configData[$yearMonthDay];
        }

        $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('main_table.id',array('gt'=>$lastListingProductId));
        $collection->addFieldToFilter('main_table.status',
                    array('neq'=>\Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED));
        $collection->addFieldToFilter('main_table.status',
                    array('neq'=>\Ess\M2ePro\Model\Listing\Product::STATUS_LISTED));
        $collection->addFieldToFilter('main_table.additional_data',
                    array('like'=>'%"add_to_schedule":true%'));
        $collection->getSelect()->order('main_table.id ASC');
        $collection->getSelect()->limit(100);

        $lastItem = $collection->getLastItem();
        if (!$lastItem->getId()) {
            return array();
        }

        $configData = array($yearMonthDay=>$lastItem->getId());
        $cacheConfig->setGroupValue($cacheConfigGroup,'last_listing_product_id',json_encode($configData));

        return $collection->getItems();
    }

    //########################################

    private function getAction(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if ($listingProduct->isHidden()) {
            return \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE;
        }

        return \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST;
    }

    private function prepareConfigurator(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                         \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator $configurator,
                                         $action)
    {
        if ($action != \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST) {
            $configurator->setParams(array('replaced_action' => \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST));
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        if (!$ebayListingProduct->getEbaySynchronizationTemplate()->isRelistSendData()) {
            $configurator->setPartialMode();
            $configurator->allowQty()->allowPrice()->allowVariations();
        }
    }

    //########################################
}