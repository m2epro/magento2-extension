<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Save
 */
class Save extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_listings_m2epro');
    }

    //########################################

    public function execute()
    {
        if (!$post = $this->getRequest()->getPost()) {
            $this->_redirect('*/ebay_listing/index');
        }

        $id = $this->getRequest()->getParam('id');
        $model = $this->ebayFactory->getObjectLoaded('Listing', $id, null, false);

        if ($model === null && $id) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));

            return $this->_redirect('*/ebay_listing/index');
        }

        $snapshotBuilder = $this->modelFactory->getObject('Ebay_Listing_SnapshotBuilder');
        $snapshotBuilder->setModel($model);

        $oldData = $snapshotBuilder->getSnapshot();

        $data = [];
        $keys = [
            'template_payment_id',
            'template_shipping_id',
            'template_return_policy_id',
            'template_selling_format_id',
            'template_description_id',
            'template_synchronization_id',
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        $model->addData($data);
        $model->getChildObject()->addData($data);
        $model->save();

        $snapshotBuilder = $this->modelFactory->getObject('Ebay_Listing_SnapshotBuilder');
        $snapshotBuilder->setModel($model);

        $newData = $snapshotBuilder->getSnapshot();

        /** @var \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager */
        $templateManager = $this->modelFactory->getObject('Ebay_Template_Manager');

        $affectedListingsProducts = $this->modelFactory->getObject('Ebay_Listing_AffectedListingsProducts');
        $affectedListingsProducts->setModel($model);

        foreach ($templateManager->getAllTemplates() as $template) {
            $templateManager->setTemplate($template);
            $templateModelName = $templateManager->getTemplateModelName();

            /** @var \Ess\M2ePro\Model\ActiveRecord\SnapshotBuilder $snapshotBuilder */
            /** @var \Ess\M2ePro\Model\ActiveRecord\Diff $diff */
            /** @var \Ess\M2ePro\Model\Template\ChangeProcessorAbstract $changeProcessor */
            if ($templateManager->isHorizontalTemplate()) {
                $newTemplate = $this->ebayFactory->getCachedObjectLoaded(
                    $templateModelName,
                    $newData[$templateManager->getTemplateIdColumnName()]
                )
                    ->getChildObject();
                $oldTemplate = $this->ebayFactory->getCachedObjectLoaded(
                    $templateModelName,
                    $oldData[$templateManager->getTemplateIdColumnName()]
                )
                    ->getChildObject();
                $snapshotBuilder = $this->modelFactory->getObject(
                    'Ebay_' . $templateModelName . '_SnapshotBuilder'
                );
                $diff = $this->modelFactory->getObject('Ebay_' . $templateModelName . '_Diff');
                $changeProcessor = $this->modelFactory->getObject(
                    'Ebay_' . $templateModelName . '_ChangeProcessor'
                );
            } else {
                $newTemplate = $this->activeRecordFactory->getCachedObjectLoaded(
                    $templateModelName,
                    $newData[$templateManager->getTemplateIdColumnName()]
                );
                $oldTemplate = $this->activeRecordFactory->getCachedObjectLoaded(
                    $templateModelName,
                    $oldData[$templateManager->getTemplateIdColumnName()]
                );
                $snapshotBuilder = $this->modelFactory->getObject(
                    $templateModelName . '_SnapshotBuilder'
                );
                $diff = $this->modelFactory->getObject($templateModelName . '_Diff');
                $changeProcessor = $this->modelFactory->getObject(
                    $templateModelName . '_ChangeProcessor'
                );
            }

            $snapshotBuilder->setModel($newTemplate);
            $newTemplateData = $snapshotBuilder->getSnapshot();

            $snapshotBuilder->setModel($oldTemplate);
            $oldTemplateData = $snapshotBuilder->getSnapshot();

            $diff->setNewSnapshot($newTemplateData);
            $diff->setOldSnapshot($oldTemplateData);

            $changeProcessor->process(
                $diff,
                $affectedListingsProducts->getObjectsData(['id', 'status'], ['template' => $template])
            );
        }

        $this->getMessageManager()->addSuccess($this->__('The Listing was saved.'));

        return $this->_redirect($this->getHelper('Data')->getBackUrl('list', [], ['edit' => ['id' => $id]]));
    }

    //########################################
}
