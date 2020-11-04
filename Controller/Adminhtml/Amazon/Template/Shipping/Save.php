<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Shipping;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Shipping\Save
 */
class Save extends Template
{
    public function execute()
    {
        if (!$post = $this->getRequest()->getPost()) {
            return $this->_redirect('*/amazon_template/index');
        }

        $id = $this->getRequest()->getParam('id');

        /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping $model */
        $model = $this->activeRecordFactory->getObjectLoaded('Amazon_Template_Shipping', $id, null, false);

        if ($model === null) {
            $model = $this->activeRecordFactory->getObject('Amazon_Template_Shipping');
        }

        $oldData = [];

        if (!empty($id)) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_Shipping_SnapshotBuilder');
            $snapshotBuilder->setModel($model);
            $oldData = $snapshotBuilder->getSnapshot();
        }

        $this->modelFactory->getObject('Amazon_Template_Shipping_Builder')->build($model, $post->toArray());

        $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_Shipping_SnapshotBuilder');
        $snapshotBuilder->setModel($model);
        $newData = $snapshotBuilder->getSnapshot();

        /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping\Diff $diff */
        $diff = $this->modelFactory->getObject('Amazon_Template_Shipping_Diff');
        $diff->setNewSnapshot($newData);
        $diff->setOldSnapshot($oldData);

        /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping\AffectedListingsProducts $affectedListingsProducts */
        $affectedListingsProducts = $this->modelFactory->getObject('Amazon_Template_Shipping_AffectedListingsProducts');
        $affectedListingsProducts->setModel($model);

        /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping\ChangeProcessor $changeProcessor */
        $changeProcessor = $this->modelFactory->getObject('Amazon_Template_Shipping_ChangeProcessor');
        $changeProcessor->process(
            $diff,
            $affectedListingsProducts->getObjectsData(['id', 'status'], ['only_physical_units' => true])
        );

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true
            ]);
            return $this->getResult();
        }

        $this->getMessageManager()->addSuccess($this->__('Policy was saved'));

        return $this->_redirect($this->getHelper('Data')->getBackUrl('*/amazon_template/index', [], [
            'edit' => [
                'id' => $model->getId(),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ]
        ]));
    }
}
