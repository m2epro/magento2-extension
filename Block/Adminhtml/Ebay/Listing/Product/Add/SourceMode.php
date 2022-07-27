<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add;

class SourceMode extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    const MODE_PRODUCT  = 'product';
    const MODE_CATEGORY = 'category';
    const MODE_OTHER    = 'other';

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingSourceMode');
        $this->_controller = 'adminhtml_ebay_listing_product_add';
        $this->_mode = 'sourceMode';

        $this->_headerText = $this->__('Add Products');

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        if (!$this->getRequest()->getParam('listing_creation', false)) {
            $url = $this->getUrl('*/ebay_listing/view', [
                'id' => $this->getRequest()->getParam('id')
            ]);
            $this->addButton('back', [
                'label'     => $this->__('Back'),
                'onclick'   => 'setLocation(\''.$url.'\')',
                'class'     => 'back'
            ]);
        }

        // ---------------------------------------
        $url = $this->getUrl('*/*/*', ['_current' => true]);
        $this->addButton('next', [
            'label'     => $this->__('Continue'),
            'onclick'   => 'CommonObj.submitForm(\''.$url.'\');',
            'class'     => 'action-primary forward'
        ]);
        // ---------------------------------------
    }

    protected function _toHtml()
    {
        $listing = $this->globalDataHelper->getValue('listing_for_products_add');

        $viewHeaderBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class,
            '',
            ['data' => ['listing' => $listing]]
        );

        return $viewHeaderBlock->toHtml() . parent::_toHtml();
    }
}
