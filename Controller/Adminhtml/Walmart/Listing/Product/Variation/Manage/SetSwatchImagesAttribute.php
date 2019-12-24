<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;
use Ess\M2ePro\Model\Listing\Product;
use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Manage\SetSwatchImagesAttribute
 */
class SetSwatchImagesAttribute extends Main
{
    public function execute()
    {
        $listingProductId = $this->getRequest()->getParam('product_id');
        $attribute = $this->getRequest()->getParam('attribute', null);

        if (empty($listingProductId) || $attribute === null) {
            $this->setAjaxContent('You should provide correct parameters.');
            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $listingProductId);
        $listingProduct->setSetting('additional_data', 'variation_swatch_images_attribute', $attribute);
        $listingProduct->save();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

        /** @var ParentRelation $parentTypeModel */
        $typeModel = $walmartListingProduct->getVariationManager()->getTypeModel();

        foreach ($typeModel->getChildListingsProducts() as $childListingProduct) {
            /** @var Product $childListingProduct */
            $synchReasons = $childListingProduct->getData('synch_reasons');

            if ($synchReasons) {
                $childListingProduct->setData(
                    'synch_reasons',
                    $synchReasons . ',' . \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Description::SYNCH_REASON
                );
            } else {
                $childListingProduct->setData(
                    'synch_reasons',
                    \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Description::SYNCH_REASON
                );
            }

            $childListingProduct->save();
        }

        $this->setJsonContent([
            'success' => true,
        ]);

        return $this->getResult();
    }
}
