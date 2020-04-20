<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add\DescriptionTemplateAssignType
 */
class DescriptionTemplateAssignType extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $listingId = $this->getRequest()->getParam('id');
        $listingProductsIds = $this->getRequest()->getParam('products_ids');

        $mode = $this->getRequest()->getParam('mode');
        $descriptionTemplateId = $this->getRequest()->getParam('description_template_id');

        if (empty($listingId) || empty($mode)) {
            $this->_forward('index');
            return;
        }

        if (!is_array($listingProductsIds)) {
            $listingProductsIds = explode(',', $listingProductsIds);
        }

        $listing = $this->amazonFactory->getObjectLoaded('Listing', $listingId);
        $listingAdditionalData = $listing->getData('additional_data');
        $listingAdditionalData = $this->getHelper('Data')->jsonDecode($listingAdditionalData);

        $listingAdditionalData['new_asin_mode'] = $mode;

        $listing->setData(
            'additional_data',
            $this->getHelper('Data')->jsonEncode($listingAdditionalData)
        )->save();

        if ($mode == 'same' && !empty($descriptionTemplateId)) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\Description $descriptionTemplate */
            $descriptionTemplate = $this->amazonFactory->getObjectLoaded(
                'Template\Description',
                $descriptionTemplateId
            );

            if (!$descriptionTemplate->isEmpty()) {
                if (!empty($listingProductsIds)) {
                    $this->setDescriptionTemplate($listingProductsIds, $descriptionTemplateId);
                    $this->_forward('mapToNewAsin', 'amazon_listing_product');
                }

                return $this->_redirect('*/amazon_listing_product_add/index', [
                    '_current' => true,
                    'step' => 5
                ]);
            }

            unset($listingAdditionalData['new_asin_mode']);

            $listing->setData(
                'additional_data',
                $this->getHelper('Data')->jsonEncode($listingAdditionalData)
            )->save();
        } elseif ($mode == 'category') {
            return $this->_redirect('*/*/descriptionTemplateAssignByMagentoCategory', [
                '_current' => true,
            ]);
        } elseif ($mode == 'manually') {
            return $this->_redirect('*/*/descriptionTemplateAssignManually', [
                '_current' => true,
            ]);
        }

        $this->_forward('index');
    }

    //########################################
}
