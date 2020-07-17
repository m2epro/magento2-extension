<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\ReturnPolicy;

use Ess\M2ePro\Model\Ebay\Template\ReturnPolicy as ReturnPolicy;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy\Builder
 */
class Builder extends \Ess\M2ePro\Model\Ebay\Template\AbstractBuilder
{
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

        $domesticKeys = [
            'accepted',
            'option',
            'within',
            'shipping_cost'
        ];
        foreach ($domesticKeys as $keyName) {
            isset($this->rawData[$keyName]) && $data[$keyName] = $this->rawData[$keyName];
        }

        $internationalKeys = [
            'international_accepted',
            'international_option',
            'international_within',
            'international_shipping_cost'
        ];
        foreach ($internationalKeys as $keyName) {
            isset($this->rawData[$keyName]) && $data[$keyName] = $this->rawData[$keyName];
        }

        isset($this->rawData['description']) && $data['description'] = $this->rawData['description'];

        return $data;
    }

    //########################################

    public function getDefaultData()
    {
        return [
            'accepted'      => ReturnPolicy::RETURNS_ACCEPTED,
            'option'        => '',
            'within'        => '',
            'shipping_cost' => '',

            'international_accepted'      => ReturnPolicy::RETURNS_NOT_ACCEPTED,
            'international_option'        => '',
            'international_within'        => '',
            'international_shipping_cost' => '',

            'description' => ''
        ];
    }

    //########################################
}
