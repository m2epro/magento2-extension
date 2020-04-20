<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Variation\Product\Manage;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Variation\Product\Manage\SetIdentifiers
 */
class SetIdentifiers extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    public function execute()
    {
        $variationsId = $this->getRequest()->getParam('variation_id');
        $productDetails   = $this->getRequest()->getParam('product_details');

        if (empty($variationsId) || empty($productDetails)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $variation */
        $variation = $this->ebayFactory->getObjectLoaded('Listing_Product_Variation', $variationsId);

        $data = [];
        foreach ($productDetails as $key => $value) {
            if (!empty($value)) {
                $data[$key] = $value;
            }
        }

        $additionalData = $variation->getAdditionalData();
        $additionalData['product_details'] = $data;
        $variation->setData(
            'additional_data',
            $this->getHelper('Data')->jsonEncode($additionalData)
        )->save();

        $this->setJsonContent(['success' => true]);
        return $this->getResult();
    }
}
