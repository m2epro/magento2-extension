<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Synchronization\Save
 */
class Save extends Template
{
    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
            return;
        }

        $id = $this->getRequest()->getParam('id');

        // Add or update model
        // ---------------------------------------
        $model = $this->amazonFactory->getObject('Template\Synchronization');

        $oldData = [];

        if ($id) {
            $model->load($id);

            /** @var \Ess\M2ePro\Model\Amazon\Template\Synchronization\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_Synchronization_SnapshotBuilder');
            $snapshotBuilder->setModel($model);
            $oldData = $snapshotBuilder->getSnapshot();
        }

        $this->modelFactory->getObject('Amazon_Template_Synchronization_Builder')->build($model, $post->toArray());

        /** @var \Ess\M2ePro\Model\Amazon\Template\Synchronization\SnapshotBuilder $snapshotBuilder */
        $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_Synchronization_SnapshotBuilder');
        $snapshotBuilder->setModel($model);
        $newData = $snapshotBuilder->getSnapshot();

        /** @var \Ess\M2ePro\Model\Amazon\Template\Synchronization\Diff $diff */
        $diff = $this->modelFactory->getObject('Amazon_Template_Synchronization_Diff');
        $diff->setNewSnapshot($newData);
        $diff->setOldSnapshot($oldData);

        /** @var \Ess\M2ePro\Model\Amazon\Template\Synchronization\AffectedListingsProducts $affectedListingsProducts */
        $affectedListingsProducts = $this->modelFactory->getObject(
            'Amazon_Template_Synchronization_AffectedListingsProducts'
        );
        $affectedListingsProducts->setModel($model);

        /** @var \Ess\M2ePro\Model\Amazon\Template\Synchronization\ChangeProcessor $changeProcessor */
        $changeProcessor = $this->modelFactory->getObject('Amazon_Template_Synchronization_ChangeProcessor');
        $changeProcessor->process(
            $diff,
            $affectedListingsProducts->getObjectsData(['id', 'status'])
        );

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true
            ]);
            return $this->getResult();
        }

        $id = $model->getId();
        // ---------------------------------------

        $this->messageManager->addSuccess($this->__('Policy was saved'));
        return $this->_redirect($this->getHelper('Data')->getBackUrl('*/amazon_template/index', [], [
            'edit' => [
                'id' => $id,
                'wizard' => $this->getRequest()->getParam('wizard'),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ],
        ]));
    }
}
