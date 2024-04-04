<?php

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

class Categories extends AbstractModel
{
    /** @var \Ess\M2ePro\Model\Ebay\Template\Category */
    private $categoryTemplate;

    /** @var \Ess\M2ePro\Model\Ebay\Template\Category */
    protected $categorySecondaryTemplate = null;

    /** @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory */
    protected $storeCategoryTemplate = null;

    /** @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory */
    protected $storeCategorySecondaryTemplate = null;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    //---------------------------------------

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //---------------------------------------

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBuilderData(): array
    {
        $data = $this->getCategoriesData();
        $data['item_specifics'] = $this->getItemSpecificsData();

        return $data;
    }

    //---------------------------------------

    public function getCategoriesData(): array
    {
        $data = [
            'category_main_id' => $this->getCategorySource()->getCategoryId(),
            'category_secondary_id' => 0,
            'store_category_main_id' => 0,
            'store_category_secondary_id' => 0,
        ];

        if ($this->getCategorySecondaryTemplate() !== null) {
            $data['category_secondary_id'] = $this->getCategorySecondarySource()->getCategoryId();
        }

        if ($this->getStoreCategoryTemplate() !== null) {
            $data['store_category_main_id'] = $this->getStoreCategorySource()->getCategoryId();
        }

        if ($this->getStoreCategorySecondaryTemplate() !== null) {
            $data['store_category_secondary_id'] = $this->getStoreCategorySecondarySource()->getCategoryId();
        }

        return $data;
    }

    public function getItemSpecificsData(): array
    {
        $data = [];

        foreach ($this->getCategoryTemplate()->getSpecifics(true) as $specific) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Specific $specific */

            $this->searchNotFoundAttributes();

            $tempAttributeLabel = $specific->getSource($this->getMagentoProduct())
                                           ->getLabel();
            $tempAttributeValues = $specific->getSource($this->getMagentoProduct())
                                            ->getValues();

            if (is_array($tempAttributeValues) && !empty($tempAttributeValues['found_in_children'])) {
                $this->addFoundAttributesInChildrenMessages([$tempAttributeValues['code']]);
                $tempAttributeValues = [$tempAttributeValues['value']];
            } elseif (empty($tempAttributeValues) || !$this->processNotFoundAttributes('Specifics')) {
                continue;
            }

            $values = [];
            foreach ($tempAttributeValues as $tempAttributeValue) {
                if ($tempAttributeValue === '--') {
                    continue;
                }

                $values[] = $tempAttributeValue;
            }

            $data[] = [
                'name' => $tempAttributeLabel,
                'value' => $values,
            ];
        }

        return $data;
    }

    //---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     */
    protected function getCategoryTemplate()
    {
        if ($this->categoryTemplate === null) {
            $this->categoryTemplate = $this->getListingProduct()
                                           ->getChildObject()
                                           ->getCategoryTemplate();
        }

        return $this->categoryTemplate;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     */
    protected function getCategorySecondaryTemplate()
    {
        if ($this->categorySecondaryTemplate === null) {
            $this->categorySecondaryTemplate = $this->getListingProduct()->getChildObject()
                                                    ->getCategorySecondaryTemplate();
        }

        return $this->categorySecondaryTemplate;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    protected function getStoreCategoryTemplate()
    {
        if ($this->storeCategoryTemplate === null) {
            $this->storeCategoryTemplate = $this->getListingProduct()->getChildObject()
                                                ->getStoreCategoryTemplate();
        }

        return $this->storeCategoryTemplate;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    protected function getStoreCategorySecondaryTemplate()
    {
        if ($this->storeCategorySecondaryTemplate === null) {
            $this->storeCategorySecondaryTemplate = $this->getListingProduct()->getChildObject()
                                                         ->getStoreCategorySecondaryTemplate();
        }

        return $this->storeCategorySecondaryTemplate;
    }

    //---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category\Source
     */
    protected function getCategorySource()
    {
        return $this->getEbayListingProduct()->getCategoryTemplateSource();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category\Source
     */
    protected function getCategorySecondarySource()
    {
        return $this->getEbayListingProduct()->getCategorySecondaryTemplateSource();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory\Source
     */
    protected function getStoreCategorySource()
    {
        return $this->getEbayListingProduct()->getStoreCategoryTemplateSource();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory\Source
     */
    protected function getStoreCategorySecondarySource()
    {
        return $this->getEbayListingProduct()->getStoreCategorySecondaryTemplateSource();
    }

    //---------------------------------------
}
