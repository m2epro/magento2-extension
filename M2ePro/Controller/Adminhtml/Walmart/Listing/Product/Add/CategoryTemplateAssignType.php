<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add\CategoryTemplateAssignType
 */
class CategoryTemplateAssignType extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $listingId = $this->getRequest()->getParam('id');
        $listingProductsIds = $this->getRequest()->getParam('products_ids');

        $mode = $this->getRequest()->getParam('mode');
        $categoryTemplateId = $this->getRequest()->getParam('category_template_id');

        if (empty($listingId) || empty($mode)) {
            $this->_forward('index');
            return;
        }

        if (!is_array($listingProductsIds)) {
            $listingProductsIds = explode(',', $listingProductsIds);
        }

        $listing = $this->walmartFactory->getObjectLoaded('Listing', $listingId);
        $listingAdditionalData = $listing->getData('additional_data');
        $listingAdditionalData = $this->getHelper('Data')->jsonDecode($listingAdditionalData);

        $listingAdditionalData['category_template_mode'] = $mode;

        $listing->setData(
            'additional_data',
            $this->getHelper('Data')->jsonEncode($listingAdditionalData)
        )->save();

        if ($mode == 'same' && !empty($categoryTemplateId)) {
            /** @var \Ess\M2ePro\Model\Walmart\Template\Category $categoryTemplate */
            $categoryTemplate = $this->activeRecordFactory->getObjectLoaded(
                'Walmart_Template_Category',
                $categoryTemplateId
            );

            if (!$categoryTemplate->isEmpty()) {
                if (!empty($listingProductsIds)) {
                    $this->setCategoryTemplate($listingProductsIds, $categoryTemplateId);
                }

                return $this->_redirect('*/walmart_listing_product_add/index', [
                    '_current' => true,
                    'step' => 4
                ]);
            }

            unset($listingAdditionalData['category_template_mode']);

            $listing->setData(
                'additional_data',
                $this->getHelper('Data')->jsonEncode($listingAdditionalData)
            )->save();
        } elseif ($mode == 'category') {
            return $this->_redirect('*/*/categoryTemplateAssignByMagentoCategory', [
                '_current' => true,
            ]);
        } elseif ($mode == 'manually') {
            return $this->_redirect('*/*/categoryTemplateAssignManually', [
                '_current' => true,
            ]);
        }

        $this->_forward('index');
    }

    //########################################
}
