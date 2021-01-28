<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode as SourceModeBlock;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode as CategoryTemplateBlock;
use \Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;
use \Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings
 */
abstract class Settings extends Listing
{
    protected $sessionKey = 'ebay_listing_product_category_settings';

    //########################################

    protected function getSelectedListingProductsIdsByCategoriesIds($categoriesIds)
    {
        $productsIds = $this->getHelper('Magento\Category')->getProductsFromCategories($categoriesIds);

        $listingProductIds = $this->ebayFactory->getObject('Listing\Product')->getCollection()
            ->addFieldToFilter('product_id', ['in' => $productsIds])->getAllIds();

        return array_values(
            array_intersect(
                $this->getEbayListingFromRequest()->getAddedListingProductsIds(),
                $listingProductIds
            )
        );
    }

    //########################################

    protected function setWizardStep($step)
    {
        /** @var \Ess\M2ePro\Helper\Module\Wizard $wizardHelper */
        $wizardHelper = $this->getHelper('Module\Wizard');

        if (!$wizardHelper->isActive(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK)) {
            return;
        }

        $wizardHelper->setStep(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK, $step);
    }

    protected function endWizard()
    {
        /** @var \Ess\M2ePro\Helper\Module\Wizard $wizardHelper */
        $wizardHelper = $this->getHelper('Module\Wizard');

        if (!$wizardHelper->isActive(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK)) {
            return;
        }

        $wizardHelper->setStatus(
            \Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK,
            \Ess\M2ePro\Helper\Module\Wizard::STATUS_COMPLETED
        );

        $this->getHelper('Magento')->clearMenuCache();
    }

    //########################################

    protected function save($sessionData)
    {
        $listing = $this->getListingFromRequest();
        $sessionData = $this->convertCategoriesIdstoProductIds($sessionData);
        $sessionData = $this->prepareUniqueTemplatesData($sessionData);

        foreach ($sessionData as $hash => $templatesData) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\Converter $converter */
            $converter = $this->modelFactory->getObject('Ebay_Template_Category_Chooser_Converter');
            $converter->setAccountId($listing->getAccountId());
            $converter->setMarketplaceId($listing->getMarketplaceId());

            foreach ($templatesData as $categoryType => $templateData) {
                $listingProductsIds = $templateData['listing_products_ids'];
                $listingProductsIds = array_unique($listingProductsIds);
                unset($templateData['listing_products_ids']);

                if (empty($listingProductsIds)) {
                    continue;
                }

                if ($this->getHelper('Component_Ebay_Category')->isEbayCategoryType($categoryType)) {
                    $template = $this->activeRecordFactory->getObject('Ebay_Template_Category');
                    $builder = $this->modelFactory->getObject('Ebay_Template_Category_Builder');
                } else {
                    $template = $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory');
                    $builder = $this->modelFactory->getObject('Ebay_Template_StoreCategory_Builder');
                }

                $converter->setCategoryDataFromChooser($templateData, $categoryType);
                $categoryTpl = $builder->build($template, $converter->getCategoryDataForTemplate($categoryType));

                $this->activeRecordFactory->getObject('Ebay_Listing_Product')->assignTemplatesToProducts(
                    $listingProductsIds,
                    $categoryType == eBayCategory::TYPE_EBAY_MAIN ? $categoryTpl->getId() : null,
                    $categoryType == eBayCategory::TYPE_EBAY_SECONDARY ? $categoryTpl->getId() : null,
                    $categoryType == eBayCategory::TYPE_STORE_MAIN ? $categoryTpl->getId() : null,
                    $categoryType == eBayCategory::TYPE_STORE_SECONDARY ? $categoryTpl->getId() : null
                );
            }
        }

