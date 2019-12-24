<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Settings\Motors;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Settings\Motors\ClearAddedMotorsData
 */
class ClearAddedMotorsData extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Settings
{
    //########################################

    public function execute()
    {
        $helper = $this->getHelper('Component_Ebay_Motors');
        $motorsType = $this->getRequest()->getPost('motors_type');

        if (!$motorsType) {
            $this->getMessageManager()->addError($this->__('Some of required fields are not filled up.'));
            return $this->_redirect('*/ebay_settings/index');
        }

        $connWrite = $this->resourceConnection->getConnection();
        $conditions = ['is_custom = ?' => 1];
        if ($helper->isTypeBasedOnEpids($motorsType)) {
            $conditions['scope = ?'] = $helper->getEpidsScopeByType($motorsType);
        }

        $connWrite->delete(
            $helper->getDictionaryTable($motorsType),
            $conditions
        );

        $this->getMessageManager()->addSuccess($this->__('Added compatibility data has been cleared.'));
        return $this->_redirect('*/ebay_settings/index');
    }

    //########################################
}
