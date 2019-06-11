<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

class SetChannelAttributes extends Main
{
    public function execute()
    {
        $listingProductId = $this->getRequest()->getParam('product_id');
        $channelAttributes = $this->getRequest()->getParam('channel_attribute', null);

        if (empty($listingProductId) || is_null($channelAttributes)) {
            $this->setAjaxContent('You should provide correct parameters.');
            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product',$listingProductId);

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

        /**
         *@var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation $parentTypeModel
         */
        $parentTypeModel = $walmartListingProduct->getVariationManager()->getTypeModel();
        $parentTypeModel->setChannelAttributes($channelAttributes);

        $parentTypeModel->getProcessor()->process();

        $this->setJsonContent(array(
            'success' => true,
        ));

        return $this->getResult();
    }
}