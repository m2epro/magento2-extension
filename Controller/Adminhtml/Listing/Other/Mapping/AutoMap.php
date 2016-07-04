<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Mapping;

use Ess\M2ePro\Controller\Adminhtml\Listing;
use Ess\M2ePro\Controller\Adminhtml\Context;

class AutoMap extends Listing
{
    protected $parentFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        Context $context
    )
    {
        $this->parentFactory = $parentFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('componentMode');
        $productIds = $this->getRequest()->getParam('product_ids');

        if (empty($productIds)) {
            $this->setAjaxContent('You should select one or more Products', false);
            return $this->getResult();
        }

        if (empty($componentMode)) {
            $this->setAjaxContent('Component is not defined.', false);
            return $this->getResult();
        }

        $productIds = explode(',', $productIds);

        $productsForMapping = array();
        foreach ($productIds as $productId) {

            /** @var $listingOther \Ess\M2ePro\Model\Listing\Other */
            $listingOther = $this->parentFactory
                ->getObjectLoaded($componentMode,'Listing\Other',$productId);

            if ($listingOther->getProductId()) {
                continue;
            }

            $productsForMapping[] = $listingOther;
        }

        $componentMode = ucfirst(strtolower($componentMode));
        $mappingModel = $this->modelFactory->getObject($componentMode.'\Listing\Other\Mapping');
        $mappingModel->initialize();

        if (!$mappingModel->autoMapOtherListingsProducts($productsForMapping)) {
            $this->setAjaxContent('1', false);
            return $this->getResult();
        }
    }
}