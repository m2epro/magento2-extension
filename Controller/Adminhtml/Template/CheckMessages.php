<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Template;

use Ess\M2ePro\Controller\Adminhtml\Base;

class CheckMessages extends Base
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_configuration_templates') ||
               $this->_authorization->isAllowed('Ess_M2ePro::amazon_configuration_templates');
    }

    //########################################

    public function execute()
    {
        // ---------------------------------------
        $id   = $this->getRequest()->getParam('id');
        $nick = $this->getRequest()->getParam('nick');
        $data = $this->getRequest()->getParam($nick);
        $component = $this->getRequest()->getParam('component_mode');
        // ---------------------------------------

        // ---------------------------------------
        $template = NULL;
        $templateData = $data ? $data : array();
        $templateUsedAttributes = array();
        // ---------------------------------------

        // ---------------------------------------
        switch ($component) {
            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                $manager = $this->modelFactory->getObject('Ebay\Template\Manager');
                $manager->setTemplate($nick);
                $template = $this->activeRecordFactory->getObjectLoaded(
                    $manager->getTemplateModelName(), $id, NULL, false
                );
                break;
            default:
                if ($nick == \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT) {
                    $template = $this->parentFactory->getObjectLoaded(
                        $component, 'Template\SellingFormat', $id, NULL, false
                    );
                }
                break;
        }
        // ---------------------------------------

        if (!is_null($template) && $template->getId()) {
            $templateData = $template->getData();
            if ($template instanceof \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel) {
                $templateData = array_merge($templateData, $template->getChildObject()->getData());
            }
            $templateUsedAttributes = $template->getUsedAttributes();
        }

        // ---------------------------------------
        if (is_null($template) && empty($templateData)) {
            $this->setJsonContent(['messages' => '']);
            return $this->getResult();
        }
        // ---------------------------------------

        /** @var \Ess\M2ePro\Block\Adminhtml\Template\Messages $messagesBlock */
        $messagesBlock = $this->createBlock('Template\Messages')->getResultBlock($nick, $component);

        $messagesBlock->setData('template_data', $templateData);
        $messagesBlock->setData('used_attributes', $templateUsedAttributes);
        $messagesBlock->setData('marketplace_id', $this->getRequest()->getParam('marketplace_id'));
        $messagesBlock->setData('store_id', $this->getRequest()->getParam('store_id'));

        $this->setJsonContent(['messages' => $messagesBlock->getMessagesHtml()]);
        return $this->getResult();
    }

    //########################################
}