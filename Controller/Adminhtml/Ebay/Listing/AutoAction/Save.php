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
use \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\Converter;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\ConverterFactory */
    private $converterFactory;

    /** @var \Ess\M2ePro\Model\Ebay\Template\CategoryFactory */
    private $categoryFactory;

    /** @var \Ess\M2ePro\Model\Ebay\Template\Category\BuilderFactory */
    private $categoryBuilderFactory;

    /** @var \Ess\M2ePro\Model\Ebay\Template\StoreCategoryFactory */
    private $storeCategoryFactory;

    /** @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory\BuilderFactory */
    private $storeCategoryBuilderFactory;

    /** @var \Ess\M2ePro\Model\Ebay\Template\Category */
    private $tmpltCategory;

    /** @var \Ess\M2ePro\Model\Ebay\Template\Category */
    private $secTmpltCategory;

    /** @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory */
    private $storeTmpltCategory;

    /** @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory */
    private $secStoreTmpltCategory;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Auto\Category\GroupFactory */
    private $autoCategoryGroupFactory;

    /** @var \Ess\M2ePro\Model\Listing\Auto\CategoryFactory */
    private $autoCategoryFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\ConverterFactory $converterFactory,
        \Ess\M2ePro\Model\Ebay\Template\CategoryFactory $categoryFactory,
        \Ess\M2ePro\Model\Ebay\Template\StoreCategoryFactory $storeCategoryFactory,
        \Ess\M2ePro\Model\Ebay\Template\Category\BuilderFactory $categoryBuilderFactory,
        \Ess\M2ePro\Model\Ebay\Template\StoreCategory\BuilderFactory $storeCategoryBuilderFactory,
        \Ess\M2ePro\Model\Ebay\Listing\Auto\Category\GroupFactory $autoCategoryGroupFactory,
        \Ess\M2ePro\Model\Listing\Auto\CategoryFactory $autoCategoryFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->dataHelper = $dataHelper;
        $this->converterFactory = $converterFactory;
        $this->categoryFactory = $categoryFactory;
        $this->storeCategoryFactory = $storeCategoryFactory;
        $this->categoryBuilderFactory = $categoryBuilderFactory;
        $this->storeCategoryBuilderFactory = $storeCategoryBuilderFactory;
        $this->autoCategoryGroupFactory = $autoCategoryGroupFactory;
        $this->autoCategoryFactory = $autoCategoryFactory;
    }

    public function execute()
    {
        $requestData = $this->dataHelper->jsonDecode(
            $this->getRequest()->getPost('auto_action_data')
        );

        if ($requestData === null) {
            $this->setJsonContent(['success' => false]);
            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->ebayFactory->getCachedObjectLoaded(
            'Listing',
            $this->getRequest()->getParam('listing_id')
        );

        $converter = $this->getConverter($requestData['template_category_data'], $listing);
        $this->buildTemplatesOfCategories($converter);
        $this->saveListing($requestData, $listing);

        $this->setJsonContent(['success' => true]);
        return $this->getResult();
    }

    private function getConverter($templateCategoryData, \Ess\M2ePro\Model\Listing $listing)
    {
        $converter = $this->converterFactory->create();
        $converter->setAccountId($listing->getAccountId());
        $converter->setMarketplaceId($listing->getMarketplaceId());

        foreach ($templateCategoryData as $type => $templateData) {
            $converter->setCategoryDataFromChooser($templateData, $type);
        }

        return $converter;
    }

    private function buildTemplatesOfCategories(Converter $converter)
    {
        $this->tmpltCategory = $this->buildTemplateCategory(
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_EBAY_MAIN)
        );

        $this->secTmpltCategory = $this->buildTemplateCategory(
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_EBAY_SECONDARY)
        );

        $this->storeTmpltCategory = $this->buildStoreTemplateCategory(
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_STORE_MAIN)
        );

        $this->secStoreTmpltCategory = $this->buildStoreTemplateCategory(
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_STORE_SECONDARY)
        );
    }

    private function buildTemplateCategory(array $rawData)
    {
        $builder = $this->categoryBuilderFactory->create();
        return $builder->build($this->categoryFactory->create(), $rawData);
    }

    private function buildStoreTemplateCategory(array $rawData)
    {
        $builder = $this->storeCategoryBuilderFactory->create();
        return $builder->build($this->storeCategoryFactory->create(), $rawData);
    }

    private function saveListing($requestData, \Ess\M2ePro\Model\Listing $listing)
    {
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

        if ($requestData['auto_mode'] == Listing::AUTO_MODE_GLOBAL) {
            $listingData['auto_mode']               = Listing::AUTO_MODE_GLOBAL;
            $listingData['auto_global_adding_mode'] = $requestData['auto_global_adding_mode'];

            if ($requestData['auto_global_adding_mode'] == eBayListing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY) {
                $listingData['auto_global_adding_template_category_id'] = $this->tmpltCategory->getId();
                $listingData['auto_global_adding_template_category_secondary_id'] = $this->secTmpltCategory->getId();
                $listingData['auto_global_adding_template_store_category_id'] = $this->storeTmpltCategory->getId();
                $listingData['auto_global_adding_template_store_category_secondary_id']
                    = $this->secStoreTmpltCategory->getId();
            }

            if ($requestData['auto_global_adding_mode'] != Listing::ADDING_MODE_NONE) {
                $listingData['auto_global_adding_add_not_visible'] = $requestData['auto_global_adding_add_not_visible'];
            }
        }

        if ($requestData['auto_mode'] == Listing::AUTO_MODE_WEBSITE) {
            $listingData['auto_mode']                  = Listing::AUTO_MODE_WEBSITE;
            $listingData['auto_website_adding_mode']   = $requestData['auto_website_adding_mode'];
            $listingData['auto_website_deleting_mode'] = $requestData['auto_website_deleting_mode'];

            if ($requestData['auto_website_adding_mode'] == eBayListing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY) {
                $listingData['auto_website_adding_template_category_id'] = $this->tmpltCategory->getId();
                $listingData['auto_website_adding_template_category_secondary_id'] = $this->secTmpltCategory->getId();
                $listingData['auto_website_adding_template_store_category_id']  = $this->storeTmpltCategory->getId();
                $listingData['auto_website_adding_template_store_category_secondary_id']
                    = $this->secStoreTmpltCategory->getId();
            }

            if ($requestData['auto_website_adding_mode'] != Listing::ADDING_MODE_NONE) {
                $listingData['auto_website_adding_add_not_visible'] =
                    $requestData['auto_website_adding_add_not_visible'];
            }
        }

        if ($requestData['auto_mode'] == Listing::AUTO_MODE_CATEGORY) {
            $listingData['auto_mode'] = Listing::AUTO_MODE_CATEGORY;
            $this->saveAutoCategory($requestData, $listing->getId());
        }

        $listing->addData($listingData);
        $listing->getChildObject()->addData($listingData);
        $listing->save();
    }

    private function saveAutoCategory($requestData, $listingId)
    {
        $groupData = [
            'id'                     => null,
            'category'               => null,
            'title'                  => null,
            'auto_mode'              => Listing::AUTO_MODE_NONE,
            'adding_mode'            => Listing::ADDING_MODE_NONE,
            'adding_add_not_visible' => Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'deleting_mode'          => Listing::DELETING_MODE_NONE
        ];
        $groupData = array_merge($groupData, $requestData);

        $ebayGroupData = [
            'adding_template_category_id'                 => null,
            'adding_template_category_secondary_id'       => null,
            'adding_template_store_category_id'           => null,
            'adding_template_store_category_secondary_id' => null
        ];

        if ($requestData['adding_mode'] == eBayListing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY) {
            $ebayGroupData['adding_template_category_id'] = $this->tmpltCategory->getId();
            $ebayGroupData['adding_template_category_secondary_id'] = $this->secTmpltCategory->getId();
            $ebayGroupData['adding_template_store_category_id'] = $this->storeTmpltCategory->getId();
            $ebayGroupData['adding_template_store_category_secondary_id'] = $this->secStoreTmpltCategory->getId();
        }

        /** @var $group \Ess\M2ePro\Model\Listing\Auto\Category\Group */
        $group = $this->ebayFactory->getObject('Listing_Auto_Category_Group');
        $ebayGroup = $this->autoCategoryGroupFactory->create();

        if ((int)$requestData['id'] > 0) {
            $group->load((int)$requestData['id']);
        } else {
            unset($requestData['id']);
        }

        $group->addData($groupData);
        $group->setData('listing_id', $listingId);
        $group->save();

        $ebayGroup->setId($group->getId());
        $ebayGroup->addData($ebayGroupData);
        $ebayGroup->save();

        $group->clearCategories();

        foreach ($requestData['categories'] as $categoryId) {
            $category = $this->autoCategoryFactory->create();
            $category->setData('group_id', $group->getId());
            $category->setData('category_id', $categoryId);
            $category->save();
        }
    }
}
