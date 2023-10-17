<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalStaticTabs
{
    private const GENERAL_TAB_ID = 'general';
    private const POLICIES_TAB_ID = 'policies';
    private const CATEGORIES_TAB_ID = 'categories';

    protected function init(): void
    {
        $cssMb20 = 'margin-bottom: 20px;';
        $cssMb10 = 'margin-bottom: 10px;';

        $id = $this->getRequest()->getParam('id');
        // ---------------------------------------

        $this->addTab(
            self::GENERAL_TAB_ID,
            __('General'),
            $this->getUrl('*/ebay_listing_edit/general', ['id' => $id, '_current' => true])
        );
        $this->registerCssForTab(self::GENERAL_TAB_ID, $cssMb20);

        // ---------------------------------------

        $this->addTab(
            self::POLICIES_TAB_ID,
            $this->__('Policies'),
            $this->getUrl('*/ebay_listing_edit/policies', ['id' => $id, '_current' => true])
        );
        $this->registerCssForTab(self::POLICIES_TAB_ID, $cssMb20);

        // ---------------------------------------

        $this->addTab(
            self::CATEGORIES_TAB_ID,
            __('Categories'),
            $this->getUrl('*/ebay_listing_edit/categories', ['id' => $id, '_current' => true])
        );
        $this->registerCssForTab(self::CATEGORIES_TAB_ID, $cssMb20);

        // ---------------------------------------
    }

    /**
     * @return void
     */
    public function activateGeneralTab(): void
    {
        $this->setActiveTabId(self::GENERAL_TAB_ID);
    }

    /**
     * @return void
     */
    public function activatePoliciesTab(): void
    {
        $this->setActiveTabId(self::POLICIES_TAB_ID);
    }

    /**
     * @return void
     */
    public function activateCategoriesTab(): void
    {
        $this->setActiveTabId(self::CATEGORIES_TAB_ID);
    }
}
