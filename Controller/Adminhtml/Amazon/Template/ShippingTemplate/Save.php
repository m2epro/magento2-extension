<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ShippingTemplate;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ShippingTemplate\Save
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
            'template_name_mode',
            'template_name_value',
            'template_name_attribute',
        ];

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        /** @var \Ess\M2ePro\Model\Amazon\Template\ShippingTemplate $model */
        $model = $this->activeRecordFactory->getObjectLoaded('Amazon_Template_ShippingTemplate', $id, null, false);

        if ($model === null) {
            $model = $this->activeRecordFactory->getObject('Amazon_Template_ShippingTemplate');
        }

        $oldData = (!empty($id)) ? $model->getDataSnapshot() : [];

        $model->addData($data)->save();

        $newData = $model->getDataSnapshot();

        $model->setSynchStatusNeed($newData, $oldData);

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true
            ]);
            return $this->getResult();
        }

        $this->getMessageManager()->addSuccess($this->__('Policy was successfully saved'));

        return $this->_redirect($this->getHelper('Data')->getBackUrl('*/amazon_template/index', [], [
            'edit' => [
                'id' => $model->getId(),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ]
        ]));
    }
}
