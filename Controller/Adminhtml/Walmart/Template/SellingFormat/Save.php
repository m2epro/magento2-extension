<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\SellingFormat;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\SellingFormat\Save
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

        // Add or update model
        // ---------------------------------------
        $sellingFormatTemplate = $this->walmartFactory->getObject('Template\SellingFormat');

        $id && $sellingFormatTemplate->load($id);

        $oldData = [];

        if ($sellingFormatTemplate->getId()) {
            /** @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Walmart_Template_SellingFormat_SnapshotBuilder');
            $snapshotBuilder->setModel($sellingFormatTemplate);
            $oldData = $snapshotBuilder->getSnapshot();
        }

        $this->modelFactory->getObject('Walmart_Template_SellingFormat_Builder')
            ->build($sellingFormatTemplate, $post->toArray());

        $this->updateServices($post, $sellingFormatTemplate->getId());
        $this->updatePromotions($post, $sellingFormatTemplate->getId());

        $snapshotBuilder = $this->modelFactory->getObject('Walmart_Template_SellingFormat_SnapshotBuilder');
        $snapshotBuilder->setModel($sellingFormatTemplate);
        $newData = $snapshotBuilder->getSnapshot();

        $diff = $this->modelFactory->getObject('Walmart_Template_SellingFormat_Diff');
        $diff->setNewSnapshot($newData);
        $diff->setOldSnapshot($oldData);

        $affectedListingsProducts = $this->modelFactory->getObject(
            'Walmart_Template_SellingFormat_AffectedListingsProducts'
        );
        $affectedListingsProducts->setModel($sellingFormatTemplate);

        $changeProcessor = $this->modelFactory->getObject('Walmart_Template_SellingFormat_ChangeProcessor');
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

        $id = $sellingFormatTemplate->getId();
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

    // ---------------------------------------

    private function updateServices($data, $templateId)
    {
        $collection = $this->activeRecordFactory->getObject('Walmart_Template_SellingFormat_ShippingOverride')
                          ->getCollection()
                          ->addFieldToFilter('template_selling_format_id', (int)$templateId);

        foreach ($collection as $item) {
            $item->delete();
        }

        if (empty($data['shipping_override_rule'])) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride\Builder $builder */
        $builder = $this->modelFactory
            ->getObject('Walmart_Template_SellingFormat_ShippingOverride_Builder');

        foreach ($data['shipping_override_rule'] as $serviceData) {
            /** @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride $shippingOverrideInstance */
            $shippingOverrideInstance = $this->activeRecordFactory
                ->getObject('Walmart_Template_SellingFormat_ShippingOverride');
            $builder->setTemplateSellingFormatId($templateId);
            $builder->build($shippingOverrideInstance, $serviceData);
        }
    }

    private function updatePromotions($data, $templateId)
    {
        $collection = $this->activeRecordFactory->getObject('Walmart_Template_SellingFormat_Promotion')
                                                ->getCollection()
                                                ->addFieldToFilter('template_selling_format_id', (int)$templateId);

        foreach ($collection as $item) {
            $item->delete();
        }

        if (empty($data['promotions'])) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion\Builder $builder */
        $builder = $this->modelFactory->getObject('Walmart_Template_SellingFormat_Promotion_Builder');

        foreach ($data['promotions'] as $promotionData) {
            /** @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion $promotionInstance */
            $promotionInstance = $this->activeRecordFactory->getObject('Walmart_Template_SellingFormat_Promotion');
            $builder->setTemplateSellingFormatId($templateId);
            $builder->build($promotionInstance, $promotionData);
        }
    }

    //########################################
}
