<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category
 */
class Category extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_template_category', 'id');
        $this->addUniqueField(
            [
                'field' => [
                    'marketplace_id',
                    'category_id',
                    'category_attribute',
                    'is_custom_template'
                ],
                'title' => $this->helperFactory->getObject('Module\Translation')->__('CategoryTemplate with same data')
            ]
        );
    }

    //########################################

    public function loadByCategoryValue(
        \Ess\M2ePro\Model\Ebay\Template\Category $object,
        $value,
        $mode,
        $marketplaceId,
        $isCustomTemplate = null
    ) {
        /** @var \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel $collection */
        $collection = $object->getCollection();
        $collection->addFieldToFilter('category_mode', $mode);
        $collection->addFieldToFilter('marketplace_id', $marketplaceId);

        if ($isCustomTemplate !== null) {
            $collection->addFieldToFilter('is_custom_template', (int)$isCustomTemplate);
        }

        $mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY
            ? $collection->addFieldToFilter('category_id', $value)
            : $collection->addFieldToFilter('category_attribute', $value);

        // @codingStandardsIgnoreLine
        if ($firstItem = $collection->getFirstItem()) {
            $object->setData($firstItem->getData());
        }
    }

    //########################################

    protected function _checkUnique(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->getData('is_custom_template')) {
            return $this;
        }

        return parent::_checkUnique($object);
    }

    //########################################
}
