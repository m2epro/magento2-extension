<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Search;

class Menu extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct = null;

    public function _construct()
    {
        parent::_construct();

        $this->setTemplate('amazon/listing/product/search/menu.phtml');
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing\Product|null
     */
    public function getListingProduct()
    {
        return $this->listingProduct;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return $this
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;

        return $this;
    }

    //########################################

    public function isIndividualFromBundleOrSimpleOrDownloadable()
    {
        if (!$this->getListingProduct()->getChildObject()->getVariationManager()->isIndividualType()) {
            return false;
        }

        return $this->getListingProduct()->getMagentoProduct()->isBundleType() ||
               $this->getListingProduct()->getMagentoProduct()->isSimpleTypeWithCustomOptions() ||
               $this->getListingProduct()->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks();
    }

    public function isParentFromBundleOrSimpleOrDownloadable()
    {
        if (!$this->getListingProduct()->getChildObject()->getVariationManager()->isRelationParentType()) {
            return false;
        }

        return $this->getListingProduct()->getMagentoProduct()->isBundleType() ||
               $this->getListingProduct()->getMagentoProduct()->isSimpleTypeWithCustomOptions() ||
               $this->getListingProduct()->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks();
    }

    //########################################

    public function getWarnings()
    {
        if ($this->getListingProduct()->getChildObject()->getData('search_settings_status') ==
            \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_NOT_FOUND) {

            /** @var \Magento\Framework\View\Element\Messages $messages */
            $messages = $this->getLayout()->createBlock('\Magento\Framework\View\Element\Messages');
            $messages->addWarning(
                'There were no Products found on Amazon according to the Listing Search Settings.'
            );

            return <<<HTML
<div style="margin: 6px 0px">
    {$messages->toHtml()}
</div>
HTML;
        }

        return '';

    }

    //########################################
}