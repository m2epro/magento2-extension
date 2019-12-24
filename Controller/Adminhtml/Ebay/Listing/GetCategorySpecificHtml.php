<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\GetCategorySpecificHtml
 */
class GetCategorySpecificHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        // ---------------------------------------
        $listingId = $this->getRequest()->getParam('id');
        $listingProductIds = $this->getRequestIds('ids');
        $categoryMode = $this->getRequest()->getParam('category_mode');
        $categoryValue = $this->getRequest()->getParam('category_value');
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);
        // ---------------------------------------

        /** @var $specific \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Specific */
        $specific = $this->createBlock('Ebay_Listing_Product_Category_Settings_Specific');
        $specific->setMarketplaceId($listing->getMarketplaceId());
        $specific->setCategoryMode($categoryMode);
        $specific->setCategoryValue($categoryValue);

        // ---------------------------------------

        $template = $this->identifyCategoryTemplate($listingProductIds, $listingId, $categoryValue, $categoryMode);

        if ($template) {
            $specific->setInternalData($template->getData());
            $specific->setSelectedSpecifics($template->getSpecifics());
        }

        // ---------------------------------------
        $wrapper = $this->createBlock(
            'Ebay_Listing_View_Settings_Category_Specific_Wrapper'
        );
        $wrapper->setChild('specific', $specific);
        // ---------------------------------------

        $this->setAjaxContent($wrapper);
        return $this->getResult();
    }

    //########################################

    /**
     * @param array $listingProductIds
     * @param $listingId
     * @param $categoryValue
     * @param $categoryMode
     * @return \Ess\M2ePro\Model\Ebay\Template\Category|null
     */
    private function identifyCategoryTemplate(array $listingProductIds, $listingId, $categoryValue, $categoryMode)
    {
        /** @var $template \Ess\M2ePro\Model\Ebay\Template\Category|null */
        $template       = null;
        $listing        = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);
        $templateIds    = $this->getCategoryTemplateCandidates($listingProductIds, $listingId);
        $countTemplates = count($templateIds);

        if ($countTemplates == 0) {
            return null;
        }

        foreach ($templateIds as $templateId) {
            $tempTemplate = $this->getCategoryTemplate($templateId);

            if ($this->isMainCategoryWasChanged($tempTemplate, $categoryValue)) {
                $template = $this->loadLastCategoryTemplate(
                    $categoryMode,
                    $categoryValue,
                    $listing->getMarketplaceId()
                );

                return $template;
            }
        }

        if ($countTemplates == 1) {
            $templateId = reset($templateIds);
            $template = $this->getCategoryTemplate($templateId);
        } else {
            $isDifferent = false;
            for ($i = 0; $i < $countTemplates - 1; $i++) {
                $templateCurr = $this->getCategoryTemplate($templateIds[$i]);
                $templateNext = $this->getCategoryTemplate($templateIds[$i + 1]);

                if ($this->isDifferentSpecifics($templateCurr->getSpecifics(), $templateNext->getSpecifics())) {
                    $isDifferent = true;
                    break;
                }
            }

            !$isDifferent && $template = $templateNext;
        }

        return $template;
    }

    /**
     * @param array $listingProductIds
     * @param $listingId
     * @return array
     */
    private function getCategoryTemplateCandidates(array $listingProductIds, $listingId)
    {
        $templateIds = $this->activeRecordFactory
            ->getObject('Ebay_Listing_Product')
            ->getResource()
            ->getTemplateCategoryIds($listingProductIds);
        /**
         * If there are no templates for particular listing product ids, consider action as assigning new template
         */
        if (empty($templateIds)) {
            $templateIds = $this->activeRecordFactory
                ->getObject('Ebay\Listing')
                ->getResource()
                ->getTemplateCategoryIds($listingId);
        }

        return array_values($templateIds);
    }

    //########################################

    /**
     * @param $id
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getCategoryTemplate($id)
    {
        return $this->activeRecordFactory->getCachedObjectLoaded(
            'Ebay_Template_Category',
            (int)$id
        );
    }

    private function isMainCategoryWasChanged(\Ess\M2ePro\Model\Ebay\Template\Category $template, $selectedValue)
    {
        return $template->getData('category_main_id') != $selectedValue &&
               $template->getData('category_main_attribute') != $selectedValue;
    }

    private function loadLastCategoryTemplate($mode, $categoryValue, $marketplaceId)
    {
        $templateData = [
            'category_main_id'        => 0,
            'category_main_mode'      => $mode,
            'category_main_attribute' => '',
            'marketplace_id'          => $marketplaceId
        ];

        if ($mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
            $templateData['category_main_id'] = $categoryValue;
        } elseif ($mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            $templateData['category_main_attribute'] = $categoryValue;
        }

        $existingTemplates = $this->activeRecordFactory
                                    ->getObject('Ebay_Template_Category')
                                    ->getCollection()
                                    ->getItemsByPrimaryCategories([$templateData]);

        return reset($existingTemplates);
    }

    private function isDifferentSpecifics(array $firstSpecifics, array $secondSpecifics)
    {
        $model = $this->activeRecordFactory->getObject('Ebay_Template_Category')->getResource();
        return $model->isDifferent(
            ['specifics' => $firstSpecifics],
            ['specifics' => $secondSpecifics]
        );
    }
}
