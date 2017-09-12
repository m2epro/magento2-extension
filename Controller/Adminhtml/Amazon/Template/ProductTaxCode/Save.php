<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductTaxCode;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

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
        $data = array();

        $keys = array(
            'title',

            'product_tax_code_mode',
            'product_tax_code_value',
            'product_tax_code_attribute',
        );

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode $model */
        $model = $this->activeRecordFactory->getObjectLoaded('Amazon\Template\ProductTaxCode', $id, NULL, false);

        if (is_null($model)) {
            $model = $this->activeRecordFactory->getObject('Amazon\Template\ProductTaxCode');
        }

        $oldData = (!empty($id)) ? $model->getDataSnapshot() : array();

        $model->addData($data)->save();

        $newData = $model->getDataSnapshot();

        $model->setSynchStatusNeed($newData,$oldData);

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true
            ]);
            return $this->getResult();
        }

        $this->getMessageManager()->addSuccess($this->__('Policy was successfully saved'));

        return $this->_redirect($this->getHelper('Data')->getBackUrl('*/amazon_template/index', array(), array(
            'edit' => array('id' => $model->getId()),
        )));
    }
}