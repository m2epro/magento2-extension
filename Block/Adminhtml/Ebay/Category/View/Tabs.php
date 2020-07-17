<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Category\View;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalTabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Category\View\Tabs
 */
class Tabs extends AbstractHorizontalTabs
{
    const TAB_ID_ITEM_SPECIFICS     = 'item_specifics';
    const TAB_ID_PRODUCTS_PRIMARY   = 'products_primary';
    const TAB_ID_PRODUCTS_SECONDARY = 'products_secondary';

    //########################################

    protected function _construct()
    {
        parent::_construct();

        $this->setId('ebayCategoryViewTabs');
        $this->setDestElementId('category_tab_container');
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->addTab(self::TAB_ID_ITEM_SPECIFICS, $this->prepareTabItemSpecifics());
        $this->addTab(self::TAB_ID_PRODUCTS_PRIMARY, $this->prepareTabProductsPrimary());
        $this->addTab(self::TAB_ID_PRODUCTS_SECONDARY, $this->prepareTabProductsSecondary());

        $this->setActiveTab($this->getActiveTab());

        return parent::_prepareLayout();
    }

    //########################################

    protected function prepareTabItemSpecifics()
    {
        $tab = [
            'label' => $this->__('Item Specific (Default)'),
            'title' => $this->__('Item Specific (Default)')
        ];
        if ($this->getActiveTab() == self::TAB_ID_ITEM_SPECIFICS) {
            $tab['content'] = $this->createBlock('Ebay_Category_View_Tabs_ItemSpecific_Edit')->toHtml();
        } else {
            $tab['url'] = $this->getUrl(
                '*/ebay_category/view',
                [
                    'active_tab' => self::TAB_ID_ITEM_SPECIFICS,
                    'template_id' => $this->getTemplateCategoryId()
                ]
            );
        }

        return $tab;
    }

    protected function prepareTabProductsPrimary()
    {
        $tab = [
            'label' => $this->__('Primary Category'),
            'title' => $this->__('Products with Primary Category')
        ];
        if ($this->getActiveTab() == self::TAB_ID_PRODUCTS_PRIMARY) {
            $tab['content'] = $this->createBlock('Ebay_Category_View_Tabs_ProductsPrimary')->toHtml();
        } else {
            $tab['url'] = $this->getUrl(
                '*/ebay_category/view',
                [
                    'active_tab' => self::TAB_ID_PRODUCTS_PRIMARY,
                    'template_id' => $this->getTemplateCategoryId()
                ]
            );
        }

        return $tab;
    }

    protected function prepareTabProductsSecondary()
    {
        $tab = [
            'label' => $this->__('Secondary Category'),
            'title' => $this->__('Products with Secondary Category')
        ];
        if ($this->getActiveTab() == self::TAB_ID_PRODUCTS_SECONDARY) {
            $tab['content'] = $this->createBlock('Ebay_Category_View_Tabs_ProductsSecondary')->toHtml();
        } else {
            $tab['url'] = $this->getUrl(
                '*/ebay_category/view',
                [
                    'active_tab' => self::TAB_ID_PRODUCTS_SECONDARY,
                    'template_id' => $this->getTemplateCategoryId()
                ]
            );
        }

        return $tab;
    }

    //########################################

    protected function getActiveTab()
    {
        $activeTab = $this->getRequest()->getParam('active_tab', self::TAB_ID_ITEM_SPECIFICS);
        $allowedTabs = [
            self::TAB_ID_ITEM_SPECIFICS,
            self::TAB_ID_PRODUCTS_PRIMARY,
            self::TAB_ID_PRODUCTS_SECONDARY
        ];

        if (!in_array($activeTab, $allowedTabs)) {
            $activeTab = self::TAB_ID_ITEM_SPECIFICS;
        }

        return $activeTab;
    }

    protected function getTemplateCategoryId()
    {
        return $this->getRequest()->getParam('template_id');
    }

    //########################################
}
