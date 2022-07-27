<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

class Save extends Template
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->dataHelper = $dataHelper;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
            return;
        }

        $id = $this->getRequest()->getParam('id');

        $model = $this->walmartFactory->getObject('Template\Synchronization');

        $oldData = [];

        $id && $model->load($id);

        if ($model->getId()) {
            /** @var \Ess\M2ePro\Model\Walmart\Template\Synchronization\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Walmart_Template_Synchronization_SnapshotBuilder');
            $snapshotBuilder->setModel($model);
            $oldData = $snapshotBuilder->getSnapshot();
        }

        $this->modelFactory->getObject('Walmart_Template_Synchronization_Builder')->build($model, $post->toArray());

        $snapshotBuilder = $this->modelFactory->getObject('Walmart_Template_Synchronization_SnapshotBuilder');
        $snapshotBuilder->setModel($model);
        $newData = $snapshotBuilder->getSnapshot();

        $diff = $this->modelFactory->getObject('Walmart_Template_Synchronization_Diff');
        $diff->setNewSnapshot($newData);
        $diff->setOldSnapshot($oldData);

        $affectedListingsProducts = $this->modelFactory->getObject(
            'Walmart_Template_Synchronization_AffectedListingsProducts'
        );
        $affectedListingsProducts->setModel($model);

        $changeProcessor = $this->modelFactory->getObject('Walmart_Template_Synchronization_ChangeProcessor');
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
        return $this->_redirect($this->dataHelper->getBackUrl('*/walmart_template/index', [], [
            'edit' => [
                'id' => $id,
                'wizard' => $this->getRequest()->getParam('wizard'),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ],
        ]));
    }

    private function getRuleData($rulePrefix)
    {
        $postData = $this->getRequest()->getPost()->toArray();

        if (empty($postData['rule'][$rulePrefix])) {
            return null;
        }

        $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
            ['prefix' => $rulePrefix]
        );

        return $ruleModel->getSerializedFromPost($postData);
    }
}
