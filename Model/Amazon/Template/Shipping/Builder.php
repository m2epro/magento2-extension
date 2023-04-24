<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Shipping;

class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $amazonHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->amazonHelper = $amazonHelper;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function prepareData(): array
    {
        $data = [];

        $keys = array_keys($this->getDefaultData());

        foreach ($keys as $key) {
            if (isset($this->rawData[$key])) {
                $data[$key] = $this->rawData[$key];
            }
        }

        if (isset($data['account_id'])) {
            $data['marketplace_id'] = $this->amazonHelper->getAccountMarketplace($data['account_id']);
        }

        return $data;
    }

    /**
     * @return string[]
     */
    public function getDefaultData(): array
    {
        return [
            'title' => '',
            'account_id' => '',
            'marketplace_id' => '',
            'template_id' => '',
        ];
    }
}
