<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Congratulation;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;
use Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Congratulation\Content\Form;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Congratulation\Content
 */
class Content extends AbstractBlock
{
    //########################################

    protected $_template = 'wizard/migrationFromMagento1/installation/congratulation.phtml';

    protected function _beforeToHtml()
    {
        $form = $this->getLayout()->createBlock(Form::class);

        $this->setChild('enable_synchronization_form', $form);

        return parent::_beforeToHtml();
    }

    //########################################
}
