<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;
use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;
use Ess\M2ePro\Model\Walmart\Template\ChangeProcessor\ChangeProcessorAbstract;
use Ess\M2ePro\Model\Walmart\Template\Description\ChangeProcessor;

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
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource */
        $instructionResource = $this->activeRecordFactory
            ->getObject('Listing_Product_Instruction')
            ->getResource();
        foreach ($typeModel->getChildListingsProducts() as $childListingProduct) {
            $instructionResource->addForComponent(
                [
                    'listing_product_id' => $childListingProduct->getId(),
                    'type' => ChangeProcessorAbstract::INSTRUCTION_TYPE_DETAILS_DATA_CHANGED,
                    'initiator' => ChangeProcessor::INSTRUCTION_INITIATOR,
                    'priority' => 10,
                ],
                \Ess\M2ePro\Helper\Component\Walmart::NICK
            );
        }

        $this->setJsonContent([
            'success' => true,
        ]);

        return $this->getResult();
    }
}
