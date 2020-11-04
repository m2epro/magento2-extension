<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

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

        $this->messageManager->addSuccess($this->__('Policy was saved.'));

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

        /** @var \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager */
        $templateManager = $this->templateManager->setTemplate($nick);
        $templateModel = $templateManager->getTemplateModel();

        if (empty($data['id'])) {
            $oldData = [];
        } else {
            $templateModel->load($data['id']);

            /** @var \Ess\M2ePro\Model\ActiveRecord\SnapshotBuilder $snapshotBuilder */
            if ($templateManager->isHorizontalTemplate()) {
                $snapshotBuilder = $this->modelFactory->getObject(
                    'Ebay_'.$templateManager->getTemplateModelName().'_SnapshotBuilder'
                );
            } else {
                $snapshotBuilder = $this->modelFactory->getObject(
                    $templateManager->getTemplateModelName().'_SnapshotBuilder'
                );
            }

            $snapshotBuilder->setModel(
                $templateManager->isHorizontalTemplate() ? $templateModel->getChildObject() : $templateModel
            );

            $oldData = $snapshotBuilder->getSnapshot();
        }

        $template = $templateManager->getTemplateBuilder()->build($templateModel, $data);

        /** @var \Ess\M2ePro\Model\ActiveRecord\SnapshotBuilder $snapshotBuilder */
        if ($templateManager->isHorizontalTemplate()) {
            $snapshotBuilder = $this->modelFactory->getObject(
                'Ebay_'.$templateManager->getTemplateModelName().'_SnapshotBuilder'
            );
        } else {
            $snapshotBuilder = $this->modelFactory->getObject(
                $templateManager->getTemplateModelName().'_SnapshotBuilder'
            );
        }

        $snapshotBuilder->setModel($template);

        $newData = $snapshotBuilder->getSnapshot();

        /** @var \Ess\M2ePro\Model\ActiveRecord\Diff $diff */
        if ($templateManager->isHorizontalTemplate()) {
            $diff = $this->modelFactory->getObject('Ebay_'.$templateManager->getTemplateModelName().'_Diff');
        } else {
            $diff = $this->modelFactory->getObject($templateManager->getTemplateModelName().'_Diff');
        }

        $diff->setNewSnapshot($newData);
        $diff->setOldSnapshot($oldData);

        /** @var \Ess\M2ePro\Model\Template\AffectedListingsProductsAbstract $affectedListingsProducts */
        if ($templateManager->isHorizontalTemplate()) {
            $affectedListingsProducts = $this->modelFactory->getObject(
                'Ebay_'.$templateManager->getTemplateModelName().'_AffectedListingsProducts'
            );
        } else {
            $affectedListingsProducts = $this->modelFactory->getObject(
                $templateManager->getTemplateModelName().'_AffectedListingsProducts'
            );
        }

        $affectedListingsProducts->setModel($template);

        /** @var \Ess\M2ePro\Model\Template\ChangeProcessorAbstract $changeProcessor */
        if ($templateManager->isHorizontalTemplate()) {
            $changeProcessor = $this->modelFactory->getObject(
                'Ebay_'.$templateManager->getTemplateModelName().'_ChangeProcessor'
            );
        } else {
            $changeProcessor = $this->modelFactory->getObject(
                $templateManager->getTemplateModelName().'_ChangeProcessor'
            );
        }

        $changeProcessor->process($diff, $affectedListingsProducts->getObjectsData(['id', 'status']));

        return $template;
    }

    //########################################
}
