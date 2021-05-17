<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Mapping;

use Ess\M2ePro\Controller\Adminhtml\Listing;
use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Listing\Other\Mapping\Map
 */
class Map extends Listing
{
    protected $productFactory;

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        Context $context
    ) {
        $this->productFactory = $productFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('component_mode');
        $productId = $this->getRequest()->getPost('product_id');
        $productOtherId = $this->getRequest()->getPost('other_product_id');

        if (!$productId || !$productOtherId || !$componentMode) {
            $this->setJsonContent(['result' => false]);
            return $this->getResult();
        }

        $collection = $this->productFactory->create()->getCollection();

        $productId && $collection->addFieldToFilter('entity_id', $productId);

        $magentoCatalogProductModel = $collection->getFirstItem();
        if ($magentoCatalogProductModel->isEmpty()) {
            $this->setJsonContent(['result' => false]);
            return $this->getResult();
        }

        $productId || $productId = $magentoCatalogProductModel->getId();

        $productOtherInstance = $this->parentFactory->getObjectLoaded(
            $componentMode,
            'Listing\Other',
            $productOtherId
        );

        $productOtherInstance->mapProduct($productId);

        $this->setJsonContent(['result' => true]);
        return $this->getResult();
    }
}
