<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;
use \Magento\Backend\App\Action;

class GetTemplateHtml extends Template
{
    //########################################

    public function execute()
    {
        try {

            // ---------------------------------------
            /** @var \Ess\M2ePro\Helper\Component\Ebay\Template\Switcher\DataLoader $dataLoader */
            $dataLoader = $this->getHelper('Component\Ebay\Template\Switcher\DataLoader');
            $dataLoader->load($this->getRequest());
            // ---------------------------------------

            // ---------------------------------------
            $templateNick = $this->getRequest()->getParam('nick');
            $templateDataForce = (bool)$this->getRequest()->getParam('data_force', false);

            /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Template\Switcher $switcherBlock */
            $switcherBlock = $this->createBlock('Ebay\Listing\Template\Switcher');
            $switcherBlock->setData(['template_nick' => $templateNick]);
            // ---------------------------------------

            $this->setAjaxContent($switcherBlock->getFormDataBlockHtml($templateDataForce));

        } catch (\Exception $e) {
            $this->setJsonContent(['error' => $e->getMessage()]);
        }

        return $this->getResult();
    }

    //########################################
}