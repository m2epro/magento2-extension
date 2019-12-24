<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Breadcrumb
 */
class Breadcrumb extends \Ess\M2ePro\Block\Adminhtml\Widget\Breadcrumb
{
    public function _construct()
    {
        parent::_construct();

        $this->setSteps([
            [
                'id' => 'disableModule',
                'title' => $this->__('Step 1'),
                'description' => $this->__('Module Preparation'),
            ],
            [
                'id' => 'database',
                'title' => $this->__('Step 2'),
                'description' => $this->__('Database Migration'),
            ],
            [
                'id' => 'synchronization',
                'title' => $this->__('Step 3'),
                'description' => $this->__('Marketplaces Synchronization'),
            ],
            [
                'id' => 'congratulation',
                'title' => $this->__('Step 4'),
                'description' => $this->__('Congratulation'),
            ],
        ]);
    }
}
