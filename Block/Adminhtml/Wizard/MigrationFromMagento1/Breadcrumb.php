<?php

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1;

class Breadcrumb extends \Ess\M2ePro\Block\Adminhtml\Widget\Breadcrumb
{
    public function _construct()
    {
        parent::_construct();

        $this->setSteps([
            [
                'id' => 'welcome',
                'title' => $this->__('Step 1'),
                'description' => $this->__('Migration Notes'),
            ],
            [
                'id' => 'synchronization',
                'title' => $this->__('Step 2'),
                'description' => $this->__('Marketplaces Data Synchronization'),
            ],
            [
                'id' => 'congratulation',
                'title' => $this->__('Step 3'),
                'description' => $this->__('Congratulation'),
            ],
        ]);
    }
}