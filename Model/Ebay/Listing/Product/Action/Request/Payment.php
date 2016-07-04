<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request;

class Payment extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\AbstractModel
{
    const PAYPAL = 'PayPal';

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Payment
     */
    private $paymentTemplate = NULL;

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        $data = array(
            'methods' => $this->getMethodsData()
        );

        if ($payPalData = $this->getPayPalData($data['methods'])) {
            $data['paypal'] = $payPalData;
        }

        return array('payment'=>$data);
    }

    //########################################

    /**
     * @return array
     */
    public function getMethodsData()
    {
        $methods = array();

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

    public function getPayPalData($methods)
    {
        if (!in_array(self::PAYPAL,$methods)) {
            return false;
        }

        return array(
            'email' => $this->getPaymentTemplate()->getPayPalEmailAddress(),
            'immediate_payment' => $this->getPaymentTemplate()->isPayPalImmediatePaymentEnabled()
        );
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Payment
     */
    private function getPaymentTemplate()
    {
        if (is_null($this->paymentTemplate)) {
            $this->paymentTemplate = $this->getListingProduct()
                                          ->getChildObject()
                                          ->getPaymentTemplate();
        }
        return $this->paymentTemplate;
    }

    //########################################
}