<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings
 */
abstract class Settings extends Listing
{
    protected $sessionKey = 'ebay_listing_product_category_settings';

    //########################################

    protected function addCategoriesPath(&$data, \Ess\M2ePro\Model\Listing $listing)
    {
        $marketplaceId = $listing->getData('marketplace_id');
        $accountId = $listing->getAccountId();

        if (isset($data['category_main_mode'])) {
            if ($data['category_main_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
                $data['category_main_path'] = $this->getHelper('Component_Ebay_Category_Ebay')->getPath(
                    $data['category_main_id'],
                    $marketplaceId
                );
            } else {
                $data['category_main_path'] = null;
            }
        }

        if (isset($data['category_secondary_mode'])) {
            if ($data['category_secondary_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
                $data['category_secondary_path'] = $this->getHelper('Component_Ebay_Category_Ebay')->getPath(
                    $data['category_secondary_id'],
                    $marketplaceId
                );
            } else {
                $data['category_secondary_path'] = null;
            }
        }

        if (isset($data['store_category_main_mode'])) {
            if ($data['store_category_main_mode'] ==
                \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
                $data['store_category_main_path'] = $this->getHelper('Component_Ebay_Category_Store')
                    ->getPath(
                        $data['store_category_main_id'],
                        $accountId
                    );
            } else {
                $data['store_category_main_path'] = null;
            }
        }

        if (isset($data['store_category_secondary_mode'])) {
            if ($data['store_category_secondary_mode'] ==
                \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
                $data['store_category_secondary_path'] =$this->getHelper('Component_Ebay_Category_Store')
                    ->getPath(
                        $data['store_category_secondary_id'],
                        $accountId
                    );
            } else {
                $data['store_category_secondary_path'] = null;
            }
        }
    }

    //########################################

    protected function getSelectedListingProductsIdsByCategoriesIds($categoriesIds)
    {
        $productsIds = $this->getHelper('Magento\Category')->getProductsFromCategories($categoriesIds);

        $listingProductIds = $this->ebayFactory->getObject('Listing\Product')->getCollection()
            ->addFieldToFilter('product_id', ['in' => $productsIds])->getAllIds();

        return array_values(array_intersect(
            $this->getListing()->getChildObject()->getAddedListingProductsIds(),
            $listingProductIds
        ));
    }

    protected function assignTemplatesToProducts($categoryTemplateId, $otherCategoryTemplateId, $productsIds)
    {
        if (empty($productsIds)) {
            return;
        }

        $connection = $this->resourceConnection->getConnection();

        $connection->update(
            $this->activeRecordFactory->getObject('Ebay_Listing_Product')->getResource()->getMainTable(),
            [
                'template_category_id'       => $categoryTemplateId,
                'template_other_category_id' => $otherCategoryTemplateId
            ],
            'listing_product_id IN ('.implode(',', $productsIds).')'
        );
    }

    //########################################

    protected function getCurrentPrimaryCategory()
    {
        $currentPrimaryCategory = $this->getSessionValue('current_primary_category');

        if ($currentPrimaryCategory !== null) {
            return $currentPrimaryCategory;
        }

        $useLastSpecifics = $this->useLastSpecifics();

        $specifics = $this->getSessionValue('specifics');

        if (!$useLastSpecifics) {
            return key($specifics);
        }

        foreach ($specifics as $id => $specificsData) {
            if (!$specificsData['template_exists']) {
                $currentPrimaryCategory = $id;
                break;
            }
        }

        return $currentPrimaryCategory;
    }

    //########################################

    protected function clearSpecificsSession()
    {
        $this->setSessionValue('specifics', null);
        $this->setSessionValue('current_primary_category', null);
    }

    //########################################

    protected function getSpecificBlock()
    {
        $templatesData = $this->getTemplatesData();
        $currentPrimaryCategory = $this->getCurrentPrimaryCategory();

        $listing = $this->getListing();

        /** @var $specific \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Specific */
        $specific = $this->createBlock('Ebay_Listing_Product_Category_Settings_Specific');
        $specific->setMarketplaceId($listing->getMarketplaceId());

        $currentTemplateData = $templatesData[$currentPrimaryCategory];

        $categoryMode = $currentTemplateData['category_main_mode'];
        $specific->setCategoryMode($categoryMode);

        if ($categoryMode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
            $specific->setCategoryValue($currentTemplateData['category_main_id']);
        } elseif ($categoryMode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            $specific->setCategoryValue($currentTemplateData['category_main_attribute']);
        }

        $specificsData = $this->getSessionValue('specifics');

        $specific->setInternalData($specificsData[$currentPrimaryCategory]);
        $specific->setSelectedSpecifics($specificsData[$currentPrimaryCategory]['specifics']);

        return $specific;
    }

    //########################################

    protected function useLastSpecifics()
    {
        return (bool)$this->getHelper('Module')->getConfig()->getGroupValue(
            '/view/ebay/template/category/',
            'use_last_specifics'
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
        if ($this->getSessionValue('mode') == 'category') {
            foreach ($sessionData as $categoryId => $data) {
                $listingProductsIds = $data['listing_products_ids'];
                unset($data['listing_products_ids']);

                foreach ($listingProductsIds as $listingProductId) {
                    $sessionData[$listingProductId] = $data;
                }

                unset($sessionData[$categoryId]);
            }
        }

        $specificsData = $this->getSessionValue('specifics');

        foreach ($this->getUniqueTemplatesData($sessionData) as $templateData) {
            $listingProductsIds = $templateData['listing_products_ids'];
            $listingProductsIds = array_unique($listingProductsIds);

            if (empty($listingProductsIds)) {
                continue;
            }

            // save category template & specifics
            // ---------------------------------------
            $builderData = $templateData;
            $builderData['account_id'] = $this->getListing()->getAccountId();
            $builderData['marketplace_id'] = $this->getListing()->getMarketplaceId();

            $categoryTemplateId = null;

            if ($builderData['identifier'] !== null) {
                $builderData['specifics'] = $specificsData[$templateData['identifier']]['specifics'];

                $categoryTemplateId = $this->modelFactory->getObject('Ebay_Template_Category_Builder')->build(
                    $builderData
                )->getId();
            }

            $otherCategoryTemplate = $this->modelFactory->getObject('Ebay_Template_OtherCategory_Builder')->build(
                $builderData
            );
            // ---------------------------------------

            $this->assignTemplatesToProducts(
                $categoryTemplateId,
                $otherCategoryTemplate->getId(),
                $listingProductsIds
            );
        }

        $this->endWizard();
        $this->endListingCreation();
    }

    //########################################

    protected function getUniqueTemplatesData($templatesData)
    {
        $unique = [];

        foreach ($templatesData as $listingProductId => $data) {
            $hash = sha1($this->getHelper('Data')->jsonEncode($data));

            $data['identifier'] = null;

            if ($data['category_main_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
                $data['identifier'] = $data['category_main_id'];
            }
            if ($data['category_main_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
                $data['identifier'] = $data['category_main_attribute'];
            }

            !isset($unique[$hash]) && $unique[$hash] = [];

            $unique[$hash] = array_merge($unique[$hash], $data);
            $unique[$hash]['listing_products_ids'][] = $listingProductId;
        }

        return array_values($unique);
    }

    //########################################

    protected function getTemplatesData()
    {
        $listing = $this->getListing();

        $templatesData = [];
        foreach ($this->getSessionValue($this->getSessionDataKey()) as $templateData) {
            if ($templateData['category_main_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
                $id = $templateData['category_main_id'];
            } else {
                $id = $templateData['category_main_attribute'];
            }

            if (empty($id)) {
                continue;
            }

            $templateData['marketplace_id'] = $listing->getMarketplaceId();
            $templatesData[$id] = $templateData;
        }

        ksort($templatesData);
        $templatesData = array_reverse($templatesData, true);

        return $templatesData;
    }

    //########################################

    protected function initSessionData($ids, $override = false)
    {
        $key = $this->getSessionDataKey();

        $sessionData = $this->getSessionValue($key);
        !$sessionData && $sessionData = [];

        foreach ($ids as $id) {
            if (!empty($sessionData[$id]) && !$override) {
                continue;
            }

            $sessionData[$id] = [
                'category_main_id' => null,
                'category_main_path' => null,
                'category_main_mode' => \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE,
                'category_main_attribute' => null,

                'category_secondary_id' => null,
                'category_secondary_path' => null,
                'category_secondary_mode' => \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE,
                'category_secondary_attribute' => null,

                'store_category_main_id' => null,
                'store_category_main_path' => null,
                'store_category_main_mode' => \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE,
                'store_category_main_attribute' => null,

                'store_category_secondary_id' => null,
                'store_category_secondary_path' => null,
                'store_category_secondary_mode' => \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE,
                'store_category_secondary_attribute' => null,
            ];
        }

        if (!$override) {
            foreach (array_diff(array_keys($sessionData), $ids) as $id) {
                unset($sessionData[$id]);
            }
        }

        $this->setSessionValue($key, $sessionData);
    }

    //########################################

    protected function endListingCreation()
    {
        $ebayListing = $this->getListing()->getChildObject();

        $this->getHelper('Data\Session')->setValue(
            'added_products_ids',
            $ebayListing->getAddedListingProductsIds()
        );

        $sessionData = $this->getSessionValue($this->getSessionDataKey());

        if ($this->getSessionValue('mode') == 'same') {
            $ebayListing->updateLastPrimaryCategory(
                ['ebay_primary_category', 'mode_same'],
                ['category_main_id' => $sessionData['category']['category_main_id'],
                    'category_main_mode' => $sessionData['category']['category_main_mode'],
                    'category_main_attribute' => $sessionData['category']['category_main_attribute']]
            );

            $ebayListing->updateLastPrimaryCategory(
                ['ebay_store_primary_category', 'mode_same'],
                ['store_category_main_id' => $sessionData['category']['store_category_main_id'],
                    'store_category_main_mode' => $sessionData['category']['store_category_main_mode'],
                    'store_category_main_attribute' => $sessionData['category']['store_category_main_attribute']]
            );
        } elseif ($this->getSessionValue('mode') == 'category') {
            foreach ($sessionData as $magentoCategoryId => $data) {
                $ebayListing->updateLastPrimaryCategory(
                    ['ebay_primary_category', 'mode_category', $magentoCategoryId],
                    [
                        'category_main_id' => $data['category_main_id'],
                        'category_main_mode' => $data['category_main_mode'],
                        'category_main_attribute' => $data['category_main_attribute']
                    ]
                );

                $ebayListing->updateLastPrimaryCategory(
                    ['ebay_store_primary_category', 'mode_category', $magentoCategoryId],
                    [
                        'store_category_main_id' => $data['store_category_main_id'],
                        'store_category_main_mode' => $data['store_category_main_mode'],
                        'store_category_main_attribute' => $data['store_category_main_attribute']
                    ]
                );
            }
        }

        $ebayListing->setData(
            'product_add_ids',
            $this->getHelper('Data')->jsonEncode([])
        )->save();

        $this->clearSession();
    }

    //########################################

    protected function setSessionValue($key, $value)
    {
        $sessionData = $this->getSessionValue();
        $sessionData[$key] = $value;

        $this->getHelper('Data\Session')->setValue($this->sessionKey, $sessionData);

        return $this;
    }

    protected function getSessionValue($key = null)
    {
        $sessionData = $this->getHelper('Data\Session')->getValue($this->sessionKey);

        if ($sessionData === null) {
            $sessionData = [];
        }

        if ($key === null) {
            return $sessionData;
        }

        return isset($sessionData[$key]) ? $sessionData[$key] : null;
    }

    protected function getSessionDataKey()
    {
        $key = '';

        switch (strtolower($this->getSessionValue('mode'))) {
            case 'same':
                $key = 'mode_same';
                break;
            case 'category':
                $key = 'mode_category';
                break;
            case 'product':
            case 'manually':
                $key = 'mode_product';
                break;
        }

        return $key;
    }

    protected function clearSession()
    {
        $this->getHelper('Data\Session')->getValue($this->sessionKey, true);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    protected function getListing()
    {
        return $this->ebayFactory->getObjectLoaded('Listing', $this->getRequest()->getParam('id'));
    }

    //########################################
}
