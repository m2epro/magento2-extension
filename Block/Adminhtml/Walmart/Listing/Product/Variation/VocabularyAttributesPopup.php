<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\VocabularyAttributesPopup
 */
class VocabularyAttributesPopup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingVocabularyAttributesPopup');
        // ---------------------------------------

        $this->setTemplate('walmart/listing/product/variation/vocabulary_attributes_popup.phtml');
    }
}
