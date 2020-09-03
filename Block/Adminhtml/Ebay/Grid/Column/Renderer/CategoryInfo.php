<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer;

use \Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;
use \Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;
use \Ess\M2ePro\Block\Adminhtml\Traits;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\CategoryInfo
 */
class CategoryInfo extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    use Traits\BlockTrait;

    /** @var \Magento\Framework\DataObject */
    protected $_row;

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\Listing */
    protected $_listing;

    /** @var string */
    protected $_entityIdField;

    /** @var array */
    protected $_categoriesData = [];

    /** @var bool */
    protected $_hideSpecificsRequiredMark = false;

    /** @var bool */
    protected $_hideUnselectedSpecifics = false;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Backend\Block\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helperFactory = $helperFactory;
    }

    //########################################

    public function render(\Magento\Framework\DataObject  $row)
    {
        $this->_row = $row;

        $id = $row->getData($this->_entityIdField);
        $categoriesData = isset($this->_categoriesData[$id]) ? $this->_categoriesData[$id] : [];

        $html = '';
        $html .= $this->renderCategoryInfo($categoriesData, eBayCategory::TYPE_EBAY_MAIN);
        $html .= $this->renderItemSpecifics($categoriesData);
        $html .= $this->renderCategoryInfo($categoriesData, eBayCategory::TYPE_EBAY_SECONDARY);
        $html .= $this->renderCategoryInfo($categoriesData, eBayCategory::TYPE_STORE_MAIN);
        $html .= $this->renderCategoryInfo($categoriesData, eBayCategory::TYPE_STORE_SECONDARY);

        if (empty($html)) {
            $iconSrc = $this->getViewFileUrl('Ess_M2ePro::images/warning.png');
            $html .= <<<HTML
<img src="{$iconSrc}" alt="">&nbsp;<span style="font-style: italic; color: gray">
    {$this->getHelper('Module\Translation')->__('Not Selected')}</span>
HTML;
        }

        return $html;
    }

    protected function renderCategoryInfo($categoryData, $categoryType)
    {
        $titles = [
            eBayCategory::TYPE_EBAY_MAIN       => $this->getHelper('Module\Translation')->__('eBay Primary Category'),
            eBayCategory::TYPE_EBAY_SECONDARY  => $this->getHelper('Module\Translation')->__('eBay Secondary Category'),
            eBayCategory::TYPE_STORE_MAIN      => $this->getHelper('Module\Translation')->__('Store Primary Category'),
            eBayCategory::TYPE_STORE_SECONDARY => $this->getHelper('Module\Translation')->__('Store Secondary Category')
        ];

        if (!isset($categoryData[$categoryType], $titles[$categoryType]) ||
            !isset(
                $categoryData[$categoryType]['mode'],
                $categoryData[$categoryType]['path'],
                $categoryData[$categoryType]['value']
            )
        ) {
            return '';
        }

        $info = '';
        if ($categoryData[$categoryType]['mode'] == TemplateCategory::CATEGORY_MODE_EBAY) {
            $info = "{$categoryData[$categoryType]['path']}&nbsp;({$categoryData[$categoryType]['value']})";
        } elseif ($categoryData[$categoryType]['mode'] == TemplateCategory::CATEGORY_MODE_ATTRIBUTE) {
            $info = $categoryData[$categoryType]['path'];
        }

        return <<<HTML
<div>
    <span style="text-decoration: underline">{$titles[$categoryType]}:</span>
    <p style="padding: 2px 0 0 10px;">{$info}</p>
</div>
HTML;
    }

    protected function renderItemSpecifics($categoryData)
    {
        if (empty($categoryData[eBayCategory::TYPE_EBAY_MAIN]['value'])) {
            return '';
        }

        if (!isset($categoryData[eBayCategory::TYPE_EBAY_MAIN]['is_custom_template']) &&
            $this->_hideUnselectedSpecifics
        ) {
            return '';
        }

        $specificsRequired = $this->getHelper('Component_Ebay_Category_Ebay')->hasRequiredSpecifics(
            $categoryData[eBayCategory::TYPE_EBAY_MAIN]['value'],
            $this->_listing->getMarketplaceId()
        );

        $requiredMark = '';
        if ($specificsRequired && !$this->_hideSpecificsRequiredMark) {
            $requiredMark = '&nbsp;<span class="required">*</span>';
        }

        if (!isset($categoryData[eBayCategory::TYPE_EBAY_MAIN]['is_custom_template'])) {
            $color = $specificsRequired ? 'red' : 'grey';
            $info = <<<HTML
<span style="font-style: italic; color: {$color}">{$this->getHelper('Module\Translation')->__('Not Set')}</span>
HTML;
        } elseif ($categoryData[eBayCategory::TYPE_EBAY_MAIN]['is_custom_template'] == 1) {
            $info = "<span>{$this->getHelper('Module\Translation')->__('Custom')}</span>";
        } else {
            $info = "<span>{$this->getHelper('Module\Translation')->__('Default')}</span>";
        }

        return <<<HTML
<div style="margin-bottom: .5em;">
    <span style="text-decoration: underline">{$this->getHelper('Module\Translation')->__('Item Specifics')}:</span>{$requiredMark}&nbsp;
    {$info}
</div>
HTML;
    }

    //########################################

    public function setCategoriesData($data)
    {
        $this->_categoriesData = $data;
        return $this;
    }

    public function setHideSpecificsRequiredMark($mode)
    {
        $this->_hideSpecificsRequiredMark = $mode;
        return $this;
    }

    public function setHideUnselectedSpecifics($mode)
    {
        $this->_hideUnselectedSpecifics = $mode;
        return $this;
    }

    public function setListing(\Ess\M2ePro\Model\Listing $listing)
    {
        $this->_listing = $listing;
        return $this;
    }

    public function setEntityIdField($field)
    {
        $this->_entityIdField = $field;
        return $this;
    }

    //########################################
}
