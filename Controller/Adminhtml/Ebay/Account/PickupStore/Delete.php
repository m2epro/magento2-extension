<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore;

class Delete extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    //########################################

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id', 0);

        if (!$id) {
            $this->getMessageManager()->addErrorMessage(
                $this->__('Store does not exist.')
            );
            return $this->_redirect('*/*/index');
        }

        /** @var \Ess\M2ePro\Model\Ebay\Account\PickupStore $model */
        $model = $this->activeRecordFactory->getObjectLoaded('Ebay\Account\PickupStore', $id);
        $params = ['account_id' => $model->getAccountId()];

        if (!$model->getId()) {
            $this->getMessageManager()->addErrorMessage($this->__('Store does not exists.'));
            return $this->_redirect('*/ebay_account_pickupStore/index', $params);
        }

        if ($model->isLocked()) {
            $this->getMessageManager()->addErrorMessage($this->__('Store used in Listing.'));
            return $this->_redirect('*/ebay_account_pickupStore/index', $params);
        }

        if ($this->getHelper('Component\Ebay\PickupStore')
            ->deletePickupStore($model->getLocationId(), $model->getAccountId())) {

            $model->delete();

            $this->getMessageManager()->addSuccessMessage($this->__('Store was successfully deleted.'));
        }

        return $this->_redirect('*/ebay_account_pickupStore/index', $params);
    }

    //########################################
}