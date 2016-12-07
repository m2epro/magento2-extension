<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore;

class Edit extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    //########################################

    protected function getLayoutType()
    {
        return self::LAYOUT_TWO_COLUMNS;
    }

    //########################################

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id', 0);

        if ($id) {
            $model = $this->activeRecordFactory->getCachedObjectLoaded('Ebay\Account\PickupStore', $id, NULL, false);
        } else {

            if (!$this->getRequest()->getParam('account_id')) {
                return $this->_redirect('*/ebay_account/index');
            }

            $model = $this->activeRecordFactory->getObject('Ebay\Account\PickupStore');
        }

        if ($id && !$model) {
            $this->getMessageManager()->addErrorMessage($this->__('Store does not exist.'));
            return $this->_redirect('*/*/index');
        }

        $formData = $this->getHelper('Data\Session')->getValue('pickup_store_form_data', true);

        if (!empty($formData) && is_array($formData)) {
            $model->addData($formData);
        }

        $this->getHelper('Data\GlobalData')->setValue('temp_data', $model);

        $account = $this->ebayFactory->getObjectLoaded(
            'Account', (int)$this->getRequest()->getParam('account_id', $model->getAccountId())
        );

        if ($model->getId()) {

            $this->getResultPage()->getConfig()->getTitle()->prepend(
                $this->__('Edit Store "%s%" for "%x%', $model->getName(), $account->getTitle())
            );
        } else {

            $this->getResultPage()->getConfig()->getTitle()->prepend(
                $this->__('Add Store for "%s%', $account->getTitle())
            );
        }

        $this->addLeft($this->createBlock('Ebay\Account\PickupStore\Edit\Tabs'));
        $this->addContent($this->createBlock('Ebay\Account\PickupStore\Edit'));
        return $this->getResult();
    }

    //########################################
}