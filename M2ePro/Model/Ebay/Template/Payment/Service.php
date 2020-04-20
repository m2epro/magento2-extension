<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Payment;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Payment\Service
 */
class Service extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Payment
     */
    private $paymentTemplateModel = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Template\Payment\Service');
    }

    //########################################

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->paymentTemplateModel = null;
        return $temp;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Payment
     */
    public function getPaymentTemplate()
    {
        if ($this->paymentTemplateModel === null) {
            $this->paymentTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                'Ebay_Template_Payment',
                $this->getTemplatePaymentId()
            );
        }

        return $this->paymentTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Payment $instance
     */
    public function setPaymentTemplate(\Ess\M2ePro\Model\Ebay\Template\Payment $instance)
    {
         $this->paymentTemplateModel = $instance;
    }

    //########################################

    /**
     * @return int
     */
    public function getTemplatePaymentId()
    {
        return (int)$this->getData('template_payment_id');
    }

    public function getCodeName()
    {
        return $this->getData('code_name');
    }

    //########################################
}
