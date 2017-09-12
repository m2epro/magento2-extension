<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Settings\Motors;

class ClearAddedMotorsData extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Settings
{
    //########################################

    public function execute()
    {
        $helper = $this->getHelper('Component\Ebay\Motors');
        $motorsType = $this->getRequest()->getPost('motors_type');

        if (!$motorsType) {
            $this->getMessageManager()->addError($this->__('Some of required fields are not filled up.'));
            return $this->_redirect('*/ebay_settings/index');
        }

        $connWrite = $this->resourceConnection->getConnection();
        $conditions = array('is_custom = ?' => 1);
        if ($helper->isTypeBasedOnEpids($motorsType)) {
            $conditions['scope = ?'] = $helper->getEpidsScopeByType($motorsType);
        }

        $connWrite->delete(
            $helper->getDictionaryTable($motorsType), $conditions
        );

        $this->getMessageManager()->addSuccess($this->__('Added compatibility data has been cleared.'));
        return $this->_redirect('*/ebay_settings/index');
    }

    //########################################
}