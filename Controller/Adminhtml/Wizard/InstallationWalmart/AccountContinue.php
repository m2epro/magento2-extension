<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;
use Ess\M2ePro\Model\Walmart\Account as WalmartAccount;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart\AccountContinue
 */
class AccountContinue extends InstallationWalmart
{
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if (empty($params)) {
            return $this->indexAction();
        }

        if (empty($params['marketplace_id'])) {
            $result['message'] = $this->__('Please select Marketplace');
            $this->setJsonContent($result);
            return $this->getResult();
        }

        $result =  [
            'result' => false,
            'message' => null
        ];

        try {
            $accountData = [];

            $requiredFields = [
                'marketplace_id',
                'consumer_id',
                'old_private_key',
                'client_id',
                'client_secret'
            ];

            foreach ($requiredFields as $requiredField) {
                if (!empty($params[$requiredField])) {
                    $accountData[$requiredField] = $params[$requiredField];
                }
            }

            /** @var $marketplaceObject \Ess\M2ePro\Model\Marketplace */
            $marketplaceObject = $this->walmartFactory->getCachedObjectLoaded(
                'Marketplace',
                $params['marketplace_id']
            );

            if ($params['marketplace_id'] == \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA &&
                $params['consumer_id'] && $params['old_private_key']) {
                $requestData = [
                    'marketplace_id' => $params['marketplace_id'],
                    'consumer_id' => $params['consumer_id'],
                    'private_key' => $params['old_private_key'],
                ];
            } elseif ($params['marketplace_id'] != \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA &&
                $params['client_id'] && $params['client_secret']) {
                $requestData = [
                    'marketplace_id' => $params['marketplace_id'],
                    'client_id'     => $params['client_id'],
                    'client_secret' => $params['client_secret'],
                    'consumer_id'   => $params['consumer_id']
                ];
            } else {
                $result['message'] = $this->__('You should fill all required fields.');
                $this->setJsonContent($result);
                return $this->getResult();
            }

            $marketplaceObject->setData('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)->save();

            $accountData = array_merge(
                $this->getAccountDefaultSettings(),
                [
                    'title' => "Default - {$marketplaceObject->getCode()}",
                ],
                $accountData
            );

            /** @var $model \Ess\M2ePro\Model\Account */
            $model = $this->walmartFactory->getObject('Account');
            $model->setData($accountData);
            $id = $model->save()->getId();

            /** @var $dispatcherObject \Ess\M2ePro\Model\Walmart\Connector\Dispatcher */
            $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');

            $connectorObj = $dispatcherObject->getConnector(
                'account',
                'add',
                'entityRequester',
                $requestData,
                $id
            );
            $dispatcherObject->process($connectorObj);
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            if (!empty($model)) {
                $model->delete();
            }

            $error = 'The Walmart access obtaining is currently unavailable.<br/>Reason: %error_message%';
            $error = $this->__($error, $exception->getMessage());

            $this->setJsonContent([
                'success' => false,
                'message' => $error
            ]);

            return $this->getResult();
        }

        $this->setStep($this->getNextStep());

        $this->setJsonContent([
            'success' => true
        ]);
        return $this->getResult();
    }

    private function getAccountDefaultSettings()
    {
        return [
            'title'           => '',
            'marketplace_id'  => 0,

            'related_store_id' => 0,

            'other_listings_synchronization'  => WalmartAccount::OTHER_LISTINGS_SYNCHRONIZATION_NO,
            'other_listings_mapping_mode'     => WalmartAccount::OTHER_LISTINGS_MAPPING_MODE_NO,
            'other_listings_mapping_settings' => $this->getHelper('Data')->jsonEncode([]),

            'magento_orders_settings' => $this->getHelper('Data')->jsonEncode([
                'listing' => [
                    'mode'       => WalmartAccount::MAGENTO_ORDERS_LISTINGS_MODE_YES,
                    'store_mode' => WalmartAccount::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT,
                    'store_id'   => null
                ],
                'listing_other' => [
                    'mode'                 => WalmartAccount::MAGENTO_ORDERS_LISTINGS_OTHER_MODE_YES,
                    'product_mode'         => WalmartAccount::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IMPORT,
                    'product_tax_class_id' => \Ess\M2ePro\Model\Magento\Product::TAX_CLASS_ID_NONE,
                    'store_id'             => $this->getHelper('Magento\Store')->getDefaultStoreId(),
                ],
                'number' => [
                    'source' => WalmartAccount::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO,
                    'prefix' => [
                        'mode'   => WalmartAccount::MAGENTO_ORDERS_NUMBER_PREFIX_MODE_NO,
                        'prefix' => '',
                    ]
                ],
                'tax' => [
                    'mode' => WalmartAccount::MAGENTO_ORDERS_TAX_MODE_MIXED
                ],
                'customer' => [
                    'mode'          => WalmartAccount::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST,
                    'id'            => null,
                    'website_id'    => null,
                    'group_id'      => null,
                    'notifications' => [
                        'invoice_created' => false,
                        'order_created'   => false
                    ],
                ],
                'status_mapping' => [
                    'mode'       => WalmartAccount::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT,
                    'processing' => WalmartAccount::MAGENTO_ORDERS_STATUS_MAPPING_PROCESSING,
                    'shipped'    => WalmartAccount::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED,
                ],
                'invoice_mode'   => WalmartAccount::MAGENTO_ORDERS_INVOICE_MODE_YES,
                'shipment_mode'  => WalmartAccount::MAGENTO_ORDERS_SHIPMENT_MODE_YES
            ])
        ];
    }
}
