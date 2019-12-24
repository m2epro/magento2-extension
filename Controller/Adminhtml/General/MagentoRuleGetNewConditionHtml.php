<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\General\MagentoRuleGetNewConditionHtml
 */
class MagentoRuleGetNewConditionHtml extends General
{
    //########################################

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $prefix = $this->getRequest()->getParam('prefix');
        $storeId = $this->getRequest()->getParam('store', 0);

        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        $ruleModelPrefix = '';
        $attributeCode = !empty($typeArr[1]) ? $typeArr[1] : '';
        if (count($typeArr) == 3) {
            $ruleModelPrefix = ucfirst($typeArr[1]) . '\\';
            $attributeCode = !empty($typeArr[2]) ? $typeArr[2] : '';
        }

        $model = $this->modelFactory->getObject($type)
            ->setId($id)
            ->setType($type)
            ->setRule($this->activeRecordFactory->getObject($ruleModelPrefix.'Magento\Product\Rule'))
            ->setPrefix($prefix);

        if ($type == $ruleModelPrefix.'Magento\Product\Rule\Condition\Combine') {
            $model->setData($prefix, []);
        }

        if (!empty($attributeCode)) {
            $model->setAttribute($attributeCode);
        }

        if ($model instanceof \Magento\Rule\Model\Condition\ConditionInterface) {
            $model->setJsFormObject($prefix);
            $model->setStoreId($storeId);
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->setAjaxContent($html);
        return $this->getResult();
    }

    //########################################
}
