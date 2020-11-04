<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Payment;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Payment\Builder
 */
class Builder extends \Ess\M2ePro\Model\Ebay\Template\AbstractBuilder
{
    //########################################

    public function build($model, array $rawData)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Template\Payment $model */
        $model = parent::build($model, $rawData);

        $services = $model->getServices(true);
        foreach ($services as $service) {
            $service->delete();
        }

        if (empty($this->rawData['services']) || !is_array($this->rawData['services'])) {
            return $model;
        }

        foreach ($this->rawData['services'] as $codeName) {
            $this->createService($model->getId(), $codeName);
        }

        return $model;
    }

    //########################################

    protected function validate()
    {
        if (empty($this->rawData['marketplace_id'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Marketplace ID is empty.');
        }

        parent::validate();
    }

    protected function prepareData()
    {
        $this->validate();

        $data = parent::prepareData();

        $data['marketplace_id'] = (int)$this->rawData['marketplace_id'];

        if (isset($this->rawData['managed_payments_mode'])) {
            $data['managed_payments_mode'] = (int)(bool)$this->rawData['managed_payments_mode'];
        } else {
            $data['managed_payments_mode'] = 0;
        }

        if (isset($this->rawData['pay_pal_mode'])) {
            $data['pay_pal_mode'] = (int)(bool)$this->rawData['pay_pal_mode'];
        } else {
            $data['pay_pal_mode'] = 0;
        }

        if (isset($this->rawData['pay_pal_email_address'])) {
            $data['pay_pal_email_address'] = $this->rawData['pay_pal_email_address'];
        }

        $data['pay_pal_immediate_payment'] = 0;
        if (isset($this->rawData['pay_pal_immediate_payment'])) {
            $data['pay_pal_immediate_payment'] = (int)(bool)$this->rawData['pay_pal_immediate_payment'];
        }

        return $data;
    }

    //########################################

    protected function createService($templatePaymentId, $codeName)
    {
        $data = [
            'template_payment_id' => $templatePaymentId,
            'code_name' => $codeName
        ];

        $model = $this->activeRecordFactory->getObject('Ebay_Template_Payment_Service');
        $model->addData($data);
        $model->save();

        return $model;
    }

    //########################################

    public function getDefaultData()
    {
        return [
            'managed_payments_mode'     => 0,
            'pay_pal_mode'              => 0,
            'pay_pal_email_address'     => '',
            'pay_pal_immediate_payment' => 0,
            'services'                  => []
        ];
    }

    //########################################
}