        $this->endWizard();
        $this->endListingCreation();
    }

    //########################################

    protected function isEbayPrimaryCategorySelected(
        $categoryData,
        \Ess\M2ePro\Model\Listing $listing,
        $validateSpecifics = true
    ) {
        if (!isset($categoryData[eBayCategory::TYPE_EBAY_MAIN]) ||
            $categoryData[eBayCategory::TYPE_EBAY_MAIN]['mode'] === TemplateCategory::CATEGORY_MODE_NONE
        ) {
            return false;
        }

        if (!$validateSpecifics) {
            return true;
        }

        if ($categoryData[eBayCategory::TYPE_EBAY_MAIN]['is_custom_template'] !== null) {
            return true;
        }

        return !$this->getHelper('Component_Ebay_Category_Ebay')->hasRequiredSpecifics(
            $categoryData[eBayCategory::TYPE_EBAY_MAIN]['value'],
            $listing->getMarketplaceId()
        );
    }

    //########################################

    protected function prepareUniqueTemplatesData($sessionData)
    {
        $unique = [];

        $categoryHelper = $this->getHelper('Component_Ebay_Category');
        $listing = $this->getListingFromRequest();

        foreach ($sessionData as $listingProductId => $templatesData) {
            if (!$this->isEbayPrimaryCategorySelected($templatesData, $listing)) {
                $this->deleteListingProducts([$listingProductId]);
                continue;
            }

            foreach ($templatesData as $categoryType => $categoryData) {
                if (!$categoryHelper->isEbayCategoryType($categoryType) &&
                    !$categoryHelper->isStoreCategoryType($categoryType)
                ) {
                    continue;
                }

                list($mainHash, $hash) = $this->getCategoryHashes($categoryData);

                if (!isset($unique[$hash][$categoryType])) {
                    $unique[$hash][$categoryType] = $categoryData;
                    $unique[$hash][$categoryType]['listing_products_ids'] = $templatesData['listing_products_ids'];
                } else {
                    // @codingStandardsIgnoreLine
                    $unique[$hash][$categoryType]['listing_products_ids'] = array_merge(
                        $unique[$hash][$categoryType]['listing_products_ids'],
                        $templatesData['listing_products_ids']
                    );
                }
            }
        }

        return $unique;
    }

    //########################################

    protected function convertCategoriesIdstoProductIds($sessionData)
    {
        if ($this->getSessionValue('mode') !== CategoryTemplateBlock::MODE_CATEGORY) {
            return $sessionData;
        }

        foreach ($sessionData as $categoryId => $data) {
            $listingProductsIds = isset($data['listing_products_ids']) ? $data['listing_products_ids'] : [];
            unset($sessionData[$categoryId]);

            foreach ($listingProductsIds as $listingProductId) {
                $sessionData[$listingProductId] = $data;
            }
        }

        foreach ($this->getEbayListingFromRequest()->getAddedListingProductsIds() as $listingProductId) {
            if (!array_key_exists($listingProductId, $sessionData)) {
                $sessionData[$listingProductId] = [];
            }
        }

        return $sessionData;
    }

    //########################################

    protected function initSessionDataProducts(array $addingListingProductIds)
    {
        $listingProducts = $this->ebayFactory->getObject('Listing_Product')->getCollection();
        $listingProducts->addFieldToFilter('id', ['in' => $addingListingProductIds]);

        $sessionData = $this->getSessionValue($this->getSessionDataKey());
        !$sessionData && $sessionData = [];

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\Converter $converter */
        $converter = $this->modelFactory->getObject('Ebay_Template_Category_Chooser_Converter');
        $converter->setAccountId($this->listing->getAccountId());
        $converter->setMarketplaceId($this->listing->getMarketplaceId());

        foreach ($addingListingProductIds as $id) {
            if (!empty($sessionData[$id])) {
                continue;
            }

            $sessionData[$id] = [];

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $listingProducts->getItemByColumnValue('id', $id);
            if ($listingProduct === null) {
                continue;
            }

            $onlineDataByType = [
                'category_main_id'            => eBayCategory::TYPE_EBAY_MAIN,
                'category_secondary_id'       => eBayCategory::TYPE_EBAY_SECONDARY,
                'store_category_main_id'      => eBayCategory::TYPE_STORE_MAIN,
                'store_category_secondary_id' => eBayCategory::TYPE_STORE_SECONDARY,
            ];

            $onlineData = $listingProduct->getChildObject()->getOnlineCategoriesData();
            foreach ($onlineDataByType as $onlineKey => $categoryType) {
                if (!empty($onlineData[$onlineKey])) {
                    $categoryPath = $this->getHelper('Component_Ebay_Category')->isEbayCategoryType($categoryType)
                        ? $this->getHelper('Component_Ebay_Category_Ebay')->getPath(
                            $onlineData[$onlineKey],
                            $listingProduct->getMarketplace()->getId()
                        )
                        : $this->getHelper('Component_Ebay_Category_Store')->getPath(
                            $onlineData[$onlineKey],
                            $listingProduct->getAccount()->getId()
                        );

                    $sessionData[$id][$categoryType] = [
                        'mode'               => TemplateCategory::CATEGORY_MODE_EBAY,
                        'value'              => $onlineData[$onlineKey],
                        'path'               => $categoryPath,
                        'template_id'        => null,
                        'is_custom_template' => null,
                        'specific'           => null
                    ];

                    if ($categoryType === eBayCategory::TYPE_EBAY_MAIN) {
                        $template = $this->activeRecordFactory->getObject('Ebay_Template_Category');
                        $template->loadByCategoryValue(
                            $sessionData[$id][$categoryType]['value'],
                            $sessionData[$id][$categoryType]['mode'],
                            $this->listing->getMarketplaceId(),
                            0
                        );

                        if ($template->getId()) {
                            $converter->setCategoryDataFromTemplate($template->getData(), eBayCategory::TYPE_EBAY_MAIN);
                            $sessionData[$id][$categoryType] = $converter->getCategoryDataForChooser(
                                eBayCategory::TYPE_EBAY_MAIN
                            );
                        }
                    }
                }
            }

            $sessionData[$id]['listing_products_ids'] = [$id];
        }

        foreach (array_diff(array_keys($sessionData), $addingListingProductIds) as $id) {
            unset($sessionData[$id]);
        }

        $this->setSessionValue($this->getSessionDataKey(), $sessionData);
    }

    protected function initSessionDataCategories(array $categoriesIds)
    {
        $sessionData = $this->getSessionValue($this->getSessionDataKey());
        !$sessionData && $sessionData = [];

        foreach ($categoriesIds as $id) {
            if (!empty($sessionData[$id])) {
                continue;
            }

            $sessionData[$id] = [];
        }

        foreach (array_diff(array_keys($sessionData), $categoriesIds) as $id) {
            unset($sessionData[$id]);
        }

        $listing = $this->getListingFromRequest();
        $ebayListing = $listing->getChildObject();
        $previousCategoriesData = [];

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\Converter $converter */
        $converter = $this->modelFactory->getObject('Ebay_Template_Category_Chooser_Converter');
        $converter->setAccountId($listing->getAccountId());
        $converter->setMarketplaceId($listing->getMarketplaceId());

        $tempData = $ebayListing->getLastPrimaryCategory(['ebay_primary_category','mode_category']);
        foreach ($tempData as $categoryId => $data) {
            !isset($previousCategoriesData[$categoryId]) && $previousCategoriesData[$categoryId] = [];
            if (!empty($data['mode']) && !empty($data['value']) && !empty($data['path'])) {
                $template = $this->activeRecordFactory->getObject('Ebay_Template_Category');
                $template->loadByCategoryValue(
                    $data['value'],
                    $data['mode'],
                    $listing->getMarketplaceId(),
                    0
                );

                if ($template->getId()) {
                    $converter->setCategoryDataFromTemplate($template->getData(), eBayCategory::TYPE_EBAY_MAIN);
                    $previousCategoriesData[$categoryId][eBayCategory::TYPE_EBAY_MAIN] =
                        $converter->getCategoryDataForChooser(eBayCategory::TYPE_EBAY_MAIN);
                } else {
                    $previousCategoriesData[$categoryId][eBayCategory::TYPE_EBAY_MAIN] = [
                        'mode'  => $data['mode'],
                        'value' => $data['value'],
                        'path'  => $data['path']
                    ];
                }
            }
        }

        $tempData = $ebayListing->getLastPrimaryCategory(['ebay_store_primary_category','mode_category']);
        foreach ($tempData as $categoryId => $data) {
            !isset($previousCategoriesData[$categoryId]) && $previousCategoriesData[$categoryId] = [];
            if (!empty($data['mode']) && !empty($data['value']) && !empty($data['path'])) {
                $template = $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory');
                $template->loadByCategoryValue(
                    $data['value'],
                    $data['mode'],
                    $listing->getAccountId()
                );

                if ($template->getId()) {
                    $converter->setCategoryDataFromTemplate($template->getData(), eBayCategory::TYPE_STORE_MAIN);
                    $previousCategoriesData[$categoryId][eBayCategory::TYPE_STORE_MAIN] =
                        $converter->getCategoryDataForChooser(eBayCategory::TYPE_STORE_MAIN);
                } else {
                    $previousCategoriesData[$categoryId][eBayCategory::TYPE_STORE_MAIN] = [
                        'mode'  => $data['mode'],
                        'value' => $data['value'],
                        'path'  => $data['path']
                    ];
                }
            }
        }

        foreach ($sessionData as $magentoCategoryId => &$data) {
            if (!isset($previousCategoriesData[$magentoCategoryId])) {
                continue;
            }

            $data['listing_products_ids'] = $this->getSelectedListingProductsIdsByCategoriesIds(
                [$magentoCategoryId]
            );

            // @codingStandardsIgnoreLine
            $data = array_replace_recursive($data, $previousCategoriesData[$magentoCategoryId]);
        }

        $this->setSessionValue($this->getSessionDataKey(), $sessionData);
    }

    //########################################

    protected function setSessionValue($key, $value)
    {
        $listing = $this->getListingFromRequest();
        $sessionData = $this->getSessionValue();

        if ($key === null) {
            $sessionData = $value;
        } else {
            $sessionData[$key] = $value;
        }

        $this->getHelper('Data\Session')->setValue($this->sessionKey . $listing->getId(), $sessionData);

        return $this;
    }

    protected function getSessionValue($key = null)
    {
        $listing = $this->getListingFromRequest();
        $sessionData = $this->getHelper('Data\Session')->getValue($this->sessionKey . $listing->getId());

        if ($sessionData === null) {
            $sessionData = [];
        }

        if ($key === null) {
            return $sessionData;
        }

        return isset($sessionData[$key]) ? $sessionData[$key] : null;
    }

    protected function clearSession()
    {
        $listing = $this->getListingFromRequest();
        $this->getHelper('Data\Session')->getValue($this->sessionKey . $listing->getId(), true);
    }

    protected function getSessionDataKey()
    {
        $key = '';

        switch (strtolower($this->getSessionValue('mode'))) {
            case CategoryTemplateBlock::MODE_SAME:
                $key = 'mode_same';
                break;
            case CategoryTemplateBlock::MODE_CATEGORY:
                $key = 'mode_category';
                break;
            case CategoryTemplateBlock::MODE_PRODUCT:
            case CategoryTemplateBlock::MODE_MANUALLY:
                $key = 'mode_product';
                break;
        }

        return $key;
    }

    //########################################

    protected function endListingCreation()
    {
        $ebayListing = $this->getEbayListingFromRequest();

        $this->getHelper('Data\Session')->setValue('added_products_ids', $ebayListing->getAddedListingProductsIds());
        $sessionData = $this->getSessionValue($this->getSessionDataKey());

        if ($this->getSessionValue('mode') == CategoryTemplateBlock::MODE_SAME) {
            if (isset($sessionData['category'][eBayCategory::TYPE_EBAY_MAIN])) {
                unset($sessionData['category'][eBayCategory::TYPE_EBAY_MAIN]['specific']);
                $ebayListing->updateLastPrimaryCategory(
                    ['ebay_primary_category', 'mode_same'],
                    $sessionData['category'][eBayCategory::TYPE_EBAY_MAIN]
                );
            }

            if (isset($sessionData['category'][eBayCategory::TYPE_STORE_MAIN])) {
                $ebayListing->updateLastPrimaryCategory(
                    ['ebay_store_primary_category', 'mode_same'],
                    $sessionData['category'][eBayCategory::TYPE_STORE_MAIN]
                );
            }
        } elseif ($this->getSessionValue('mode') == CategoryTemplateBlock::MODE_CATEGORY) {
            foreach ($sessionData as $magentoCategoryId => $data) {
                if (isset($data[eBayCategory::TYPE_EBAY_MAIN])) {
                    unset($data[eBayCategory::TYPE_EBAY_MAIN]['specific']);
                    $ebayListing->updateLastPrimaryCategory(
                        ['ebay_primary_category', 'mode_category', $magentoCategoryId],
                        $data[eBayCategory::TYPE_EBAY_MAIN]
                    );
                }

                if (isset($data[eBayCategory::TYPE_STORE_MAIN])) {
                    $ebayListing->updateLastPrimaryCategory(
                        ['ebay_store_primary_category', 'mode_category', $magentoCategoryId],
                        $data[eBayCategory::TYPE_STORE_MAIN]
                    );
                }
            }
        }

        //-- Remove successfully moved Unmanaged items
        $additionalData = $ebayListing->getParentObject()->getSettings('additional_data');
        if (isset($additionalData['source']) && $additionalData['source'] == SourceModeBlock::MODE_OTHER) {
            $this->deleteListingOthers();
        }

        //--
        $this->clearSession();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing
     * @throws \Exception
     */
    protected function getListingFromRequest()
    {
        if (!$listingId = $this->getRequest()->getParam('id')) {
            throw new \Ess\M2ePro\Model\Exception('Listing is not defined');
        }

        return $this->ebayFactory->getObjectLoaded('Listing', $this->getRequest()->getParam('id'));
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing
     * @throws \Exception
     */
    protected function getEbayListingFromRequest()
    {
        return $this->getListingFromRequest()->getChildObject();
    }

    //########################################

    protected function deleteListingProducts($listingProductsIds)
    {
        $listingProductsIds = array_map('intval', $listingProductsIds);

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->ebayFactory->getObject('Listing_Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $listingProductsIds]);

        foreach ($collection->getItems() as $listingProduct) {
            /**@var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct->canBeForceDeleted(true);
            $listingProduct->delete();
        }

        $listing = $this->getListingFromRequest();

        $listingProductAddIds = $listing->getChildObject()->getAddedListingProductsIds();
        if (empty($listingProductAddIds)) {
            return;
        }

        $listingProductAddIds = array_map('intval', $listingProductAddIds);
        $listingProductAddIds = array_diff($listingProductAddIds, $listingProductsIds);

        $listing->getChildObject()->setData(
            'product_add_ids',
            $this->getHelper('Data')->jsonEncode($listingProductAddIds)
        );
        $listing->save();
    }

    protected function deleteListingOthers()
    {
        $listingProductsIds = $this->getEbayListingFromRequest()->getAddedListingProductsIds();
        if (empty($listingProductsIds)) {
            return;
        }

        $otherProductsIds = [];

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->ebayFactory->getObject('Listing_Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $listingProductsIds]);
        foreach ($collection->getItems() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $otherProductsIds[] = (int)$listingProduct->getSetting(
                'additional_data',
                $listingProduct::MOVING_LISTING_OTHER_SOURCE_KEY
            );
        }

        if (empty($otherProductsIds)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection $collection */
        $collection = $this->ebayFactory->getObject('Listing_Other')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $otherProductsIds]);
        foreach ($collection->getItems() as $listingOther) {
            /** @var \Ess\M2ePro\Model\Listing\Other $listingOther */
            $listingOther->moveToListingSucceed();
        }
    }

    //########################################

    protected function getCategoryHashes(array $categoryData)
    {
        // @codingStandardsIgnoreStart
        $mainHash = $categoryData['mode'] .'-'. $categoryData['value'];
        $specificsHash = !empty($categoryData['specific'])
            ? sha1($this->getHelper('Data')->jsonEncode($categoryData['specific']))
            : '';
        // @codingStandardsIgnoreEnd

        return [
            $mainHash,
            $mainHash .'-'. $specificsHash
        ];
    }

    //########################################
}
