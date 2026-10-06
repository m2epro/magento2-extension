<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
{
    private \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter\Create $amazonAutoAdvancedFilterCreate;
    private \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter\Update $amazonAutoAdvancedFilterUpdate;
    private \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter\Repository $amazonAutoAdvancedFilterRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter\Create $amazonAutoAdvancedFilterCreate,
        \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter\Update $amazonAutoAdvancedFilterUpdate,
        \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter\Repository $amazonAutoAdvancedFilterRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->amazonAutoAdvancedFilterCreate = $amazonAutoAdvancedFilterCreate;
        $this->amazonAutoAdvancedFilterUpdate = $amazonAutoAdvancedFilterUpdate;
        $this->amazonAutoAdvancedFilterRepository = $amazonAutoAdvancedFilterRepository;
    }

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

        $listing = $this->amazonFactory->getCachedObjectLoaded(
            'Listing',
            $this->getRequest()->getParam('listing_id')
        );

        $data = \Ess\M2ePro\Helper\Json::decode($post['auto_action_data']);

        $listingData = [
            'auto_mode' => \Ess\M2ePro\Model\Listing::AUTO_MODE_NONE,
            'auto_global_adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'auto_global_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_global_adding_product_type_template_id'  => null,
            'auto_website_adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'auto_website_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_website_deleting_mode' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE,
            'auto_website_adding_product_type_template_id' => null,
        ];

        $groupData = [
            'id' => null,
            'category' => null,
            'title' => null,
            'auto_mode' => \Ess\M2ePro\Model\Listing::AUTO_MODE_NONE,
            'adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'deleting_mode' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE,
            'categories' => [],
        ];

        // mode global
        // ---------------------------------------
        if ($data['auto_mode'] == \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL) {
            $listingData['auto_mode'] = \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL;
            $listingData['auto_global_adding_mode'] = $data['auto_global_adding_mode'];
            $listingData['auto_global_adding_product_type_template_id'] = $data['adding_product_type_template_id'];

            if ($listingData['auto_global_adding_mode'] != \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE) {
                $listingData['auto_global_adding_add_not_visible'] = $data['auto_global_adding_add_not_visible'];
            }
        }

        // mode website
        // ---------------------------------------
        if ($data['auto_mode'] == \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE) {
            $listingData['auto_mode'] = \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE;
            $listingData['auto_website_adding_mode'] = $data['auto_website_adding_mode'];
            $listingData['auto_website_deleting_mode'] = $data['auto_website_deleting_mode'];
            $listingData['auto_website_adding_product_type_template_id'] = $data['adding_product_type_template_id'];

            if ($listingData['auto_website_adding_mode'] != \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE) {
                $listingData['auto_website_adding_add_not_visible'] = $data['auto_website_adding_add_not_visible'];
            }
        }

        // mode category
        // ---------------------------------------
        if ($data['auto_mode'] == \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY) {
            $listingData['auto_mode'] = \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY;

            $group = $this->amazonFactory->getObject('Listing_Auto_Category_Group');

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

            if (!empty($data['adding_product_type_template_id'])) {
                $group->getChildObject()->setData(
                    'adding_product_type_template_id',
                    $data['adding_product_type_template_id']
                );
            } else {
                $group->getChildObject()->setData('adding_product_type_template_id', null);
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

        // mode advanced filter
        // ----------------------------------------
        if ($data['auto_mode'] == \Ess\M2ePro\Model\Listing::AUTO_MODE_ADVANCED_FILTER) {
            $listingData['auto_mode'] = \Ess\M2ePro\Model\Listing::AUTO_MODE_ADVANCED_FILTER;
            $this->saveAdvancedFilter($data, (int)$listing->getId());
        }
        // ---------------------------------------

        $listing->addData($listingData);
        $listing->getChildObject()->addData($listingData);
        $listing->save();

        $this->setJsonContent(['success' => true]);

        return $this->getResult();
    }

    private function saveAdvancedFilter(array $requestData, int $listingId)
    {
        $model = $this->amazonAutoAdvancedFilterRepository->find((int)$requestData['id']);

        $addingProductTypeTemplateId = !empty($requestData['adding_product_type_template_id'])
            ? (int)$requestData['adding_product_type_template_id']
            : null;

        if ($model !== null) {
            $this->amazonAutoAdvancedFilterUpdate->execute(
                $model,
                (string)$requestData['title'],
                (int)$requestData['adding_mode'],
                (int)$requestData['adding_add_not_visible'],
                (int)$requestData['deleting_mode'],
                $this->prepareAdvancedFilterCondition($requestData['condition']),
                $addingProductTypeTemplateId
            );

            return;
        }

        $this->amazonAutoAdvancedFilterCreate->execute(
            $listingId,
            (string)$requestData['title'],
            (int)$requestData['adding_mode'],
            (int)$requestData['adding_add_not_visible'],
            (int)$requestData['deleting_mode'],
            $this->prepareAdvancedFilterCondition($requestData['condition']),
            $addingProductTypeTemplateId
        );
    }

    private function prepareAdvancedFilterCondition($data): ?string
    {
        $post = [];
        parse_str($data, $post);

        if (empty($post['rule'][\Ess\M2ePro\Model\Listing\Auto\Advanced\Filter::RULE_MODEL_PREFIX])) {
            return null;
        }

        $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
            ['prefix' => \Ess\M2ePro\Model\Listing\Auto\Advanced\Filter::RULE_MODEL_PREFIX]
        );

        return $ruleModel->getSerializedFromPost($post);
    }
}
