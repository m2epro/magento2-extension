<?php
/**
 * Created by PhpStorm.
 * User: myown
 * Date: 30.03.16
 * Time: 17:23
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Mapping;

use Ess\M2ePro\Controller\Adminhtml\Listing;
use Ess\M2ePro\Controller\Adminhtml\Context;

class Unmapping extends Listing
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

        if (!$productIds || !$componentMode) {
            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        $productArray = explode(',', $productIds);

        if (empty($productArray)) {
            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        foreach ($productArray as $productId) {
            $listingOtherProductInstance = $this->parentFactory->getObjectLoaded(
                $componentMode, 'Listing\Other', $productId
            );

            if (!$listingOtherProductInstance->getId() ||
                is_null($listingOtherProductInstance->getData('product_id'))) {
                continue;
            }

            $listingOtherProductInstance->unmapProduct(\Ess\M2ePro\Helper\Data::INITIATOR_USER);
        }

        $this->setAjaxContent('1', false);
        return $this->getResult();
    }
}