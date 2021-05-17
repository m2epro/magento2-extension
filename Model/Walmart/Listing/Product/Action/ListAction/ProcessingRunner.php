<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\ListAction;

use \Ess\M2ePro\Model\Walmart\Listing\Product\Action\ProcessingList as ProcessingList;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\ListAction\ProcessingRunner
 */
class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Single\Runner
{
    const PENDING_REQUEST_MAX_LIFE_TIME = 86400;
    const MAX_LIFETIME                  = 172800;

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct = null;

    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Processing $processingAction */
    protected $processingAction = null;

    protected $walmartFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($parentFactory, $activeRecordFactory, $helperFactory, $modelFactory);

        $this->walmartFactory = $walmartFactory;
    }

    //########################################

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;

        return $this;
    }

    public function setProcessingAction(\Ess\M2ePro\Model\Walmart\Listing\Product\Action\Processing $processingAction)
    {
        $this->processingAction = $processingAction;

        return $this;
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

        $accountId = (int)$params['account_id'];
        $sku = (string)$params['requester_params']['sku'];

        $processingActionList = $this->activeRecordFactory->getObject('Walmart_Listing_Product_Action_ProcessingList');
        $processingActionList->setData(
            [
                'account_id'           => $accountId,
                'processing_action_id' => $this->getProcessingAction()->getId(),
                'listing_product_id'   => $this->getListingProduct()->getId(),
                'sku'                  => $sku,
                'stage'                => ProcessingList::STAGE_LIST_DETAILS
            ]
        );
        $processingActionList->save();
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

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Processing $processingAction */
        $processingAction = $this->activeRecordFactory->getObject('Walmart_Listing_Product_Action_Processing');
        $processingAction->setData(
            [
                'listing_product_id' => $params['listing_product_id'],
                'processing_id'      => $this->getProcessingObject()->getId(),
                'type'               => \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Processing::TYPE_ADD,
                'is_prepared'        => 0,
                'group_hash'         => $params['group_hash'],
            ]
        );
        $processingAction->save();
    }

    protected function eventAfter()
    {
        $params = $this->getParams();

        $accountId = (int)$params['account_id'];
        $sku = (string)$params['request_data']['sku'];

        $processingActionListSkuCollection = $this->activeRecordFactory
            ->getObject('Walmart_Listing_Product_Action_ProcessingList')->getCollection();

        $processingActionListSkuCollection->addFieldToFilter('account_id', $accountId);
        $processingActionListSkuCollection->addFieldToFilter('sku', $sku);

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\ProcessingList $processingActionListSku */
        $processingActionListSku = $processingActionListSkuCollection->getFirstItem();

        if ($processingActionListSku->getId()) {
            $processingActionListSku->delete();
        }
    }

    //########################################

    public function processAddResult()
    {
        try {
            /** @var \Ess\M2ePro\Model\Walmart\Connector\Product\ListAction\Responser $responser */
            $responser = $this->getResponser();
            $responser->process();
        } catch (\Exception $exception) {
            $this->getResponser()->failDetected($exception->getMessage());

            return false;
        }

        return $this->getResponser()->isSuccess();
    }

    public function processRelistResult(ProcessingList $processingList, array $resultData)
    {
        try {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response $response */
            $response = $this->modelFactory->getObject('Connector_Connection_Response');
            $response->initFromPreparedResponse($resultData);

            $responser = $this->modelFactory
                ->getObject(
                    'Walmart_Connector_Product_ListAction_UpdateInventory_Responser',
                    [
                        'params'   => $this->getResponserParams(),
                        'response' => $response
                    ]
                );

            $responser->setParams($this->getResponserParams());
            $responser->setResponse($response);

            $responser->setProcessingList($processingList);
            $responser->process();
        } catch (\Exception $exception) {
            $responser->failDetected($exception->getMessage());

            return false;
        }

        return $responser->isSuccess();
    }

    //########################################

    public function complete()
    {
        // listing product can be removed during processing action
        if ($this->getListingProduct()->getId() === null) {
            $this->getProcessingObject()->delete();

            return;
        }

        parent::complete();
    }

    //########################################

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

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $this->getListingProduct()->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();

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

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $this->getListingProduct()->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();

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

    protected function getListingProduct()
    {
        if ($this->listingProduct !== null) {
            return $this->listingProduct;
        }

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartFactory->getObject('Listing_Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $params['listing_product_id']]);

        return $this->listingProduct = $collection->getFirstItem();
    }

    protected function getProcessingAction()
    {
        if ($this->processingAction !== null) {
            return $this->processingAction;
        }

        $processingActionCollection = $this->activeRecordFactory
            ->getObject('Walmart_Listing_Product_Action_Processing')->getCollection();
        $processingActionCollection->addFieldToFilter('processing_id', $this->getProcessingObject()->getId());

        $processingAction = $processingActionCollection->getFirstItem();

        return $processingAction->getId() ? $this->processingAction = $processingAction : null;
    }

    //########################################
}
