<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\ProcessScheduledActions
 */
class ProcessScheduledActions extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/listing/product/process_scheduled_actions';

    const LIST_PRIORITY               = 25;
    const RELIST_PRIORITY             = 125;
    const STOP_PRIORITY               = 1000;
    const REVISE_QTY_PRIORITY         = 500;
    const REVISE_PRICE_PRIORITY       = 250;
    const REVISE_TITLE_PRIORITY       = 50;
    const REVISE_SUBTITLE_PRIORITY    = 50;
    const REVISE_DESCRIPTION_PRIORITY = 50;
    const REVISE_IMAGES_PRIORITY      = 50;
    const REVISE_CATEGORIES_PRIORITY  = 50;
    const REVISE_PAYMENT_PRIORITY     = 50;
    const REVISE_SHIPPING_PRIORITY    = 50;
    const REVISE_RETURN_PRIORITY      = 50;
    const REVISE_OTHER_PRIORITY       = 50;

    //####################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    //####################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     */
    protected function performActions()
    {
        $limit = $this->calculateActionsCountLimit();
        if ($limit <= 0) {
            return;
        }

        $scheduledActions = $this->getScheduledActionsForProcessing($limit);
        if (empty($scheduledActions)) {
            return;
        }

        $iteration = 0;
        $percentsForOneAction = 100 / count($scheduledActions);

        foreach ($scheduledActions as $scheduledAction) {
            try {
                $listingProduct = $scheduledAction->getListingProduct();
                $additionalData = $scheduledAction->getAdditionalData();
            } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
                $this->getHelper('Module\Exception')->process($e, false);
                $scheduledAction->delete();

                continue;
            }

            $params = [];
            if (!empty($additionalData['params'])) {
                $params = $additionalData['params'];
            }

            $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');
            if (!empty($additionalData['configurator'])) {
                $configurator->setUnserializedData($additionalData['configurator']);
                $configurator->setParams($params);
            }

            $listingProduct->setActionConfigurator($configurator);

            $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Item_Dispatcher');
            $dispatcher->process($scheduledAction->getActionType(), [$listingProduct], $params);

            $scheduledAction->delete();

            if ($iteration % 10 == 0) {
                $this->eventManager->dispatch(
                    \Ess\M2ePro\Model\Cron\Strategy\AbstractModel::PROGRESS_SET_DETAILS_EVENT_NAME,
                    [
                        'progress_nick' => self::NICK,
                        'percentage'    => ceil($percentsForOneAction * $iteration),
                        'total'         => count($scheduledActions)
                    ]
                );
            }

            $iteration++;
        }
    }

    //####################################

    /**
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function calculateActionsCountLimit()
    {
        $maxAllowedActionsCount = (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            '/ebay/listing/product/scheduled_actions/',
            'max_prepared_actions_count'
        );

        if ($maxAllowedActionsCount <= 0) {
            return 0;
        }

        $currentActionsCount = $this->activeRecordFactory
            ->getObject('Ebay_Listing_Product_Action_Processing')->getCollection()
            ->getSize();

        if ($currentActionsCount > $maxAllowedActionsCount) {
            return 0;
        }

        return $maxAllowedActionsCount - $currentActionsCount;
    }

    /**
     * @param $limit
     * @return array|\Magento\Framework\DataObject[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     */
    protected function getScheduledActionsForProcessing($limit)
    {
        $connection = $this->resource->getConnection();

        $unionSelect = $connection->select()->union([
            $this->getListScheduledActionsPreparedCollection()->getSelect(),
            $this->getRelistScheduledActionsPreparedCollection()->getSelect(),
            $this->getReviseQtyScheduledActionsPreparedCollection()->getSelect(),
            $this->getRevisePriceScheduledActionsPreparedCollection()->getSelect(),
            $this->getReviseTitleScheduledActionsPreparedCollection()->getSelect(),
            $this->getReviseSubtitleScheduledActionsPreparedCollection()->getSelect(),
            $this->getReviseDescriptionScheduledActionsPreparedCollection()->getSelect(),
            $this->getReviseImagesScheduledActionsPreparedCollection()->getSelect(),
            $this->getReviseCategoriesScheduledActionsPreparedCollection()->getSelect(),
            $this->getRevisePaymentScheduledActionsPreparedCollection()->getSelect(),
            $this->getReviseShippingScheduledActionsPreparedCollection()->getSelect(),
            $this->getReviseReturnScheduledActionsPreparedCollection()->getSelect(),
            $this->getReviseOtherScheduledActionsPreparedCollection()->getSelect(),
            $this->getStopScheduledActionsPreparedCollection()->getSelect(),
        ]);

        $unionSelect->order(['coefficient DESC']);
        $unionSelect->order(['create_date ASC']);

        $unionSelect->distinct(true);
        $unionSelect->limit($limit);

        $scheduledActionsData = $unionSelect->query()->fetchAll();
        if (empty($scheduledActionsData)) {
            return [];
        }

        $scheduledActionsIds = [];
        foreach ($scheduledActionsData as $scheduledActionData) {
            $scheduledActionsIds[] = $scheduledActionData['id'];
        }

        $scheduledActionsCollection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')
            ->getCollection();
        $scheduledActionsCollection->addFieldToFilter('id', array_unique($scheduledActionsIds));

        return $scheduledActionsCollection->getItems();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getListScheduledActionsPreparedCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();

        return $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->getScheduledActionsPreparedCollection(
                self::LIST_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_LIST
            );
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getRelistScheduledActionsPreparedCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();

        return $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->getScheduledActionsPreparedCollection(
                self::RELIST_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST
            );
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getReviseQtyScheduledActionsPreparedCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();

        return $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_QTY_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->addTagFilter('qty');
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getRevisePriceScheduledActionsPreparedCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();

        return $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_PRICE_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->addTagFilter('price');
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getReviseTitleScheduledActionsPreparedCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();

        return $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_TITLE_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->addTagFilter('title');
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getReviseSubtitleScheduledActionsPreparedCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();

        return $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_SUBTITLE_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->addTagFilter('subtitle');
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getReviseDescriptionScheduledActionsPreparedCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();

        return $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_DESCRIPTION_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->addTagFilter('description');
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getReviseImagesScheduledActionsPreparedCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();

        return $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_IMAGES_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->addTagFilter('images');
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getReviseCategoriesScheduledActionsPreparedCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();

        return $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_CATEGORIES_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->addTagFilter('categories');
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getRevisePaymentScheduledActionsPreparedCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();

        return $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_PAYMENT_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->addTagFilter('payment');
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getReviseShippingScheduledActionsPreparedCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();

        return $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_SHIPPING_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->addTagFilter('shipping');
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getReviseReturnScheduledActionsPreparedCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();

        return $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_RETURN_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->addTagFilter('return');
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getReviseOtherScheduledActionsPreparedCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();

        return $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->getScheduledActionsPreparedCollection(
                self::REVISE_OTHER_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            )
            ->addTagFilter('other');
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getStopScheduledActionsPreparedCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();

        return $collection->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->getScheduledActionsPreparedCollection(
                self::STOP_PRIORITY,
                \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
            );
    }

    //####################################
}
