<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\Ebay\Template\Category',
            'Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category'
        );
    }

    //########################################

    /**
     * @param $primaryCategoriesData
     * @return \Ess\M2ePro\Model\Ebay\Template\Category[]
     */
    public function getItemsByPrimaryCategories($primaryCategoriesData)
    {
        $conn = $this->getConnection();

        $where = '';
        foreach ($primaryCategoriesData as $categoryData) {
            $where && $where .= ' OR ';

            $categoryData['category_main_id'] = (int)$categoryData['category_main_id'];
            $categoryData['marketplace_id']   = (int)$categoryData['marketplace_id'];

            $where .= "(marketplace_id  = {$categoryData['marketplace_id']} AND";
            $where .= " category_main_id   = {$categoryData['category_main_id']} AND";
            $where .= " category_main_mode = {$conn->quote($categoryData['category_main_mode'])} AND";
            $where .= " category_main_attribute = {$conn->quote($categoryData['category_main_attribute'])}) ";
        }

        $this->getSelect()->where($where);
        $this->getSelect()->order('create_date DESC');

        $templates = [];
        /** @var $template \Ess\M2ePro\Model\Ebay\Template\Category */
        foreach ($this->getItems() as $template) {
            if ($template['category_main_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
                $key = $template['category_main_id'];
            } else {
                $key = $template['category_main_attribute'];
            }

            if (isset($templates[$key])) {
                continue;
            }

            $templates[$key] = $template;
        }

        return $templates;
    }

    //########################################
}
