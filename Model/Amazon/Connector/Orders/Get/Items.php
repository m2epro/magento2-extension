<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get;

class Items extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    public const TIMEOUT_ERRORS_COUNT_TO_RISE = 3;
    public const TIMEOUT_RISE_ON_ERROR = 30;
    public const TIMEOUT_RISE_MAX_VALUE = 1500;

    /** @see https://developer-docs.amazon.com/sp-api/docs/orders-api-v0-reference#addresstype */
    private const AMAZON_ADDRESS_TYPE_COMMERCIAL = 'Commercial';

    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $configManager;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registryManager;

    /**
     * @param \Ess\M2ePro\Model\Config\Manager $configManager
     * @param \Ess\M2ePro\Model\Registry\Manager $registryManager
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Model\Account|NULL $account
     * @param array $params
     */
    public function __construct(
        \Ess\M2ePro\Model\Config\Manager $configManager,
        \Ess\M2ePro\Model\Registry\Manager $registryManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        $account,
        array $params
    ) {
        parent::__construct(
            $helperFactory,
            $modelFactory,
            $account,
            $params
        );
        $this->configManager = $configManager;
        $this->registryManager = $registryManager;
    }

    /**
     * @return string[]
     */
    public function getCommand()
    {
        return ['orders', 'get', 'items'];
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        $accountsAccessTokens = [];
        foreach ($this->params['accounts'] as $account) {
            $accountsAccessTokens[] = $account->getChildObject()->getServerHash();
        }

        $data = [
            'accounts' => $accountsAccessTokens,
        ];

        if (
            !empty($this->params['from_update_date'])
            && !empty($this->params['to_update_date'])
        ) {
            $data['from_update_date'] = $this->params['from_update_date'];
            $data['to_update_date'] = $this->params['to_update_date'];
        }

        if (
            !empty($this->params['from_create_date'])
            && !empty($this->params['to_create_date'])
        ) {
            $data['from_create_date'] = $this->params['from_create_date'];
            $data['to_create_date'] = $this->params['to_create_date'];
        }

        if (!empty($this->params['job_token'])) {
            $data['job_token'] = $this->params['job_token'];
        }

        return $data;
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Connection
     */
    public function process()
    {
        try {
            parent::process();
        } catch (\Ess\M2ePro\Model\Exception\Connection $exception) {
            $data = $exception->getAdditionalData();
            if (
                !empty($data['curl_error_number'])
                && (int)$data['curl_error_number'] === CURLE_OPERATION_TIMEOUTED
            ) {
                $fails = (int)$this->registryManager->getValue('/amazon/orders/receive/timeout_fails/');
                $fails++;

                $rise = (int)$this->registryManager->getValue('/amazon/orders/receive/timeout_rise/');
                $rise += self::TIMEOUT_RISE_ON_ERROR;

                if ($fails >= self::TIMEOUT_ERRORS_COUNT_TO_RISE && $rise <= self::TIMEOUT_RISE_MAX_VALUE) {
                    $fails = 0;
                    $this->registryManager->setValue('/amazon/orders/receive/timeout_rise/', $rise);
                }
                $this->registryManager->setValue('/amazon/orders/receive/timeout_fails/', $fails);
            }

            throw $exception;
        }

        $this->registryManager->setValue('/amazon/orders/receive/timeout_fails/', 0);
    }

    // ----------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Connector\Connection\Single
     */
    protected function buildConnectionInstance()
    {
        $connection = parent::buildConnectionInstance();
        $connection->setTimeout($this->getRequestTimeOut());

        return $connection;
    }

    protected function prepareResponseData()
    {
        $responseData = $this->getResponse()->getResponseData();

        if (
            !isset($responseData['items'])
            || $this->getResponse()->isResultError()
        ) {
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

            /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
            $marketplace = $accounts[$accountAccessToken]->getChildObject()->getMarketplace();

            foreach ($ordersData as $orderData) {
                $order = [];

                $order['amazon_order_id'] = trim((string)$orderData['id']);
                $order['status'] = trim((string)$orderData['status']);

                $sellerOrderId = trim((string)$orderData['seller_id']);
                $order['seller_order_id'] = empty($sellerOrderId) ? null : $sellerOrderId;

                $order['marketplace_id'] = $marketplace->getId();
                $order['is_afn_channel'] = (int)$orderData['channel']['is_afn'];
                $order['is_prime'] = (int)$orderData['is_prime'];
                $order['is_sold_by_amazon'] = (int)$orderData['is_sold_by_amazon'];
                $order['is_business'] = (int)$orderData['is_business'];

                $order['purchase_create_date'] = $orderData['purchase_date'];
                $order['purchase_update_date'] = $orderData['update_date'];

                $order['buyer_name'] = trim((string)$orderData['buyer']['name']);
                $order['buyer_email'] = trim((string)$orderData['buyer']['email']);
                $order['buyer_tax_info'] = $orderData['buyer']['tax_info'] ?? [];

                $order['is_replacement'] = (int)$orderData['is_replacement'];
                $order['replaced_amazon_order_id'] = empty($orderData['replaced_order_id']) ? null :
                    trim((string)$orderData['replaced_order_id']);

                $order['qty_shipped'] = (int)$orderData['qty']['shipped'];
                $order['qty_unshipped'] = (int)$orderData['qty']['unshipped'];

                $shipping = $orderData['shipping'];

                $order['shipping_service'] = trim((string)$shipping['level']);
                $order['shipping_category'] = trim($shipping['category']);
                $order['shipping_mapping'] = trim((string)$orderData['shipping_mapping']);
                $order['shipping_price'] = isset($orderData['price']['shipping'])
                    ? (float)$orderData['price']['shipping'] : 0;

                $order['shipping_address'] = $this->parseShippingAddress($orderData, $marketplace);

                $order['shipping_date_to'] = $shipping['ship_date']['to'];
                $order['delivery_date_from'] = $shipping['delivery_date']['from'];
                $order['delivery_date_to'] = $shipping['delivery_date']['to'];

                $order['currency'] = trim((string)($orderData['currency'] ?? ''));
                $order['paid_amount'] = (float)($orderData['amount_paid'] ?? 0);
                $order['tax_details'] = $orderData['price']['taxes'] ?? [];
                $order['tax_registration_details'] = $orderData['tax_registration_details'] ?? [];

                $order['is_buyer_requested_cancel'] = (int)($orderData['is_buyer_requested_cancel'] ?? 0);
                $order['buyer_cancel_reason'] = $orderData['buyer_cancel_reason'] ?? null;

                $order['discount_details'] = $orderData['price']['discounts'] ?? [];

                $order['items'] = [];

                foreach ($orderData['items'] as $item) {
                    $order['items'][] = [
                        'amazon_order_item_id' => trim((string)$item['id']),
                        'sku' => trim((string)$item['identifiers']['sku']),
                        'general_id' => trim((string)$item['identifiers']['general_id']),
                        'is_isbn_general_id' => (int)$item['identifiers']['is_isbn'],
                        'title' => trim((string)$item['title']),
                        'price' => (float)$item['prices']['product']['value'],
                        'shipping_price' => (float)$item['prices']['shipping']['value'],
                        'gift_price' => (float)$item['prices']['gift']['value'],
                        'gift_type' => trim((string)($item['gift_type'] ?? '')),
                        'gift_message' => trim((string)($item['gift_message'] ?? '')),
                        'currency' => trim((string)$item['prices']['product']['currency']),
                        'tax_details' => $item['taxes'],
                        'ioss_number' => $item['ioss_number'],
                        'discount_details' => $item['discounts'],
                        'qty_purchased' => (int)$item['qty']['ordered'],
                        'qty_shipped' => (int)$item['qty']['shipped'],
                        'buyer_customized_info' => !empty($item['buyer']['customized_info'])
                            ? trim($item['buyer']['customized_info'])
                            : null,
                        'is_shipping_pallet_delivery' => (int)($item['shipping_pallet_delivery'] ?? 0),
                    ];
                }

                $preparedOrders[$accountAccessToken][] = $order;
            }
        }

        $this->responseData = [
            'items' => $preparedOrders,
        ];

        if (!empty($responseData['to_update_date'])) {
            $this->responseData['to_update_date'] = $responseData['to_update_date'];
        }

        if (!empty($responseData['to_create_date'])) {
            $this->responseData['to_create_date'] = $responseData['to_create_date'];
        }

        if (!empty($responseData['job_token'])) {
            $this->responseData['job_token'] = $responseData['job_token'];
        }
    }

    // ----------------------------------------

    /**
     * @return int
     */
    private function getRequestTimeOut(): int
    {
        $rise = (int)$this->registryManager->getValue('/amazon/orders/receive/timeout_rise/');
        if ($rise > self::TIMEOUT_RISE_MAX_VALUE) {
            $rise = self::TIMEOUT_RISE_MAX_VALUE;
        }

        return 300 + $rise;
    }

    /**
     * @param array $shippingData
     * @param \Ess\M2ePro\Model\Marketplace $marketplace
     *
     * @return array
     */
    private function parseShippingAddress(
        array $orderData,
        \Ess\M2ePro\Model\Marketplace $marketplace
    ): array {
        $shippingData = $orderData['shipping'];
        $location = $shippingData['location'] ?? [];
        $address = $shippingData['address'] ?? [];

        $parsedAddress = [
            'county' => trim((string)($location['county'] ?? '')),
            'country_code' => trim((string)($location['country_code'] ?? '')),
            'state' => trim((string)($location['state'] ?? '')),
            'city' => trim((string)($location['city'] ?? '')),
            'postal_code' => trim((string)($location['postal_code'] ?? '')),
            'recipient_name' => trim((string)($shippingData['buyer'] ?? '')),
            'phone' => $shippingData['phone'] ?? '',
            'company' => $shippingData['company_name'] ?? '',
            'address_type' => trim((string)($shippingData['address_type'] ?? '')),
            'street' => array_filter([
                $address['first'] ?? '',
                $address['second'] ?? '',
                $address['third'] ?? '',
            ]),
            'buyer_company_name' => $orderData['buyer_company_name'] ?? '',
        ];

        $group = '/amazon/order/settings/marketplace_' . $marketplace->getId() . '/';
        $useFirstStreetLineAsCompany = $this->configManager
            ->getGroupValue($group, 'use_first_street_line_as_company');

        if (
            $useFirstStreetLineAsCompany
            && empty($parsedAddress['company'])
            && $parsedAddress['address_type'] === self::AMAZON_ADDRESS_TYPE_COMMERCIAL
            && count($parsedAddress['street']) > 1
        ) {
            $parsedAddress['company'] = array_shift($parsedAddress['street']);
        }

        return $parsedAddress;
    }
}
