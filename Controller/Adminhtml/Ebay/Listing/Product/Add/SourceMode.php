<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add\SourceMode
 */
class SourceMode extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add
{

    public function execute()
    {
        $this->setWizardStep('sourceMode');

        if ($this->getRequest()->isPost()) {
            $source = $this->getRequest()->getPost('source');

            if (!in_array($source, [
                \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode::MODE_PRODUCT,
                \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode::MODE_CATEGORY
            ])) {
                return $this->_redirect('*/*/*', ['_current' => true]);
            }

            $this->setSessionValue('source', $source);

            return $this->_redirect(
                '*/ebay_listing_product_add',
                [
                    'source' => $source,
                    'clear' => true,
                    'listing_creation' => true,
                    '_current' => true
                ]
            );
        }

        $this->getHelper('Data\GlobalData')->setValue('listing_for_products_add', $this->getListing());

        $this->addContent($this->createBlock('Ebay_Listing_Product_Add_SourceMode'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Add Magento Products'));
        $this->setPageHelpLink('x/dwItAQ');

        return $this->getResult();
    }

    //########################################
}
