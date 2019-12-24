<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;
use Ess\M2ePro\Model\Ebay\Account as AccountModel;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay\AfterToken
 */
class AfterToken extends InstallationEbay
{
    public function execute()
    {
        $tokenSessionId = $this->getHelper('Data\Session')->getValue('token_session_id', true);

        if (!$tokenSessionId) {
            $this->messageManager->addError($this->__('Token is not defined'));
            return $this->_redirect('*/*/installation');
        }

        $accountMode = $this->getRequest()->getParam('mode');

        $requestParams = [
            'mode' => $accountMode,
            'token_session' => $tokenSessionId
        ];

        $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector(
            'account',
            'add',
            'entity',
            $requestParams,
            null,
            null,
            null
        );

        $dispatcherObject->process($connectorObj);
        $response = array_filter($connectorObj->getResponseData());

        if (empty($response)) {
            $this->messageManager->addError($this->__('Account Add Entity failed.'));
            return $this->_redirect('*/*/installation');
        }

        if ($accountMode == 'sandbox') {
            $accountMode = AccountModel::MODE_SANDBOX;
        } else {
            $accountMode = AccountModel::MODE_PRODUCTION;
        }

        $data = array_merge(
            [
                'title' => $response['info']['UserID'],
                'user_id' => $response['info']['UserID'],
                'mode' => $accountMode,
                'info' => $this->getHelper('Data')->jsonEncode($response['info']),
                'server_hash' => $response['hash'],
                'token_session' => $tokenSessionId,
                'token_expired_date' => $response['token_expired_date']
            ],
            $this->getEbayAccountDefaultSettings()
        );

        $accountModel = $this->ebayFactory->getObject('Account')->setData($data)->save();
        $accountModel->getChildObject()->updateEbayStoreInfo();

        $this->setStep($this->getNextStep());

        return $this->_redirect('*/*/installation');
    }

    /**
     * @return array
     */
    private function getEbayAccountDefaultSettings()
    {
        return [

            'marketplaces_data' => $this->getHelper('Data')->jsonEncode([]),

            'feedbacks_receive' => AccountModel::FEEDBACKS_RECEIVE_NO,
            'feedbacks_auto_response' => AccountModel::FEEDBACKS_AUTO_RESPONSE_NONE,
            'feedbacks_auto_response_only_positive' => AccountModel::FEEDBACKS_AUTO_RESPONSE_ONLY_POSITIVE_NO,

            'other_listings_synchronization' => AccountModel::OTHER_LISTINGS_SYNCHRONIZATION_NO,
            'other_listings_mapping_mode' => AccountModel::OTHER_LISTINGS_MAPPING_MODE_NO,
            'other_listings_mapping_settings' => $this->getHelper('Data')->jsonEncode([]),

            'magento_orders_settings' => $this->getHelper('Data')->jsonEncode([
                'listing' => [
                    'mode' => AccountModel::MAGENTO_ORDERS_LISTINGS_MODE_YES,
                    'store_mode' => AccountModel::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT,
                    'store_id' => null
                ],
                'listing_other' => [
                    'mode' => AccountModel::MAGENTO_ORDERS_LISTINGS_OTHER_MODE_YES,
                    'product_mode' => AccountModel::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IMPORT,
                    'product_tax_class_id' => \Ess\M2ePro\Model\Magento\Product::TAX_CLASS_ID_NONE,
                    'store_id' => $this->getHelper('Magento\Store')->getDefaultStoreId(),
                ],
                'customer' => [
                    'mode' => AccountModel::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST,
                    'id' => null,
                    'website_id' => null,
                    'group_id' => null,
                    'notifications' => [
                        'invoice_created' => false,
                        'order_created' => false
                    ]
                ],
                'creation' => [
                    'mode' => AccountModel::MAGENTO_ORDERS_CREATE_CHECKOUT_AND_PAID,
                    'reservation_days' => 0
                ],
                'tax' => [
                    'mode' => AccountModel::MAGENTO_ORDERS_TAX_MODE_MIXED
                ],
                'status_mapping' => [
                    'mode' => AccountModel::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT,
                    'new' => AccountModel::MAGENTO_ORDERS_STATUS_MAPPING_NEW,
                    'paid' => AccountModel::MAGENTO_ORDERS_STATUS_MAPPING_PAID,
                    'shipped' => AccountModel::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED
                ],
                'qty_reservation' => [
                    'days' => 0
                ],
                'invoice_mode' => AccountModel::MAGENTO_ORDERS_INVOICE_MODE_YES,
                'shipment_mode' => AccountModel::MAGENTO_ORDERS_SHIPMENT_MODE_YES
            ])
        ];
    }
}
