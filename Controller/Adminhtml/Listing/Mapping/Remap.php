<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Mapping;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Listing;

/**
 * Class  \Ess\M2ePro\Controller\Adminhtml\Listing\Mapping\Remap
 */
class Remap extends Listing
{
    protected $magentoProductCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        Context $context
    ) {
        parent::__construct($context);
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
    }

    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('component_mode');
        $productId = $this->getRequest()->getPost('product_id');
        $listingProductId = $this->getRequest()->getPost('listing_product_id');

        if (!$productId || !$listingProductId || !$componentMode) {
            $this->setJsonContent(['result' => false]);

            return $this->getResult();
        }

        /** @var $magentoProduct \Ess\M2ePro\Model\Magento\Product */
        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProductId($productId);

        if (!$magentoProduct->exists()) {
            $this->setJsonContent(
                [
                    'result'  => false,
                    'message' => $this->__('Product does not exist.')
                ]
            );

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProductInstance */
        $listingProductInstance = $this->parentFactory->getObjectLoaded(
            $componentMode,
            'Listing\Product',
            $listingProductId
        );

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->activeRecordFactory->getObject('Listing_Product')->getCollection()
            ->addFieldToFilter('listing_id', $listingProductInstance->getListingId())
            ->addFieldToFilter('product_id', $productId);

        if (!$listingProductCollection->getFirstItem()->isEmpty()) {
            $this->setJsonContent(
                [
                    'result'  => false,
                    'message' => $this->__(
                        'Item cannot be linked to Magento Product that already exists in the Listing.'
                    )
                ]
            );

            return $this->getResult();
        }

        if ($listingProductInstance->isSetProcessingLock()) {
            $this->setJsonContent(
                [
                    'result'  => false,
                    'message' => $this->__(
                        'Another Action is being processed. Please wait until the Action is completed.'
                    )
                ]
            );

            return $this->getResult();
        }

        $listingProductInstance->remapProduct($magentoProduct);

        $this->setJsonContent(
            [
                'result'  => true,
                'message' => $this->__('Product(s) was Linked.')
            ]
        );

        return $this->getResult();
    }

    //########################################
}
