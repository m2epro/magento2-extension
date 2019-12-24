<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ShippingOverride;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ShippingOverride\Save
 */
class Save extends Template
{
    public function execute()
    {
        if (!$post = $this->getRequest()->getPost()) {
            return $this->_redirect('*/amazon_template/index');
        }

        $id = $this->getRequest()->getParam('id');

        // Base prepare
        // ---------------------------------------
        $data = [];

        $keys = [
            'title',
            'marketplace_id'
        ];

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        /** @var \Ess\M2ePro\Model\Amazon\Template\ShippingOverride $model */
        $model = $this->activeRecordFactory->getObjectLoaded('Amazon_Template_ShippingOverride', $id, null, false);

        if ($model === null) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\ShippingOverride $model */
            $model = $this->activeRecordFactory->getObject('Amazon_Template_ShippingOverride');
        }

        $oldData = (!empty($id)) ? $model->getDataSnapshot() : [];

        $model->addData($data)->save();
        $this->setServices($post['shipping_override_rule'], $model->getId());

        $newData = $model->getDataSnapshot();

        $model->setSynchStatusNeed($newData, $oldData);

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true
            ]);
            return $this->getResult();
        }

        $this->getMessageManager()->addSuccess($this->__('Policy was successfully saved'));
        $this->_redirect($this->getHelper('Data')->getBackUrl('*/amazon_template/index', [], [
            'edit' => [
                'id' => $model->getId(),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ]
        ]));
    }

    private function setServices($data, $templateId)
    {
        $newServices = [];
        foreach ($data['service'] as $key => $service) {
            $newService = [];

            $newService['template_shipping_override_id'] = $templateId;
            $newService['service'] = $data['service'][$key];
            $newService['location'] = $data['location'][$key];
            $newService['option'] = $data['option'][$key];
            $newService['type'] = $data['type'][$key];
            $newService['cost_mode'] = '';
            $newService['cost_value'] = '';

            if (!empty($data['cost_mode'][$key])) {
                $newService['cost_mode'] = $data['cost_mode'][$key];
            }
            if (isset($data['cost_value'][$key]) && $data['cost_value'][$key] != '') {
                $newService['cost_value'] = $data['cost_value'][$key];
            }

            $newServices[] = $newService;
        }

        $connection = $this->resourceConnection->getConnection();

        $atsosTable = $this->activeRecordFactory->getObject('Amazon_Template_ShippingOverride_Service')
            ->getResource()->getMainTable();

        $connection->delete(
            $atsosTable,
            ['template_shipping_override_id = ?' => (int)$templateId]
        );

        if (empty($newServices)) {
            return;
        }

        $connection->insertMultiple(
            $atsosTable,
            $newServices
        );
    }
}
