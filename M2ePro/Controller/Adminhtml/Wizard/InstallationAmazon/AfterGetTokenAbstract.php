<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;
use Ess\M2ePro\Model\Amazon\Account as AccountModel;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon\AfterGetTokenAbstract
 */
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
            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');

            $params = [
                'title'            => $accountData['merchant_id'],
                'marketplace_id'   => $accountData['marketplace_id'],
                'merchant_id'      => $accountData['merchant_id'],
                'token'            => $accountData['token'],
            ];

            $connectorObj = $dispatcherObject->getConnector(
                'account',
                'add',
                'entityRequester',
                $params,
                $accountModel->getId()
            );
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
        return [
            'related_store_id' => 0,

            'other_listings_synchronization' => 0,
            'other_listings_mapping_mode' => 0,
            'other_listings_mapping_settings' => $this->getHelper('Data')->jsonEncode([]),

            'magento_orders_settings' => $this->getHelper('Data')->jsonEncode([
                'listing' => [
                    'mode' => 1,
                    'store_mode' => AccountModel::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT,
                    'store_id' => null
                ],
                'listing_other' => [
                    'mode' => 1,
                    'product_mode' => AccountModel::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IMPORT,
                    'product_tax_class_id' => \Ess\M2ePro\Model\Magento\Product::TAX_CLASS_ID_NONE,
                    'store_id' => $this->getHelper('Magento\Store')->getDefaultStoreId(),
                ],
                'number' => [
                    'source' => AccountModel::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO,
                    'prefix' => [
                        'mode'   => 0,
                        'prefix' => '',
                    ],
                ],
                'tax' => [
                    'mode' => AccountModel::MAGENTO_ORDERS_TAX_MODE_MIXED
                ],
                'customer' => [
                    'mode' => AccountModel::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST,
                    'id' => null,
                    'website_id' => null,
                    'group_id' => null,
//                'subscription_mode' => 0,
                    'notifications' => [
//                    'customer_created' => false,
                        'invoice_created' => false,
                        'order_created' => false
                    ],
                    'billing_address_mode' => $billingAddressTheSame
                ],
                'status_mapping' => [
                    'mode' => AccountModel::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT,
                    'processing' => AccountModel::MAGENTO_ORDERS_STATUS_MAPPING_PROCESSING,
                    'shipped' => AccountModel::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED,
                ],
                'qty_reservation' => [
                    'days' => 1
                ],
                'refund_and_cancellation' => [
                    'refund_mode' => 1,
                ],
                'fba' => [
                    'mode' => 1,
                    'stock_mode' => 0
                ],
                'invoice_mode' => 1,
                'shipment_mode' => 1
            ])
        ];
    }
}
