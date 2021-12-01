<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\SaveTemplateCategorySpecifics
 */
class SaveTemplateCategorySpecifics extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{
    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPost()->toArray();

        if (empty($post['template_id'])) {
            $this->getMessageManager()->addError($this->__('Template not found.'));
            return $this->_redirect('*/*/index');
        }

        $model = $this->activeRecordFactory->getObjectLoaded('Ebay_Template_Category', (int)$post['template_id']);

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\SnapshotBuilder $snapshotBuilder */
        $snapshotBuilder = $this->modelFactory->getObject('Ebay_Template_Category_SnapshotBuilder');
        $snapshotBuilder->setModel($model);
        $oldSnapshot = $snapshotBuilder->getSnapshot();

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Builder $builder */
        $builder = $this->modelFactory->getObject('Ebay_Template_Category_Builder');
        $builder->build($model, $post);

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\SnapshotBuilder $snapshotBuilder */
        $snapshotBuilder = $this->modelFactory->getObject('Ebay_Template_Category_SnapshotBuilder');
        $snapshotBuilder->setModel($model);
        $newSnapshot = $snapshotBuilder->getSnapshot();

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Diff $diff */
        $diff = $this->modelFactory->getObject('Ebay_Template_Category_Diff');
        $diff->setNewSnapshot($newSnapshot);
        $diff->setOldSnapshot($oldSnapshot);

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\AffectedListingsProducts $affectedListingsProducts */
        $affectedListingsProducts = $this->modelFactory->getObject('Ebay_Template_Category_AffectedListingsProducts');
        $affectedListingsProducts->setModel($model);

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\ChangeProcessor $changeProcessor */
        $changeProcessor = $this->modelFactory->getObject('Ebay_Template_Category_ChangeProcessor');
        $changeProcessor->process($diff, $affectedListingsProducts->getObjectsData(['id', 'status']));

        $this->messageManager->addSuccess($this->__('Category data was saved.'));

        if ($this->getRequest()->getParam('back') === 'edit') {
            return $this->_redirect('*/*/view', ['template_id' => $post['template_id']]);
        }

        return $this->_redirect('*/*/index');
    }

    //########################################
}
