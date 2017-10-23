<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;
use Ess\M2ePro\Model\Amazon\Account as AccountModel;

abstract class AfterGetTokenAbstract extends InstallationAmazon
{
    public function execute()
    {
        try {
            $accountData = $this->getAccountData();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
            $this->messageManager->addError($this->__($exception->getMessage()));

            return $this->indexAction();
        }

        $accountModel = $this->amazonFactory->getObject('Account')->setData($accountData)->save();

        try {
            /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
            $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');

            $params = array(
                'title'            => $accountData['merchant_id'],
                'marketplace_id'   => $accountData['marketplace_id'],
                'merchant_id'      => $accountData['merchant_id'],
                'token'            => $accountData['token'],
            );

            $connectorObj = $dispatcherObject->getConnector('account', 'add', 'entityRequester',
                $params, $accountModel->getId());
            $dispatcherObject->process($connectorObj);

        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
            $this->messageManager->addError($this->__($exception->getMessage()));
            $accountModel->delete();

            return $this->indexAction();
        }

        $this->activeRecordFactory->getObjectLoaded('Marketplace', $accountData['marketplace_id'])
            ->setData('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
            ->save();

        $this->setStep($this->getNextStep());

        return $this->_redirect('*/*/installation');
    }

    abstract protected function getAccountData();

    /**
     * @return array
     */
    protected function getAmazonAccountDefaultSettings()
    {
        $billingAddressTheSame
            = AccountModel::MAGENTO_ORDERS_BILLING_ADDRESS_MODE_SHIPPING_IF_SAME_CUSTOMER_AND_RECIPIENT;
        return array(
            'related_store_id' => 0,

            'other_listings_synchronization' => AccountModel::OTHER_LISTINGS_SYNCHRONIZATION_NO,
            'other_listings_mapping_mode' => AccountModel::OTHER_LISTINGS_MAPPING_MODE_NO,
            'other_listings_mapping_settings' => $this->getHelper('Data')->jsonEncode(array()),
            'other_listings_move_mode' => AccountModel::OTHER_LISTINGS_MOVE_TO_LISTINGS_DISABLED,
            'other_listings_move_synch' => AccountModel::OTHER_LISTINGS_MOVE_TO_LISTINGS_SYNCH_MODE_NONE,

            'magento_orders_settings' => $this->getHelper('Data')->jsonEncode(array(
                'listing' => array(
                    'mode' => AccountModel::MAGENTO_ORDERS_LISTINGS_MODE_YES,
                    'store_mode' => AccountModel::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT,
                    'store_id' => NULL
                ),
                'listing_other' => array(
                    'mode' => AccountModel::MAGENTO_ORDERS_LISTINGS_OTHER_MODE_YES,
                    'product_mode' => AccountModel::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IMPORT,
                    'product_tax_class_id' => \Ess\M2ePro\Model\Magento\Product::TAX_CLASS_ID_NONE,
                    'store_id' => $this->getHelper('Magento\Store')->getDefaultStoreId(),
                ),
                'number' => array(
                    'source' => AccountModel::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO,
                    'prefix' => array(
                        'mode'   => AccountModel::MAGENTO_ORDERS_NUMBER_PREFIX_MODE_NO,
                        'prefix' => '',
                    ),
                ),
                'tax' => array(
                    'mode' => AccountModel::MAGENTO_ORDERS_TAX_MODE_MIXED
                ),
                'customer' => array(
                    'mode' => AccountModel::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST,
                    'id' => NULL,
                    'website_id' => NULL,
                    'group_id' => NULL,
//                'subscription_mode' => AccountModel::MAGENTO_ORDERS_CUSTOMER_NEW_SUBSCRIPTION_MODE_NO,
                    'notifications' => array(
//                    'customer_created' => false,
                        'invoice_created' => false,
                        'order_created' => false
                    ),
                    'billing_address_mode' => $billingAddressTheSame
                ),
                'status_mapping' => array(
                    'mode' => AccountModel::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT,
                    'processing' => AccountModel::MAGENTO_ORDERS_STATUS_MAPPING_PROCESSING,
                    'shipped' => AccountModel::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED,
                ),
                'qty_reservation' => array(
                    'days' => 1
                ),
                'refund_and_cancellation' => array(
                    'refund_mode' => 1,
                ),
                'fba' => array(
                    'mode' => AccountModel::MAGENTO_ORDERS_FBA_MODE_YES,
                    'stock_mode' => AccountModel::MAGENTO_ORDERS_FBA_STOCK_MODE_NO
                ),
                'invoice_mode' => AccountModel::MAGENTO_ORDERS_INVOICE_MODE_YES,
                'shipment_mode' => AccountModel::MAGENTO_ORDERS_SHIPMENT_MODE_YES
            ))
        );
    }
}