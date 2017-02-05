<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
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
        $listing = $this->amazonFactory->getCachedObjectLoaded('Listing', $listingId);
        // ---------------------------------------

        $data = $this->getHelper('Data')->jsonDecode($post['auto_action_data']);

        $listingData = array(
            'auto_mode' => \Ess\M2ePro\Model\Listing::AUTO_MODE_NONE,
            'auto_global_adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'auto_global_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_website_adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'auto_website_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_website_deleting_mode' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE,
            'auto_global_adding_description_template_id' => NULL,
            'auto_website_adding_description_template_id' => NULL,
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

        // mode global
        // ---------------------------------------
        if ($data['auto_mode'] == \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL) {
            $listingData['auto_mode'] = \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL;
            $listingData['auto_global_adding_mode'] = $data['auto_global_adding_mode'];

            $listingData['auto_global_adding_description_template_id'] = $data['adding_description_template_id'];

            if ($listingData['auto_global_adding_mode'] != \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE) {
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
            $listingData['auto_website_adding_description_template_id'] = $data['adding_description_template_id'];

            if ($listingData['auto_website_adding_mode'] != \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE) {
                $listingData['auto_website_adding_add_not_visible'] = $data['auto_website_adding_add_not_visible'];
            }
        }
        // ---------------------------------------

        // mode category
        // ---------------------------------------
        if ($data['auto_mode'] == \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY) {
            $listingData['auto_mode'] = \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY;

            $group = $this->amazonFactory->getObject('Listing\Auto\Category\Group');

            if ((int)$data['id'] > 0) {
                $group->load((int)$data['id']);
            } else {
                unset($data['id']);
            }

            $group->addData(array_merge($groupData, $data));
            $group->setData('listing_id', $listing->getId());
            if (!$group->getId()) {
                $group->save();
            }

            if (!empty($data['adding_description_template_id'])) {
                $group->getChildObject()->setData(
                    'adding_description_template_id', $data['adding_description_template_id']
                );
            } else {
                $group->getChildObject()->setData('adding_description_template_id', NULL);
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