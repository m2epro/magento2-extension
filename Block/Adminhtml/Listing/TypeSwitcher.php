<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing;

abstract class TypeSwitcher extends \Ess\M2ePro\Block\Adminhtml\Switcher
{
    const LISTING_TYPE_M2E_PRO       = 'product';
    const LISTING_TYPE_LISTING_OTHER = 'other';

    protected $paramName = 'listing_type';

    //########################################

    public function getLabel()
    {
        return $this->__('Listing Type');
    }

    public function hasDefaultOption()
    {
        return false;
    }

    //---------------------------------------

    protected function loadItems()
    {
        $this->items = [
            'mode' => [
                'value' => [
                    [
                        'label' => $this->__('M2E Pro'),
                        'value' => self::LISTING_TYPE_M2E_PRO
                    ],
                    [
                        'label' => $this->__('3rd Party'),
                        'value' => self::LISTING_TYPE_LISTING_OTHER
                    ],
                ]
            ]
        ];
    }

    //########################################
}