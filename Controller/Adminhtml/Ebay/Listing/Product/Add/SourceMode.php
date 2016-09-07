<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add;

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
                return $this->_redirect('*/*/*',array('_current' => true));
            }

            $this->setSessionValue('source', $source);

            return $this->_redirect(
                '*/ebay_listing_product_add',
                array(
                    'source' => $source,
                    'clear' => true,
                    'listing_creation' => true,
                    '_current' => true
                )
            );
        }

        $this->getHelper('Data\GlobalData')->setValue('listing_for_products_add', $this->getListing());

        $this->addContent($this->createBlock('Ebay\Listing\Product\Add\SourceMode'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Add Magento Products'));
        $this->setPageHelpLink('x/dwItAQ');

        return $this->getResult();
    }

    //########################################
}