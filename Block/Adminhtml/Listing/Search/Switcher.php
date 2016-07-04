<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Search;

class Switcher extends \Ess\M2ePro\Block\Adminhtml\Switcher
{
    const LISTING_TYPE_M2E_PRO = 1;
    const LISTING_TYPE_LISTING_OTHER = 2;

    protected $paramName = 'listing_type';

    //########################################

    public function getLabel()
    {
        return $this->__('Listing Type');
    }

    public function getItems()
    {
        return [
            'mode' => [
                'value' => [
                    [
                        'label' => $this->__('M2E Pro'),
                        'value' => self::LISTING_TYPE_M2E_PRO
                    ],
                    [
                        'label' => $this->__('3rd party'),
                        'value' => self::LISTING_TYPE_LISTING_OTHER
                    ],
                ]
            ]
        ];
    }

    public function getDefaultOptionName()
    {
        return $this->__('All');
    }

    //########################################
}