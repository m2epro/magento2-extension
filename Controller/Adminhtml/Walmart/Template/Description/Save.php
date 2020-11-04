<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Description\Save
 */
class Save extends Template
{
    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
            return;
        }

        $id = $this->getRequest()->getParam('id');

        // ---------------------------------------

        // Add or update model
        // ---------------------------------------
        $descriptionTemplate = $this->walmartFactory->getObject('Template\Description');

        $id && $descriptionTemplate->load($id);

        $oldData = [];
        if ($descriptionTemplate->getId()) {

            /** @var \Ess\M2ePro\Model\Walmart\Template\Description\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Walmart_Template_Description_SnapshotBuilder');
            $snapshotBuilder->setModel($descriptionTemplate);

            $oldData = $snapshotBuilder->getSnapshot();
        }

        $this->modelFactory->getObject('Walmart_Template_Description_Builder')
            ->build($descriptionTemplate, $post->toArray());

        // Is Need Synchronize
        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\Walmart\Template\Description\SnapshotBuilder $snapshotBuilder */
        $snapshotBuilder = $this->modelFactory->getObject('Walmart_Template_Description_SnapshotBuilder');
        $snapshotBuilder->setModel($descriptionTemplate);
        $newData = $snapshotBuilder->getSnapshot();

        /** @var \Ess\M2ePro\Model\Walmart\Template\Description\Diff $diff */
        $diff = $this->modelFactory->getObject('Walmart_Template_Description_Diff');
        $diff->setNewSnapshot($newData);
        $diff->setOldSnapshot($oldData);

        /** @var \Ess\M2ePro\Model\Walmart\Template\Description\AffectedListingsProducts $affectedListingsProducts */
        $affectedListingsProducts = $this->modelFactory->getObject(
            'Walmart_Template_Description_AffectedListingsProducts'
        );
        $affectedListingsProducts->setModel($descriptionTemplate);

        /** @var \Ess\M2ePro\Model\Walmart\Template\Description\ChangeProcessor $changeProcessor */
        $changeProcessor = $this->modelFactory->getObject('Walmart_Template_Description_ChangeProcessor');
        $changeProcessor->process(
            $diff,
            $affectedListingsProducts->getObjectsData(['id', 'status'])
        );
        // ---------------------------------------

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true
            ]);
            return $this->getResult();
        }

        $id = $descriptionTemplate->getId();
        // ---------------------------------------

        $this->messageManager->addSuccess($this->__('Policy was saved'));
        return $this->_redirect($this->getHelper('Data')->getBackUrl('*/walmart_template/index', [], [
            'edit' => [
                'id' => $id,
                'wizard' => $this->getRequest()->getParam('wizard'),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ],
        ]));
    }

    //########################################
}
