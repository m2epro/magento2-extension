<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Mapping;

use Ess\M2ePro\Controller\Adminhtml\Listing;
use Ess\M2ePro\Controller\Adminhtml\Context;

class Map extends Listing
{
    protected $parentFactory;
    protected $productFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        Context $context
    )
    {
        $this->parentFactory = $parentFactory;
        $this->productFactory = $productFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('componentMode');
        $productId = $this->getRequest()->getPost('productId');
        $sku = $this->getRequest()->getPost('sku');
        $productOtherId = $this->getRequest()->getPost('otherProductId');

        if ((!$productId && !$sku) || !$productOtherId || !$componentMode) {
            return;
        }

        $collection = $this->productFactory->create()->getCollection();

        $productId && $collection->addFieldToFilter('entity_id', $productId);
        $sku && $collection->addFieldToFilter('sku', $sku);

        $tempData = $collection->getSelect()->query()->fetch();
        if (!$tempData) {
            $this->setAjaxContent('1', false);
            return $this->getResult();
        }

        $productId || $productId = $tempData['entity_id'];

        $productOtherInstance = $this->parentFactory->getObjectLoaded(
            $componentMode,'Listing\Other',$productOtherId
        );

        $productOtherInstance->mapProduct($productId, \Ess\M2ePro\Helper\Data::INITIATOR_USER);

        $this->setAjaxContent('0', false);
        return $this->getResult();
    }
}