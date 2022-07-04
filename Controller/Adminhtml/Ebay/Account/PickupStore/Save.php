<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;

    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $helperDataSession;

    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\PickupStore */
    private $componentEbayPickupStore;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Helper\Data\Session $helperDataSession,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Component\Ebay\PickupStore $componentEbayPickupStore,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($storeCategoryUpdate, $componentEbayCategoryStore, $ebayFactory, $context);

        $this->helperException = $helperException;
        $this->helperDataSession = $helperDataSession;
        $this->helperData = $helperData;
        $this->componentEbayPickupStore = $componentEbayPickupStore;
    }

    public function execute()
    {
        if (!$post = $this->getRequest()->getPostValue()) {
            return $this->_redirect(
                '*/*/index',
                ['account_id' => $this->getRequest()->getParam('account_id')]
            );
        }

        $id = (int)$this->getRequest()->getParam('id', 0);

        // Base prepare
        // ---------------------------------------
        $data = [];
        // ---------------------------------------

        // tab: general
        // ---------------------------------------
        $keys = [
            'name',
            'location_id',
            'account_id',
            'marketplace_id',
            'phone',
            'url',
            'pickup_instruction'
        ];

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        // ---------------------------------------

        // tab: location
        // ---------------------------------------
        $keys = [
            'country',
            'region',
            'city',
            'postal_code',
            'address_1',
            'address_2',
            'latitude',
            'longitude',
            'utc_offset'
        ];

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        // ---------------------------------------

        // tab: businessHours
        // ---------------------------------------
        $data['business_hours'] = $this->helperData->jsonEncode($post['business_hours']);
        $data['special_hours'] = '';

        if (isset($post['special_hours'])) {
            $data['special_hours'] = $this->helperData->jsonEncode($post['special_hours']);
        }
        // ---------------------------------------

        // tab: stockSettings
        // ---------------------------------------
        $keys = [
            'qty_mode',
            'qty_custom_value',
            'qty_custom_attribute',
            'qty_percentage',
            'qty_modification_mode',
            'qty_min_posted_value',
            'qty_max_posted_value'
        ];

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        if (isset($post['default_mode']) && $post['default_mode'] == 0) {
            $data['qty_mode'] = \Ess\M2ePro\Model\Ebay\Account\PickupStore::QTY_MODE_SELLING_FORMAT_TEMPLATE;
        }
        // ---------------------------------------

        // creating of pickup store
        // ---------------------------------------
        if (!$this->componentEbayPickupStore->validateRequiredFields($data)) {
            $this->helperDataSession->setValue('pickup_store_form_data', $data);

            $this->getMessageManager()->addErrorMessage(
                $this->__('Validation error. You must fill all required fields.'),
                self::GLOBAL_MESSAGES_GROUP
            );

            return $id ? $this->_redirect('*/*/edit', ['id' => $id])
                       : $this->_redirect('*/*/new', ['account_id' => $this->getRequest()->getParam('account_id')]);
        }

        try {
            $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'store',
                'add',
                'entity',
                $this->componentEbayPickupStore->prepareRequestData($data),
                null,
                null,
                $this->getRequest()->getParam('account_id')
            );

            $dispatcherObject->process($connectorObj);
        } catch (\Exception $exception) {
            $this->helperException->process($exception);
            $this->helperDataSession->setValue('pickup_store_form_data', $data);

            $this->getMessageManager()->addErrorMessage($this->__(
                'The New Store has not been created. <br/>Reason: %error_message%',
                $exception->getMessage()
            ));

            return $id ? $this->_redirect('*/*/edit', ['id' => $id])
                       : $this->_redirect('*/*/new', ['account_id' => $this->getRequest()->getParam('account_id')]);
        }
        // ---------------------------------------

        $model = $this->activeRecordFactory->getObject('Ebay_Account_PickupStore');
        if ($id) {
            $model->load($id);
            $model->addData($data);
        } else {
            $model->setData($data);
        }
        $model->save();

        $this->getMessageManager()->addSuccessMessage(
            $this->__('Store was saved.'),
            self::GLOBAL_MESSAGES_GROUP
        );

        return $this->_redirect($this->helperData->getBackUrl(
            'list',
            [],
            [
                'list' => ['account_id' => $model->getAccountId()],
                'edit' => ['id' => $model->getId()]
            ]
        ));
    }

    //########################################
}
