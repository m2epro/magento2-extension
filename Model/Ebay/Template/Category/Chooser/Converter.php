<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */
namespace Ess\M2ePro\Model\Ebay\Template\Category\Chooser;

use \Ess\M2ePro\Helper\Component\Ebay\Category as Category;
use \Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\Converter
 */
class Converter extends \Ess\M2ePro\Model\AbstractModel
{
    protected $marketplaceId;
    protected $accountId;

    protected $categoriesData = [];

    //########################################

    /**
     * @param array $data
     * @param $type
     * @return $this
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setCategoryDataFromTemplate(array $data, $type)
    {
        if (!isset($data['category_mode'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Category mode is not provided.');
        }

        $converted = [
            'category_mode'      => $data['category_mode'],
            'category_id'        => $data['category_id'],
            'category_attribute' => $data['category_attribute'],
            'category_path'      => $data['category_path'],
            'template_id'        => $data['id'],
            'is_custom_template' => isset($data['is_custom_template']) ? $data['is_custom_template'] : null,
            'specific'           => isset($data['specific'])           ? $data['specific'] : []
        ];

        $this->categoriesData[$type] = $converted;
        return $this;
    }

    /**
     * @param array $data
     * @param $type
     * @return $this
     */
    public function setCategoryDataFromChooser(array $data, $type)
    {
        if (empty($data)) {
            return $this;
        }

        $converted = [
            'category_mode'      => $data['mode'],
            'category_id'        => $data['mode'] == TemplateCategory::CATEGORY_MODE_EBAY ? $data['value'] : null,
            'category_attribute' => $data['mode'] == TemplateCategory::CATEGORY_MODE_ATTRIBUTE ? $data['value'] : null,
            'category_path'      => isset($data['path'])               ? $data['path'] : null,
            'template_id'        => isset($data['template_id'])        ? $data['template_id'] : null,
            'is_custom_template' => isset($data['is_custom_template']) ? $data['is_custom_template'] : null,
            'specific'           => isset($data['specific'])           ? $data['specific'] : []
        ];

        $this->categoriesData[$type] = $converted;
        return $this;
    }

    //----------------------------------------

    public function getCategoryDataForChooser($type = null)
    {
        if ($type === null) {
            $result = [];
            foreach ($this->getCategoriesTypes() as $cType) {
                $temp = $this->getCategoryDataForChooser($cType);
                $temp !== null && $result[$cType] = $temp;
            }

            return $result;
        }

        if (!isset($this->categoriesData[$type])) {
            return null;
        }

        $part = $this->categoriesData[$type];
        return [
            'mode'               => $part['category_mode'],
            'value'              => $part['category_mode'] == TemplateCategory::CATEGORY_MODE_EBAY
                                        ? $part['category_id'] : $part['category_attribute'],
            'path'               => $part['category_path'],
            'template_id'        => $part['template_id'],
            'is_custom_template' => $part['is_custom_template'],
        ];
    }

    public function getCategoryDataForTemplate($type)
    {
        if (!isset($this->categoriesData[$type])) {
            return [];
        }

        $part = $this->categoriesData[$type];
        $part['account_id']     = $this->accountId;
        $part['marketplace_id'] = $this->marketplaceId;

        return $part;
    }

    //########################################

    public function setMarketplaceId($marketplaceId)
    {
        $this->marketplaceId = $marketplaceId;
        return $this;
    }

    public function setAccountId($accountId)
    {
        $this->accountId = $accountId;
        return $this;
    }

    //########################################

    protected function getCategoriesTypes()
    {
        return [
            Category::TYPE_EBAY_MAIN,
            Category::TYPE_EBAY_SECONDARY,
            Category::TYPE_STORE_MAIN,
            Category::TYPE_STORE_SECONDARY
        ];
    }

    //########################################
}
