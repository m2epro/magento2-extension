<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order;

/**
 * Class \Ess\M2ePro\Model\Walmart\Order\ShippingAddress
 */
class ShippingAddress extends \Ess\M2ePro\Model\Order\ShippingAddress
{
    //########################################

    /**
     * @return array
     */
    public function getRawData()
    {
        return [
            'buyer_name'     => $this->order->getChildObject()->getBuyerName(),
            'email'          => $this->getBuyerEmail(),
            'recipient_name' => $this->getData('recipient_name'),
            'country_id'     => $this->getData('country_code'),
            'region'         => $this->getData('state'),
            'city'           => $this->getData('city'),
            'postcode'       => $this->getPostalCode(),
            'telephone'      => $this->getPhone(),
            'company'        => $this->getData('company'),
            'street'         => array_filter($this->getData('street'))
        ];
    }

    private function getBuyerEmail()
    {
        $email = $this->order->getChildObject()->getData('buyer_email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = str_replace(' ', '-', strtolower($this->order->getChildObject()->getBuyerName()));
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
            $phone = '0000000000';
        }

        return $phone;
    }

    /**
     * @return bool
     */
    public function isRegionValidationRequired()
    {
        if (!$this->getCountry()->getId() || strtoupper($this->getCountry()->getId()) != 'US') {
            return false;
        }

        return $this->getCountry()->getRegions()->getSize() > 0;
    }

    protected function getState()
    {
        $state = $this->getData('state');

        if (!$this->getCountry()->getId() || strtoupper($this->getCountry()->getId()) != 'US') {
            return $state;
        }

        return preg_replace('/[^ \w]+/', '', $state);
    }

    //########################################
}
