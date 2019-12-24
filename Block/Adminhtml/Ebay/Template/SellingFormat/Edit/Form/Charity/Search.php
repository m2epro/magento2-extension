<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\SellingFormat\Edit\Form\Charity;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\SellingFormat\Edit\Form\Charity\Search
 */
class Search extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingTemplateSearchCharity');
        // ---------------------------------------

        $this->setTemplate('ebay/template/selling_format/charity/search.phtml');
    }

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        // ---------------------------------------
        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData([
                'id'    => 'searchCharity_submit',
                'class' => 'action primary',
                'label' => $this->__('Search'),
                'onclick' => 'EbayTemplateSellingFormatObj.searchCharity()'
            ]);
        $this->setChild('submit_button', $buttonBlock);
        // ---------------------------------------

        $this->setChild(
            'search_charity_warning',
            $this->getLayout()->createBlock(\Magento\Framework\View\Element\Messages::class)
                ->addWarning($this->__('If you do not see the organization you were looking for,
                try to enter another keywords and run the Search again.'))
        );
    }

    //########################################
}
