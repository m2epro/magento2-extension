<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Product\ProcessingRunner
 */
class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Single\Runner
{
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct = null;

    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Processing $processingAction */
    protected $processingAction = null;

    //########################################

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
        return $this;
    }

    public function setProcessingAction(\Ess\M2ePro\Model\Amazon\Listing\Product\Action\Processing $processingAction)
    {
        $this->processingAction = $processingAction;
        return $this;
    }

    //########################################

    public function processSuccess()
    {
        // listing product can be removed during processing action
        if ($this->getListingProduct()->getId() === null) {
            return true;
        }

        return parent::processSuccess();
    }

    public function processExpired()
    {
        // listing product can be removed during processing action
        if ($this->getListingProduct()->getId() === null) {
            return;
        }

        if ($this->getProcessingAction() && !$this->getProcessingAction()->isPrepared()) {
            return;
        }

        $this->getResponser()->failDetected($this->getExpiredErrorMessage());
    }

    public function complete()
    {
        // listing product can be removed during processing action
        if ($this->getListingProduct()->getId() === null) {
            $this->getProcessingObject()->delete();
            return;
        }

        if ($this->getProcessingAction() && !$this->getProcessingAction()->isPrepared()) {
            $this->stop();
            return;
        }

        parent::complete();
    }

    //########################################

    public function prepare()
    {
        if ($this->getProcessingObject() === null || !$this->getProcessingObject()->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Processing does not exist.');
        }

        if ($this->getProcessingAction() === null || !$this->getProcessingAction()->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Processing Action does not exist.');
        }

        $params = $this->getParams();

        $this->getProcessingObject()->setSettings('params', $this->getParams())->save();

        $this->getProcessingAction()->setData('is_prepared', 1);
        $this->getProcessingAction()->setData(
            'request_data',
            $this->getHelper('Data')->jsonEncode($params['request_data'])
        );
        $this->getProcessingAction()->save();
    }

    public function stop()
    {
        if ($this->getProcessingObject() === null || !$this->getProcessingObject()->getId()) {
            return;
        }

        if ($this->getProcessingAction() === null || !$this->getProcessingAction()->getId()) {
            return;
        }

        $this->getProcessingAction()->delete();
        $this->getProcessingObject()->delete();

        $this->unsetLocks();
    }

    //########################################

    protected function eventBefore()
    {
        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Processing $processingAction */
        $processingAction = $this->activeRecordFactory->getObject('Amazon_Listing_Product_Action_Processing');
        $processingAction->setData(
            [
                'listing_product_id' => $params['listing_product_id'],
                'processing_id' => $this->getProcessingObject()->getId(),
                'type' => $this->getProcessingActionType(),
                'is_prepared' => 0,
                'group_hash' => $params['group_hash']
            ]
        );
        $processingAction->save();
    }

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        $this->getListingProduct()->addProcessingLock(null, $this->getProcessingObject()->getId());
        $this->getListingProduct()->addProcessingLock('in_action', $this->getProcessingObject()->getId());
        $this->getListingProduct()->addProcessingLock(
            $params['lock_identifier'] . '_action',
            $this->getProcessingObject()->getId()
        );

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->getListingProduct()->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();

        if ($variationManager->isRelationChildType()) {
            /** @var \Ess\M2ePro\Model\Listing\Product $parentListingProduct */
            $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();

            $parentListingProduct->addProcessingLock(null, $this->getProcessingObject()->getId());
            $parentListingProduct->addProcessingLock(
                'child_products_in_action',
                $this->getProcessingObject()->getId()
            );
        }

        $this->getListingProduct()->getListing()->addProcessingLock(null, $this->getProcessingObject()->getId());
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $params = $this->getParams();

        $this->getListingProduct()->deleteProcessingLocks(null, $this->getProcessingObject()->getId());
        $this->getListingProduct()->deleteProcessingLocks('in_action', $this->getProcessingObject()->getId());
        $this->getListingProduct()->deleteProcessingLocks(
            $params['lock_identifier'] . '_action',
            $this->getProcessingObject()->getId()
        );

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->getListingProduct()->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();

        if ($variationManager->isRelationChildType()) {
            /** @var \Ess\M2ePro\Model\Listing\Product $parentListingProduct */
            $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();

            $parentListingProduct->deleteProcessingLocks(null, $this->getProcessingObject()->getId());
            $parentListingProduct->deleteProcessingLocks(
                'child_products_in_action',
                $this->getProcessingObject()->getId()
            );
        }

        $this->getListingProduct()->getListing()->deleteProcessingLocks(null, $this->getProcessingObject()->getId());
    }

    //########################################

    protected function getProcessingActionType()
    {
        $params = $this->getParams();

        switch ($params['action_type']) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Processing::TYPE_ADD;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
            case \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE:
            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Processing::TYPE_UPDATE;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE:
                return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Processing::TYPE_DELETE;

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action type.');
        }
    }

    protected function getListingProduct()
    {
        if ($this->listingProduct !== null) {
            return $this->listingProduct;
        }

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Listing\Product'
        )->getCollection();
        $collection->addFieldToFilter('id', ['in' => $params['listing_product_id']]);

        return $this->listingProduct = $collection->getFirstItem();
    }

    protected function getProcessingAction()
    {
        if ($this->processingAction !== null) {
            return $this->processingAction;
        }

        $processingActionCollection = $this->activeRecordFactory->getObject('Amazon_Listing_Product_Action_Processing')
            ->getCollection();

        $processingActionCollection->addFieldToFilter('processing_id', $this->getProcessingObject()->getId());

        $processingAction = $processingActionCollection->getFirstItem();

        return $processingAction->getId() ? $this->processingAction = $processingAction : null;
    }

    //########################################
}
