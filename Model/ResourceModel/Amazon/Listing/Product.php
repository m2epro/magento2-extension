<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Listing;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product
 */
class Product extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);

        $this->amazonFactory = $amazonFactory;
    }

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_listing_product', 'listing_product_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function getChangedItems(
        array $attributes,
        $withStoreFilter = false
    ) {
        return $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getChangedItems(
            $attributes,
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            $withStoreFilter
        );
    }

    public function getChangedItemsByListingProduct(
        array $attributes,
        $withStoreFilter = false
    ) {
        return $this->activeRecordFactory->getObject('Listing\Product')
            ->getResource()->getChangedItemsByListingProduct(
                $attributes,
                \Ess\M2ePro\Helper\Component\Amazon::NICK,
                $withStoreFilter
            );
    }

    public function getChangedItemsByVariationOption(
        array $attributes,
        $withStoreFilter = false
    ) {
        return $this->activeRecordFactory->getObject('Listing\Product')
            ->getResource()->getChangedItemsByVariationOption(
                $attributes,
                \Ess\M2ePro\Helper\Component\Amazon::NICK,
                $withStoreFilter
            );
    }

    //########################################

    public function setSynchStatusNeedByDescriptionTemplate($newData, $oldData, $listingProduct)
    {
        $newTemplateData = [];
        if ($newData['template_description_id']) {
            $template = $this->amazonFactory->getCachedObjectLoaded(
                'Template\Description',
                $newData['template_description_id'],
                null,
                false
            );
            $template !== null && $newTemplateData = $template->getDataSnapshot();
        }

        $oldTemplateData = [];
        if ($oldData['template_description_id']) {
            $template = $this->amazonFactory->getCachedObjectLoaded(
                'Template\Description',
                $oldData['template_description_id'],
                null,
                false
            );
            $template !== null && $oldTemplateData = $template->getDataSnapshot();
        }

        $this->activeRecordFactory->getObject('Amazon_Template_Description')->getResource()->setSynchStatusNeed(
            $newTemplateData,
            $oldTemplateData,
            [$listingProduct]
        );
    }

    public function setSynchStatusNeedByShippingTemplate($newData, $oldData, $listingProduct, $modelName, $fieldName)
    {
        $newTemplateData = [];
        if (!empty($newData[$fieldName])) {
            $template = $this->activeRecordFactory->getCachedObjectLoaded(
                $modelName,
                $newData[$fieldName],
                null,
                false
            );
            $template !== null && $newTemplateData = $template->getDataSnapshot();
        }

        $oldTemplateData = [];
        if (!empty($oldData[$fieldName])) {
            $template = $this->activeRecordFactory->getCachedObjectLoaded(
                $modelName,
                $oldData[$fieldName],
                null,
                false
            );
            $template !== null && $oldTemplateData = $template->getDataSnapshot();
        }

        $this->activeRecordFactory->getObject($modelName)->getResource()->setSynchStatusNeed(
            $newTemplateData,
            $oldTemplateData,
            [$listingProduct]
        );
    }

    public function setSynchStatusNeedByProductTaxCodeTemplate($newData, $oldData, $listingProduct)
    {
        $newTemplateData = [];
        if ($newData['template_product_tax_code_id']) {
            $template = $this->activeRecordFactory->getCachedObjectLoaded(
                'Amazon_Template_ProductTaxCode',
                $newData['template_product_tax_code_id'],
                null,
                ['template']
            );
            $template && $newTemplateData = $template->getDataSnapshot();
        }

        $oldTemplateData = [];
        if ($oldData['template_product_tax_code_id']) {
            $template = $this->activeRecordFactory->getCachedObjectLoaded(
                'Amazon_Template_ProductTaxCode',
                $oldData['template_product_tax_code_id'],
                null,
                ['template']
            );
            $template && $oldTemplateData = $template->getDataSnapshot();
        }

        $this->activeRecordFactory->getObject('Amazon_Template_ProductTaxCode')->getResource()->setSynchStatusNeed(
            $newTemplateData,
            $oldTemplateData,
            [$listingProduct]
        );
    }

    //########################################

    public function getProductsDataBySkus(
        array $skus = [],
        array $filters = [],
        array $columns = []
    ) {
        $result = [];
        $skuWithQuotes = false;

        foreach ($skus as $sku) {
            if (strpos($sku, '"') !== false) {
                $skuWithQuotes = true;
                break;
            }
        }

        $skus = (empty($skus) || !$skuWithQuotes) ? [$skus] : array_chunk($skus, 500);

        foreach ($skus as $skusChunk) {
            $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
            $listingProductCollection->getSelect()->joinLeft(
                ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
                'l.id = main_table.listing_id',
                []
            );

            if (!empty($skusChunk)) {
                $skusChunk = array_map(function ($el) {
                    return (string)$el;
                }, $skusChunk);
                $listingProductCollection->addFieldToFilter('sku', ['in' => array_unique($skusChunk)]);
            }

            if (!empty($filters)) {
                foreach ($filters as $columnName => $columnValue) {
                    $listingProductCollection->addFieldToFilter($columnName, $columnValue);
                }
            }

            if (!empty($columns)) {
                $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
                $listingProductCollection->getSelect()->columns($columns);
            }

            $result = array_merge(
                $result,
                $listingProductCollection->getData()
            );
        }

        return $result;
    }

    //########################################
}
