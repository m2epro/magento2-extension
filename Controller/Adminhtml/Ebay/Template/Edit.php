<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;
use Magento\Backend\App\Action;

class Edit extends Template
{
    //########################################

    public function execute()
    {
        // ---------------------------------------
        $id = $this->getRequest()->getParam('id');
        $nick = $this->getRequest()->getParam('nick');
        // ---------------------------------------

        // ---------------------------------------
        $manager = $this->templateManager->setTemplate($nick);
        $template = $manager->getTemplateModel()
            ->getCollection()
            ->addFieldToFilter('id', $id)
            ->addFieldToFilter('is_custom_template', 0)
            ->getFirstItem();
        // ---------------------------------------

        // ---------------------------------------
        if (!$template->getId() && $id) {
            $this->getMessageManager()->addError($this->__('Policy does not exist.'));
            return $this->_redirect('*/*/index');
        }
        // ---------------------------------------

        // ---------------------------------------
        /** @var \Ess\M2ePro\Helper\Component\Ebay\Template\Switcher\DataLoader $dataLoader */
        $dataLoader = $this->getHelper('Component\Ebay\Template\Switcher\DataLoader');
        $dataLoader->load($template);
        // ---------------------------------------

        $content = $this->getLayout()->createBlock(
            'Ess\\M2ePro\\Block\\Adminhtml\\Ebay\\Template\\Edit', '', ['data' => [
                'template_nick' => $nick
            ]]
        );

        switch ($nick) {

            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY:
                $this->setComponentPageHelpLink('Return+Settings');
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT:
                $this->setComponentPageHelpLink('Payment+Settings');
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING:
                $this->setComponentPageHelpLink('Shipping+Settings');
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION:
                $this->setComponentPageHelpLink('Description+Policy');
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT:
                $this->setComponentPageHelpLink('Price%2C+Quantity+and+Format+Policy');
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION:
                $this->setComponentPageHelpLink('Step+4.+Synchronization+settings');
                break;
        }

        if ($template->getId()) {
            $headerText =
                $this->__('Edit "%template_title%" %template_name% Policy',
                    $this->getHelper('Data')->escapeHtml($template->getTitle()),
                    $this->getTemplateName($nick)
                );
        } else {
            $headerText = $this->__('Add %template_name% Policy',
                $this->getTemplateName($nick)
            );
        }

        $this->getResult()->getConfig()->getTitle()->prepend($headerText);
        $this->addContent($content);
        return $this->getResult();
    }

    protected function getTemplateName($nick)
    {
        $title = '';

        switch ($nick) {
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT:
                $title = $this->__('Payment');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING:
                $title = $this->__('Shipping');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY:
                $title = $this->__('Return');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT:
                $title = $this->__('Price, Quantity and Format');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION:
                $title = $this->__('Description');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION:
                $title = $this->__('Synchronization');
                break;
        }

        return $title;
    }

    //########################################
}