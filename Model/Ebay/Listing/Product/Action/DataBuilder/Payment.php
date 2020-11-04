<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\Payment
 */
class Payment extends AbstractModel
{
    const PAYPAL = 'PayPal';

    /** @var \Ess\M2ePro\Model\Ebay\Template\Payment */
    private $paymentTemplate;

    //########################################

    public function getBuilderData()
    {
        if ($this->getPaymentTemplate()->isManagedPaymentsEnabled()) {
            return [];
        }

        $data = [
            'methods' => $this->getMethodsData()
        ];

        if ($payPalData = $this->getPayPalData($data['methods'])) {
            $data['paypal'] = $payPalData;
        }

        return ['payment' => $data];
    }

    //########################################

    /**
     * @return array
     */
    protected function getMethodsData()
    {
        $methods = [];

        if ($this->getPaymentTemplate()->isPayPalEnabled()) {
            $methods[] = self::PAYPAL;
        }

        $services = $this->getPaymentTemplate()->getServices(true);

        foreach ($services as $service) {
            /** @var $service \Ess\M2ePro\Model\Ebay\Template\Payment\Service */
            $methods[] = $service->getCodeName();
        }

        return $methods;
    }

    protected function getPayPalData($methods)
    {
        if (!in_array(self::PAYPAL, $methods)) {
            return false;
        }

        return [
            'email' => $this->getPaymentTemplate()->getPayPalEmailAddress(),
            'immediate_payment' => $this->getPaymentTemplate()->isPayPalImmediatePaymentEnabled()
        ];
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Payment
     */
    protected function getPaymentTemplate()
    {
        if ($this->paymentTemplate === null) {
            $this->paymentTemplate = $this->getListingProduct()
                                          ->getChildObject()
                                          ->getPaymentTemplate();
        }

        return $this->paymentTemplate;
    }

    //########################################
}
