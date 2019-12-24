<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;
use Magento\Backend\App\Action;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Save
 */
class Save extends Template
{
    //########################################

    public function execute()
    {
        $templates = [];
        $templateNicks = $this->templateManager->getAllTemplates();

        // ---------------------------------------
        foreach ($templateNicks as $nick) {
            if ($this->isSaveAllowed($nick)) {
                $template = $this->saveTemplate($nick);

                if ($template) {
                    $templates[] = [
                        'nick' => $nick,
                        'id' => (int)$template->getId(),
                        'title' => $this->getHelper('Data')->escapeJs(
                            $this->getHelper('Data')->escapeHtml($template->getTitle())
                        )
                    ];
                }
            }
        }
        // ---------------------------------------

        // ---------------------------------------
        if ($this->isAjax()) {
            $this->setJsonContent($templates);
            return $this->getResult();
        }
        // ---------------------------------------

        if (count($templates) == 0) {
            $this->messageManager->addError($this->__('Policy was not saved.'));
            return $this->_redirect('*/*/index');
        }

        $template = array_shift($templates);

        $this->messageManager->addSuccess($this->__('Policy was successfully saved.'));

        $extendedRoutersParams = [
            'edit' => [
                'id' => $template['id'],
                'nick' => $template['nick'],
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ]
        ];

        if ($this->getHelper('Module\Wizard')->isActive(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK)) {
            $extendedRoutersParams['edit']['wizard'] = true;
        }

        return $this->_redirect($this->getHelper('Data')->getBackUrl(
            'list',
            [],
            $extendedRoutersParams
        ));
    }

    //########################################

    protected function isSaveAllowed($templateNick)
    {
        if (!$this->getRequest()->isPost()) {
            return false;
        }

        $requestedTemplateNick = $this->getRequest()->getPost('nick');

        if ($requestedTemplateNick === null) {
            return true;
        }

        if ($requestedTemplateNick == $templateNick) {
            return true;
        }

        return false;
    }

    protected function saveTemplate($nick)
    {
        $data = $this->getRequest()->getPost($nick);

        if ($data === null) {
            return null;
        }

        $templateManager = $this->templateManager->setTemplate($nick);
        $templateModel = $templateManager->getTemplateModel();

        if (empty($data['id'])) {
            $oldData = [];
        } else {
            $templateModel->load($data['id']);
            $templateManager->isHorizontalTemplate() && $templateModel = $templateModel->getChildObject();

            $oldData = $templateModel->getDataSnapshot();
        }

        $template = $templateManager->getTemplateBuilder()->build($data);
        $newData = $template->getDataSnapshot();

        if ($templateManager->isHorizontalTemplate()) {
            $template->getChildObject()->setSynchStatusNeed($newData, $oldData);
        } else {
            $template->setSynchStatusNeed($newData, $oldData);
        }

        return $template;
    }

    //########################################
}
