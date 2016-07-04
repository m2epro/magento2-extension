<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode;

class Breadcrumb extends \Ess\M2ePro\Block\Adminhtml\Widget\Breadcrumb
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingAutoActionModeBreadcrumb');
        // ---------------------------------------

        $this->setContainerData([
            'style' => 'display: none; padding-bottom: 15px;',
            'id_prefix' => $this->getIdPrefix()
        ]);

        $this->setSteps([
            [
                'id' => 1,
                'title' => $this->__('Step 1'),
                'description' => $this->__('General')
            ],
            [
                'id' => 2,
                'title' => $this->__('Step 2'),
                'description' => $this->__('eBay Categories')
            ],
            [
                'id' => 3,
                'title' => $this->__('Step 3'),
                'description' => $this->__('Specifics')
            ],
        ]);
    }

    //########################################
}