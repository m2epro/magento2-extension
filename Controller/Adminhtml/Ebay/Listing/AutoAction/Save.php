<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction;

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

        // ---------------------------------------
        $listingId = $this->getRequest()->getParam('id');
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);
        // ---------------------------------------

        $data = $this->getHelper('Data')->jsonDecode($post['auto_action_data']);

        if (isset($data['template_category_data'])) {
            $this->getHelper('Component\Ebay\Category')->fillCategoriesPaths(
                $data['template_category_data'], $listing
            );
        }

        $listingData = array(
            'auto_mode' => \Ess\M2ePro\Model\Listing::AUTO_MODE_NONE,
            'auto_global_adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'auto_global_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_global_adding_template_category_id' => NULL,
            'auto_global_adding_template_other_category_id' => NULL,
            'auto_website_adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'auto_website_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_website_adding_template_category_id' => NULL,
            'auto_website_adding_template_other_category_id' => NULL,
            'auto_website_deleting_mode' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE
        );

        $groupData = array(
            'id' => null,
            'category' => null,
            'title' => null,
            'auto_mode' => \Ess\M2ePro\Model\Listing::AUTO_MODE_NONE,
            'adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'deleting_mode' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE,
            'categories' => array()
        );

        $addingModeAddAndAssignCategory = \Ess\M2ePro\Model\Ebay\Listing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY;

        // mode global
        // ---------------------------------------
        if ($data['auto_mode'] == \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL) {
            $listingData['auto_mode'] = \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL;
            $listingData['auto_global_adding_mode'] = $data['auto_global_adding_mode'];

            if ($data['auto_global_adding_mode'] == $addingModeAddAndAssignCategory) {
                $builderData = $data['template_category_data'];
                $builderData['marketplace_id'] = $listing->getMarketplaceId();
                $builderData['account_id'] = $listing->getAccountId();
                $builderData['specifics'] = $data['template_category_specifics_data']['specifics'];

                $categoryTemplate = $this->modelFactory->getObject('Ebay\Template\Category\Builder')->build(
                    $builderData
                );
                $otherCategoryTemplate = $this->modelFactory->getObject('Ebay\Template\OtherCategory\Builder')->build(
                    $builderData
                );

                $listingData['auto_global_adding_template_category_id'] = $categoryTemplate->getId();
                $listingData['auto_global_adding_template_other_category_id'] = $otherCategoryTemplate->getId();
            }
            if ($data['auto_global_adding_mode'] != \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE) {
                $listingData['auto_global_adding_add_not_visible'] = $data['auto_global_adding_add_not_visible'];
            }
        }
        // ---------------------------------------

        // mode website
        // ---------------------------------------
        if ($data['auto_mode'] == \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE) {
            $listingData['auto_mode'] = \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE;
            $listingData['auto_website_adding_mode'] = $data['auto_website_adding_mode'];
            $listingData['auto_website_deleting_mode'] = $data['auto_website_deleting_mode'];

            if ($data['auto_website_adding_mode'] == $addingModeAddAndAssignCategory) {
                $builderData = $data['template_category_data'];
                $builderData['marketplace_id'] = $listing->getMarketplaceId();
                $builderData['account_id'] = $listing->getAccountId();
                $builderData['specifics'] = $data['template_category_specifics_data']['specifics'];

                $categoryTemplate = $this->modelFactory->getObject('Ebay\Template\Category\Builder')->build(
                    $builderData
                );
                $otherCategoryTemplate = $this->modelFactory->getObject('Ebay\Template\OtherCategory\Builder')->build(
                    $builderData
                );

                $listingData['auto_website_adding_template_category_id'] = $categoryTemplate->getId();
                $listingData['auto_website_adding_template_other_category_id'] = $otherCategoryTemplate->getId();
            }
            if ($data['auto_website_adding_mode'] != \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE) {
                $listingData['auto_website_adding_add_not_visible'] = $data['auto_website_adding_add_not_visible'];
            }
        }
        // ---------------------------------------

        // mode category
        // ---------------------------------------
        if ($data['auto_mode'] == \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY) {
            $listingData['auto_mode'] = \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY;

            $group = $this->ebayFactory->getObject('Listing\Auto\Category\Group');

            if ((int)$data['id'] > 0) {
                $group->load((int)$data['id']);
            } else {
                unset($data['id']);
            }

            $group->addData(array_merge($groupData, $data));
            $group->setData('listing_id', $listingId);
            if (!$group->getId()) {
                $group->save();
            }

            if ($data['adding_mode'] == \Ess\M2ePro\Model\Ebay\Listing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY) {
                $builderData = $data['template_category_data'];
                $builderData['marketplace_id'] = $listing->getMarketplaceId();
                $builderData['account_id'] = $listing->getAccountId();
                $builderData['specifics'] = $data['template_category_specifics_data']['specifics'];

                $categoryTemplate = $this->modelFactory->getObject('Ebay\Template\Category\Builder')->build(
                    $builderData
                );
                $otherCategoryTemplate = $this->modelFactory->getObject('Ebay\Template\OtherCategory\Builder')->build(
                    $builderData
                );

                $group->getChildObject()->setData('adding_template_category_id', $categoryTemplate->getId());
                $group->getChildObject()->setData('adding_template_other_category_id', $otherCategoryTemplate->getId());
            } else {
                $group->getChildObject()->setData('adding_template_category_id', NULL);
                $group->getChildObject()->setData('adding_template_other_category_id', NULL);
            }

            $group->save();
            $group->clearCategories();

            foreach ($data['categories'] as $categoryId) {
                $category = $this->activeRecordFactory->getObject('Listing\Auto\Category');
                $category->setData('group_id', $group->getId());
                $category->setData('category_id', $categoryId);
                $category->save();
            }
        }
        // ---------------------------------------

        $listing->addData($listingData);
        $listing->getChildObject()->addData($listingData);
        $listing->save();

        $this->setJsonContent(['success' => true]);
        return $this->getResult();
    }
}