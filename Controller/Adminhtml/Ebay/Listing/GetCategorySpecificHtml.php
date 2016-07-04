<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class GetCategorySpecificHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        // ---------------------------------------
        $listingId = $this->getRequest()->getParam('id');
        $listingProductIds = $this->getRequestIds();
        $categoryMode = $this->getRequest()->getParam('category_mode');
        $categoryValue = $this->getRequest()->getParam('category_value');
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);
        // ---------------------------------------

        /* @var $specific \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Specific */
        $specific = $this->createBlock('Ebay\Listing\Product\Category\Settings\Specific');
        $specific->setMarketplaceId($listing->getMarketplaceId());
        $specific->setCategoryMode($categoryMode);
        $specific->setCategoryValue($categoryValue);

        // ---------------------------------------
        /* @var $template \Ess\M2ePro\Model\Ebay\Template\Category|null */
        $template = NULL;

        $templateIds = $this->activeRecordFactory
                            ->getObject('Ebay\Listing\Product')
                            ->getResource()
                            ->getTemplateCategoryIds($listingProductIds);

        $templateIds    = array_values($templateIds);
        $countTemplates = count($templateIds);

        $isChanged   = false;
        $isDifferent = false;

        foreach ($templateIds as $templateId) {
            $tempTemplate = $this->getCategoryTemplate($templateId);

            if ($this->isMainCategoryWasChanged($tempTemplate, $categoryValue)) {
                $template = $this->loadLastCategoryTemplate(
                    $categoryMode, $categoryValue, $listing->getMarketplaceId()
                );

                $isChanged = true;
                break;
            }
        }

        if (!$isChanged && $countTemplates > 0) {
            if ($countTemplates == 1 && $templateId = reset($templateIds)) {
                $template = $this->getCategoryTemplate($templateId);
            } else {
                for ($i = 0; $i < $countTemplates - 1; $i++) {
                    $templateCurr = $this->getCategoryTemplate($templateIds[$i]);
                    $templateNext = $this->getCategoryTemplate($templateIds[$i + 1]);

                    if ($this->isDifferentSpecifics($templateCurr->getSpecifics(),
                        $templateNext->getSpecifics())) {
                        $isDifferent = true;
                        break;
                    }
                }

                !$isDifferent && $template = $templateNext;
            }
        }

        if ($template) {
            $specific->setInternalData($template->getData());
            $specific->setSelectedSpecifics($template->getSpecifics());
        }

        // ---------------------------------------
        $wrapper = $this->createBlock(
            'Ebay\Listing\View\Settings\Category\Specific\Wrapper'
        );
        $wrapper->setChild('specific', $specific);
        // ---------------------------------------

        $this->setAjaxContent($wrapper);
        return $this->getResult();
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
            'Ebay\Template\Category', (int)$id
        );
    }

    private function isMainCategoryWasChanged(\Ess\M2ePro\Model\Ebay\Template\Category $template, $selectedValue)
    {
        return $template->getData('category_main_id') != $selectedValue &&
               $template->getData('category_main_attribute') != $selectedValue;
    }

    private function loadLastCategoryTemplate($mode, $categoryValue, $marketplaceId)
    {
        $templateData = array(
            'category_main_id'        => 0,
            'category_main_mode'      => $mode,
            'category_main_attribute' => '',
            'marketplace_id'          => $marketplaceId
        );

        if ($mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
            $templateData['category_main_id'] = $categoryValue;
        } elseif ($mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            $templateData['category_main_attribute'] = $categoryValue;
        }

        $existingTemplates = $this->activeRecordFactory
                                    ->getObject('Ebay\Template\Category')
                                    ->getCollection()
                                    ->getItemsByPrimaryCategories(array($templateData));

        return reset($existingTemplates);
    }

    private function isDifferentSpecifics(array $firstSpecifics, array $secondSpecifics)
    {
        $model = $this->activeRecordFactory->getObject('Ebay\Template\Category')->getResource();
        return $model->isDifferent(
            array('specifics' => $firstSpecifics), array('specifics' => $secondSpecifics)
        );
    }
}