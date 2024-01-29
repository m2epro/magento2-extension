<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order;

class ShippingAddress extends \Ess\M2ePro\Model\Order\ShippingAddress
{
    /**
     * @return array
     */
    public function getRawData()
    {
        return [
            'buyer_name' => $this->order->getChildObject()->getBuyerName(),
            'email' => $this->getBuyerEmail(),
            'recipient_name' => $this->getData('recipient_name'),
            'country_id' => $this->getData('country_code'),
            'region' => $this->getData('state'),
            'city' => $this->getData('city') ? $this->getData('city') : $this->getCountryName(),
            'postcode' => $this->getPostalCode(),
            'telephone' => $this->getPhone(),
            'company' => $this->getData('company'),
            'street' => array_filter($this->getData('street')),
        ];
    }

    private function getBuyerEmail()
    {
        $email = $this->order->getChildObject()->getData('buyer_email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = strtolower($this->order->getChildObject()->getBuyerName());
            $email = str_replace(' ', '-', $email);
            $email = preg_replace("/[^a-z0-9-]/", '', $email);
            $email = mb_convert_encoding($email, "ASCII");
            $email .= \Ess\M2ePro\Model\Magento\Customer::FAKE_EMAIL_POSTFIX;
        }

        return $email;
    }

    private function getPostalCode()
    {
        $postalCode = $this->getData('postal_code');

        if ($postalCode == '') {
            $postalCode = '0000';
        }

        return $postalCode;
    }

    private function getPhone()
    {
        $phone = $this->getData('phone');

        if ($phone == '') {
            $phone = '+0 000-000-0000';
        }

        return $phone;
    }

    protected function getState()
    {
        $state = $this->getData('state');

        if (!$this->getCountry()->getId() || strtoupper($this->getCountry()->getId()) != 'US') {
            return $state;
        }

        return preg_replace('/[^ \w]+/', '', $state);
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function isRegionOverrideRequired(): bool
    {
        /** @var \Ess\M2ePro\Model\Walmart\Account $account */
        $account = $this->order->getAccount()->getChildObject();

        return $account->isRegionOverrideRequired();
    }
}
