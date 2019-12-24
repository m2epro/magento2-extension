<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Template;

use Ess\M2ePro\Controller\Adminhtml\Base;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Template\CheckMessages
 */
class CheckMessages extends Base
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_configuration_templates') ||
               $this->_authorization->isAllowed('Ess_M2ePro::amazon_configuration_templates') ||
               $this->_authorization->isAllowed('Ess_M2ePro::walmart_configuration_templates');
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
        $template = null;
        $templateData = $data ? $data : [];
        $templateUsedAttributes = [];
        // ---------------------------------------

        // ---------------------------------------
        switch ($component) {
            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                $manager = $this->modelFactory->getObject('Ebay_Template_Manager');
                $manager->setTemplate($nick);
                $template = $this->activeRecordFactory->getObjectLoaded(
                    $manager->getTemplateModelName(),
                    $id,
                    null,
                    false
                );
                break;
            default:
                if ($nick == \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT) {
                    $template = $this->parentFactory->getObjectLoaded(
                        $component,
                        'Template\SellingFormat',
                        $id,
                        null,
                        false
                    );
                }
                break;
        }
        // ---------------------------------------

        if ($template !== null && $template->getId()) {
            $templateData = $template->getData();
            if ($template instanceof \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel) {
                $templateData = array_merge($templateData, $template->getChildObject()->getData());
            }
            $templateUsedAttributes = $template->getUsedAttributes();
        }

        // ---------------------------------------
        if ($template === null && empty($templateData)) {
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
