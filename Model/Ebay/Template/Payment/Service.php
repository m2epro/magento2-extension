<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Payment;

class Service extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Payment
     */
    private $paymentTemplateModel = NULL;

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
        $temp && $this->paymentTemplateModel = NULL;
        return $temp;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Payment
     */
    public function getPaymentTemplate()
    {
        if (is_null($this->paymentTemplateModel)) {

            $this->paymentTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                'Ebay\Template\Payment', $this->getTemplatePaymentId()
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