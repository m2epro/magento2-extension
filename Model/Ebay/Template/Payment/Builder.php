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
class Builder extends \Ess\M2ePro\Model\Ebay\Template\Builder\AbstractModel
{
    //########################################

    public function build(array $data)
    {
        if (empty($data)) {
            return null;
        }

        $this->validate($data);

        $data = $this->prepareData($data);

        $marketplace = $this->ebayFactory->getCachedObjectLoaded(
            'Marketplace',
            $data['marketplace_id']
        );

        $template = $this->activeRecordFactory->getObject('Ebay_Template_Payment');

        if (isset($data['id'])) {
            $template->load($data['id']);
        }

        $template->addData($data);
        $template->save();
        $template->setMarketplace($marketplace);

        $services = $template->getServices(true);
        foreach ($services as $service) {
            $service->delete();
        }

        if (empty($data['services']) || !is_array($data['services'])) {
            return $template;
        }

        foreach ($data['services'] as $codeName) {
            $this->createService($template->getId(), $codeName);
        }

        return $template;
    }

    //########################################

    protected function validate(array $data)
    {
        // ---------------------------------------
        if (empty($data['marketplace_id'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Marketplace ID is empty.');
        }
        // ---------------------------------------

        parent::validate($data);
    }

    protected function prepareData(array &$data)
    {
        $prepared = parent::prepareData($data);

        // ---------------------------------------
        $prepared['marketplace_id'] = (int)$data['marketplace_id'];
        // ---------------------------------------

        // ---------------------------------------
        if (isset($data['pay_pal_mode'])) {
            $prepared['pay_pal_mode'] = (int)(bool)$data['pay_pal_mode'];
        } else {
            $prepared['pay_pal_mode'] = 0;
        }

        if (isset($data['pay_pal_email_address'])) {
            $prepared['pay_pal_email_address'] = $data['pay_pal_email_address'];
        }

        $prepared['pay_pal_immediate_payment'] = 0;
        if (isset($data['pay_pal_immediate_payment'])) {
            $prepared['pay_pal_immediate_payment'] = (int)(bool)$data['pay_pal_immediate_payment'];
        }

        if (isset($data['services']) && is_array($data['services'])) {
            $prepared['services'] = $data['services'];
        }
        // ---------------------------------------

        return $prepared;
    }

    //########################################

    private function createService($templatePaymentId, $codeName)
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
}
