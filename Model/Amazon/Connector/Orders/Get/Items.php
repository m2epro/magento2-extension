<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\Items
 */
class Items extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    const TIMEOUT_ERRORS_COUNT_TO_RISE = 3;
    const TIMEOUT_RISE_ON_ERROR        = 30;
    const TIMEOUT_RISE_MAX_VALUE       = 1500;

    protected $cacheConfig;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        $account,
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        array $params
    ) {
        $this->cacheConfig = $cacheConfig;
        parent::__construct($helperFactory, $modelFactory, $account, $params);
    }

    // ########################################

    public function getCommand()
    {
        return ['orders','get','items'];
    }

    protected function getRequestData()
    {
        $accountsAccessTokens = [];
        foreach ($this->params['accounts'] as $account) {
            $accountsAccessTokens[] = $account->getChildObject()->getServerHash();
        }

        $data = [
            'accounts'         => $accountsAccessTokens,
            'from_update_date' => $this->params['from_update_date'],
            'to_update_date'   => $this->params['to_update_date']
        ];

        if (!empty($this->params['job_token'])) {
            $data['job_token'] = $this->params['job_token'];
        }

        return $data;
    }

    // ########################################

    public function process()
    {
        $cacheConfigGroup = '/amazon/synchronization/orders/receive/timeout';

        try {
            parent::process();
        } catch (\Ess\M2ePro\Model\Exception\Connection $exception) {
            $data = $exception->getAdditionalData();
            if (!empty($data['curl_error_number']) && $data['curl_error_number'] == CURLE_OPERATION_TIMEOUTED) {
                $fails = (int)$this->cacheConfig->getGroupValue($cacheConfigGroup, 'fails');
                $fails++;

                $rise = (int)$this->cacheConfig->getGroupValue($cacheConfigGroup, 'rise');
                $rise += self::TIMEOUT_RISE_ON_ERROR;

                if ($fails >= self::TIMEOUT_ERRORS_COUNT_TO_RISE && $rise <= self::TIMEOUT_RISE_MAX_VALUE) {
                    $fails = 0;
                    $this->cacheConfig->setGroupValue($cacheConfigGroup, 'rise', $rise);
                }
                $this->cacheConfig->setGroupValue($cacheConfigGroup, 'fails', $fails);
            }

            throw $exception;
        }

        $this->cacheConfig->setGroupValue($cacheConfigGroup, 'fails', 0);
    }

    protected function buildConnectionInstance()
    {
        $connection = parent::buildConnectionInstance();
        $connection->setTimeout($this->getRequestTimeOut());

        return $connection;
    }

    // ########################################

    protected function getRequestTimeOut()
    {
        $cacheConfigGroup = '/amazon/synchronization/orders/receive/timeout';

        $rise = (int)$this->cacheConfig->getGroupValue($cacheConfigGroup, 'rise');
        $rise > self::TIMEOUT_RISE_MAX_VALUE && $rise = self::TIMEOUT_RISE_MAX_VALUE;

        return 300 + $rise;
    }

    // ########################################

    protected function prepareResponseData()
    {
        $responseData = $this->getResponse()->getResponseData();

        if ($this->getResponse()->isResultError() || !isset($responseData['items'])) {
            return;
        }

        $accounts = [];
        foreach ($this->params['accounts'] as $item) {
            $accounts[$item->getChildObject()->getServerHash()] = $item;
        }

        $preparedOrders = [];

        foreach ($responseData['items'] as $accountAccessToken => $ordersData) {
            if (empty($accounts[$accountAccessToken])) {
                continue;
            }

            $preparedOrders[$accountAccessToken] = [];

            /** @var $marketplace \Ess\M2ePro\Model\Marketplace */
            $marketplace = $accounts[$accountAccessToken]->getChildObject()->getMarketplace();

            foreach ($ordersData as $orderData) {
                $order = [];

                $order['amazon_order_id'] = trim($orderData['id']);
                $order['status'] = trim($orderData['status']);

                $order['marketplace_id'] = $marketplace->getId();
                $order['is_afn_channel'] = (int)$orderData['channel']['is_afn'];
                $order['is_prime'] = (int)$orderData['is_prime'];
                $order['is_business'] = (int)$orderData['is_business'];

                $order['purchase_create_date'] = $orderData['purchase_date'];
                $order['purchase_update_date'] = $orderData['update_date'];

                $order['buyer_name'] = trim($orderData['buyer']['name']);
                $order['buyer_email'] = trim($orderData['buyer']['email']);

                $order['qty_shipped'] = (int)$orderData['qty']['shipped'];
                $order['qty_unshipped'] = (int)$orderData['qty']['unshipped'];

                $shipping = $orderData['shipping'];

                $order['shipping_service'] = trim($shipping['level']);
                $order['shipping_price'] = isset($orderData['price']['shipping'])
                    ? (float)$orderData['price']['shipping'] : 0;

                $order['shipping_address'] = $this->parseShippingAddress($shipping, $marketplace);

                $order['shipping_dates'] = [
                    'ship' => [
                        'from' => $shipping['ship_date']['from'],
                        'to' => $shipping['ship_date']['to'],
                    ],
                    'delivery' => [
                        'from' => $shipping['delivery_date']['from'],
                        'to' => $shipping['delivery_date']['to'],
                    ],
                ];

                $order['currency'] = isset($orderData['currency']) ? trim($orderData['currency']) : '';
                $order['paid_amount'] = isset($orderData['amount_paid']) ? (float)$orderData['amount_paid'] : 0;
                $order['tax_details'] = isset($orderData['price']['taxes']) ? $orderData['price']['taxes'] : [];

                $order['discount_details'] = isset($orderData['price']['discounts'])
                    ? $orderData['price']['discounts'] : [];

                $order['items'] = [];

                foreach ($orderData['items'] as $item) {
                    $order['items'][] = [
                        'amazon_order_item_id' => trim($item['id']),
                        'sku' => trim($item['identifiers']['sku']),
                        'general_id' => trim($item['identifiers']['general_id']),
                        'is_isbn_general_id' => (int)$item['identifiers']['is_isbn'],
                        'title' => trim($item['title']),
                        'price' => (float)$item['prices']['product']['value'],
                        'gift_price' => (float)$item['prices']['gift']['value'],
                        'gift_type' => trim($item['gift_type']),
                        'gift_message' => trim($item['gift_message']),
                        'currency' => trim($item['prices']['product']['currency']),
                        'tax_details' => $item['taxes'],
                        'discount_details' => $item['discounts'],
                        'qty_purchased' => (int)$item['qty']['ordered'],
                        'qty_shipped' => (int)$item['qty']['shipped']
                    ];
                }

                $preparedOrders[$accountAccessToken][] = $order;
            }
        }

        $this->responseData = [
            'items'          => $preparedOrders,
            'to_update_date' => $responseData['to_update_date']
        ];

        if (!empty($responseData['job_token'])) {
            $this->responseData['job_token']= $responseData['job_token'];
        }
    }

    private function parseShippingAddress(array $shippingData, \Ess\M2ePro\Model\Marketplace $marketplace)
    {
        $location = isset($shippingData['location']) ? $shippingData['location'] : [];
        $address  = isset($shippingData['address']) ? $shippingData['address'] : [];

        $parsedAddress = [
            'county'         => isset($location['county']) ? trim($location['county']) : '',
            'country_code'   => isset($location['country_code']) ? trim($location['country_code']) : '',
            'state'          => isset($location['state']) ? trim($location['state']) : '',
            'city'           => isset($location['city']) ? trim($location['city']) : '',
            'postal_code'    => isset($location['postal_code']) ? $location['postal_code'] : '',
            'recipient_name' => isset($shippingData['buyer']) ? trim($shippingData['buyer']) : '',
            'phone'          => isset($shippingData['phone']) ? $shippingData['phone'] : '',
            'company'        => '',
            'street'         => [
                isset($address['first']) ? $address['first'] : '',
                isset($address['second']) ? $address['second'] : '',
                isset($address['third']) ? $address['third'] : ''
            ]
        ];
        $parsedAddress['street'] = array_filter($parsedAddress['street']);

        $group = '/amazon/order/settings/marketplace_'.$marketplace->getId().'/';
        $useFirstStreetLineAsCompany = $this->getHelper('Module')
            ->getConfig()
            ->getGroupValue($group, 'use_first_street_line_as_company');

        if ($useFirstStreetLineAsCompany && count($parsedAddress['street']) > 1) {
            $parsedAddress['company'] = array_shift($parsedAddress['street']);
        }

        return $parsedAddress;
    }

    // ########################################
}
