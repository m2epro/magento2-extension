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

        $domesticKeys = [
            'accepted',
            'option',
            'within',
            'shipping_cost'
        ];
        foreach ($domesticKeys as $keyName) {
            isset($data[$keyName]) && $prepared[$keyName] = $data[$keyName];
        }

        $internationalKeys = [
            'international_accepted',
            'international_option',
            'international_within',
            'international_shipping_cost'
        ];
        foreach ($internationalKeys as $keyName) {
            isset($data[$keyName]) && $prepared[$keyName] = $data[$keyName];
        }

        isset($data['description']) && $prepared['description'] = $data['description'];

        return $prepared;
    }

    //########################################
}
