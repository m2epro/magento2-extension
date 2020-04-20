<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\View;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Listing\View\Grid
 */
abstract class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Product\Grid
{
    /**
     * @var \Ess\M2ePro\Model\Listing
     */
    protected $listing;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('view_listing');
    }

    //########################################

    public function setCollection($collection)
    {
        if ($this->listing) {
            $collection->setStoreId($this->listing['store_id']);
        }

        parent::setCollection($collection);
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/view/grid.css');

        return parent::_prepareLayout();
    }

    //########################################

    public function getStoreId()
    {
        return (int)$this->listing['store_id'];
    }

    //########################################

    protected function _toHtml()
    {
        // ---------------------------------------

        if ($this->getRequest()->isXmlHttpRequest()) {
            return parent::_toHtml();
        }

        // ---------------------------------------
        $helper = $this->getHelper('Data');

        $this->jsTranslator->addTranslations([
            'Are you sure you want to create empty Listing?' => $helper->escapeJs(
                $this->__('Are you sure you want to create empty Listing?')
            )
        ]);

        // ---------------------------------------

        return parent::_toHtml();
    }

    //########################################
}
