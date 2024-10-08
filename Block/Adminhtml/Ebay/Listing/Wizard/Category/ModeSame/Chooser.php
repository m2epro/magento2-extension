<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\ModeSame;

use Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser as CategoryChooser;

class Chooser extends CategoryChooser
{
    public function _construct()
    {
        parent::_construct();

        $this->setTemplate('ebay/listing/wizard/category/category_chooser.phtml');
    }

    public function hasStoreCatalog()
    {
        if ($this->getAccountId() === null) {
            return false;
        }

        $storeCategories = $this->ebayFactory
            ->getCachedObjectLoaded('Account', (int)$this->getAccountId())
            ->getChildObject()
            ->getEbayStoreCategories();

        return !empty($storeCategories);
    }
}
