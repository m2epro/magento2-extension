<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction;

use \Ess\M2ePro\Model\Listing as Listing;
use \Ess\M2ePro\Model\Ebay\Listing as eBayListing;
use \Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction\Save
 */
class Save extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction
{
    public function execute()
    {
        if (!$post = $this->getRequest()->getPost()) {
            $this->setJsonContent(['success' => false]);
            return $this->getResult();
        }

        if (!isset($post['auto_action_data'])) {
            $this->setJsonContent(['success' => false]);
            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->ebayFactory->getCachedObjectLoaded(
            'Listing',
            $this->getRequest()->getParam('listing_id')
        );

        $data = $this->getHelper('Data')->jsonDecode($post['auto_action_data']);

        $listingData = [
            'auto_mode'                                      => Listing::AUTO_MODE_NONE,
            'auto_global_adding_mode'                        => Listing::ADDING_MODE_NONE,
            'auto_global_adding_add_not_visible'             => Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_global_adding_template_category_id'                 => null,
            'auto_global_adding_template_category_secondary_id'       => null,
            'auto_global_adding_template_store_category_id'           => null,
            'auto_global_adding_template_store_category_secondary_id' => null,

            'auto_website_adding_mode'            => Listing::ADDING_MODE_NONE,
            'auto_website_adding_add_not_visible' => Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_website_adding_template_category_id'                 => null,
            'auto_website_adding_template_category_secondary_id'       => null,
            'auto_website_adding_template_store_category_id'           => null,
            'auto_website_adding_template_store_category_secondary_id' => null,

            'auto_website_deleting_mode' => Listing::DELETING_MODE_NONE
        ];

        $groupData = [
            'id'                     => null,
            'category'               => null,
            'title'                  => null,
            'auto_mode'              => Listing::AUTO_MODE_NONE,
            'adding_mode'            => Listing::ADDING_MODE_NONE,
            'adding_add_not_visible' => Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'deleting_mode'          => Listing::DELETING_MODE_NONE,
            'categories'             => []
        ];

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\Converter $converter */
        $converter = $this->modelFactory->getObject('Ebay_Template_Category_Chooser_Converter');
        $converter->setAccountId($listing->getAccountId());
        $converter->setMarketplaceId($listing->getMarketplaceId());
        foreach ($data['template_category_data'] as $type => $templateData) {
            $converter->setCategoryDataFromChooser($templateData, $type);
        }

        $ebayTpl = $this->modelFactory->getObject('Ebay_Template_Category_Builder')->build(
            $this->activeRecordFactory->getObject('Ebay_Template_Category'),
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_EBAY_MAIN)
        );
        $ebaySecondaryTpl = $this->modelFactory->getObject('Ebay_Template_Category_Builder')->build(
            $this->activeRecordFactory->getObject('Ebay_Template_Category'),
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_EBAY_SECONDARY)
        );
        $storeTpl = $this->modelFactory->getObject('Ebay_Template_StoreCategory_Builder')->build(
            $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory'),
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_STORE_MAIN)
        );
        $storeSecondaryTpl = $this->modelFactory->getObject('Ebay_Template_StoreCategory_Builder')->build(
            $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory'),
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_STORE_SECONDARY)
        );

        // mode global
        // ---------------------------------------
        if ($data['auto_mode'] == Listing::AUTO_MODE_GLOBAL) {
            $listingData['auto_mode']               = Listing::AUTO_MODE_GLOBAL;
            $listingData['auto_global_adding_mode'] = $data['auto_global_adding_mode'];

            if ($data['auto_global_adding_mode'] == eBayListing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY) {
                $listingData['auto_global_adding_template_category_id']                 = $ebayTpl->getId();
                $listingData['auto_global_adding_template_category_secondary_id']       = $ebaySecondaryTpl->getId();
                $listingData['auto_global_adding_template_store_category_id']           = $storeTpl->getId();
                $listingData['auto_global_adding_template_store_category_secondary_id'] = $storeSecondaryTpl->getId();
            }

            if ($data['auto_global_adding_mode'] != Listing::ADDING_MODE_NONE) {
                $listingData['auto_global_adding_add_not_visible'] = $data['auto_global_adding_add_not_visible'];
            }
        }

        // mode website
        // ---------------------------------------
        if ($data['auto_mode'] == Listing::AUTO_MODE_WEBSITE) {
            $listingData['auto_mode']                  = Listing::AUTO_MODE_WEBSITE;
            $listingData['auto_website_adding_mode']   = $data['auto_website_adding_mode'];
            $listingData['auto_website_deleting_mode'] = $data['auto_website_deleting_mode'];

            if ($data['auto_website_adding_mode'] == eBayListing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY) {
                $listingData['auto_website_adding_template_category_id']                 = $ebayTpl->getId();
                $listingData['auto_website_adding_template_category_secondary_id']       = $ebaySecondaryTpl->getId();
                $listingData['auto_website_adding_template_store_category_id']           = $storeTpl->getId();
                $listingData['auto_website_adding_template_store_category_secondary_id'] = $storeSecondaryTpl->getId();
            }

            if ($data['auto_website_adding_mode'] != Listing::ADDING_MODE_NONE) {
                $listingData['auto_website_adding_add_not_visible'] = $data['auto_website_adding_add_not_visible'];
            }
        }

        // mode category
        // ---------------------------------------
        if ($data['auto_mode'] == Listing::AUTO_MODE_CATEGORY) {
            $listingData['auto_mode'] = Listing::AUTO_MODE_CATEGORY;

            /** @var \Ess\M2ePro\Model\Listing\Auto\Category\Group $group */
            $group = $this->ebayFactory->getObject('Listing_Auto_Category_Group');

            if ((int)$data['id'] > 0) {
                $group->load((int)$data['id']);
            } else {
                unset($data['id']);
            }

            $group->addData(array_merge($groupData, $data));
            $group->setData('listing_id', $listing->getId());

            if ($data['adding_mode'] == eBayListing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY) {
                $group->setData('adding_template_category_id', $ebayTpl->getId());
                $group->setData('adding_template_category_secondary_id', $ebaySecondaryTpl->getId());
                $group->setData('adding_template_store_category_id', $storeTpl->getId());
                $group->setData('adding_template_store_category_secondary_id', $storeSecondaryTpl->getId());
            } else {
                $group->setData('adding_template_category_id', null);
                $group->setData('adding_template_category_secondary_id', null);
                $group->setData('adding_template_store_category_id', null);
                $group->setData('adding_template_store_category_secondary_id', null);
            }

            $group->save();
            $group->clearCategories();

            foreach ($data['categories'] as $categoryId) {
                $category = $this->activeRecordFactory->getObject('Listing_Auto_Category');
                $category->setData('group_id', $group->getId());
                $category->setData('category_id', $categoryId);
                $category->save();
            }
        }

        $listing->addData($listingData);
        $listing->getChildObject()->addData($listingData);
        $listing->save();

        $this->setJsonContent(['success' => true]);
        return $this->getResult();
    }

}
