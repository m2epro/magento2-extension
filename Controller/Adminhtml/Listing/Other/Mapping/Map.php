<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Mapping;

class Map extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    /** @var \Magento\Catalog\Model\ProductFactory */
    private $productFactory;

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->productFactory = $productFactory;
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
