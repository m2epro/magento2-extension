<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\ReturnPolicy;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy\Builder
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

        $template = $this->activeRecordFactory->getObject('Ebay_Template_ReturnPolicy');

        if (isset($data['id'])) {
            $template->load($data['id']);
        }

        $template->addData($data);
        $template->save();
        $template->setMarketplace($marketplace);

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

        $prepared['marketplace_id'] = (int)$data['marketplace_id'];

        if (isset($data['accepted'])) {
            $prepared['accepted'] = $data['accepted'];
        }

        if (isset($data['option'])) {
            $prepared['option'] = $data['option'];
        }

        if (isset($data['within'])) {
            $prepared['within'] = $data['within'];
        }

        if (isset($data['holiday_mode'])) {
            $prepared['holiday_mode'] = $data['holiday_mode'];
        }

        if (isset($data['shipping_cost'])) {
            $prepared['shipping_cost'] = $data['shipping_cost'];
        }

        if (isset($data['restocking_fee'])) {
            $prepared['restocking_fee'] = $data['restocking_fee'];
        }

        if (isset($data['description'])) {
            $prepared['description'] = $data['description'];
        }

        if (isset($data['accepted']) && $prepared['accepted'] != 'ReturnsAccepted') {
            $prepared['holiday_mode'] = 0;
        }

        return $prepared;
    }

    //########################################
}
