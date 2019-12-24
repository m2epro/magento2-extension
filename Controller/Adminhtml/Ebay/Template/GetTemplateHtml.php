<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;
use \Magento\Backend\App\Action;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\GetTemplateHtml
 */
class GetTemplateHtml extends Template
{
    //########################################

    public function execute()
    {
        try {
            // ---------------------------------------
            /** @var \Ess\M2ePro\Helper\Component\Ebay\Template\Switcher\DataLoader $dataLoader */
            $dataLoader = $this->getHelper('Component_Ebay_Template_Switcher_DataLoader');
            $dataLoader->load($this->getRequest());
            // ---------------------------------------

            // ---------------------------------------
            $templateNick = $this->getRequest()->getParam('nick');
            $templateDataForce = (bool)$this->getRequest()->getParam('data_force', false);

            /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Template\Switcher $switcherBlock */
            $switcherBlock = $this->createBlock('Ebay_Listing_Template_Switcher');
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
