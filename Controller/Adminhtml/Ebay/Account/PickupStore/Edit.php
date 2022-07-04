<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore\Edit
 */
class Edit extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $helperDataSession;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $helperDataGlobalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $helperDataSession,
        \Ess\M2ePro\Helper\Data\GlobalData $helperDataGlobalData,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($storeCategoryUpdate, $componentEbayCategoryStore, $ebayFactory, $context);

        $this->helperDataSession = $helperDataSession;
        $this->helperDataGlobalData = $helperDataGlobalData;
    }

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
            $model = $this->activeRecordFactory->getCachedObjectLoaded('Ebay_Account_PickupStore', $id, null, false);
        } else {
            if (!$this->getRequest()->getParam('account_id')) {
                return $this->_redirect('*/ebay_account/index');
            }

            $model = $this->activeRecordFactory->getObject('Ebay_Account_PickupStore');
        }

        if ($id && !$model) {
            $this->getMessageManager()->addErrorMessage($this->__('Store does not exist.'));
            return $this->_redirect('*/*/index');
        }

        $formData = $this->helperDataSession->getValue('pickup_store_form_data', true);

        if (!empty($formData) && is_array($formData)) {
            $model->addData($formData);
        }

        $this->helperDataGlobalData->setValue('temp_data', $model);

        $account = $this->ebayFactory->getObjectLoaded(
            'Account',
            (int)$this->getRequest()->getParam('account_id', $model->getAccountId())
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

        $this->addLeft(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs::class)
        );
        $this->addContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit::class)
        );
        return $this->getResult();
    }

    //########################################
}
