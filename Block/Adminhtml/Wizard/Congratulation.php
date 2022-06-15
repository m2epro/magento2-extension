<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\Congratulation
 */
class Congratulation extends AbstractBlock
{
    protected function _toHtml()
    {
        return <<<HTML
<h2>
    {$this->__(
        'Installation Wizard is completed. If you can\'t proceed, please contact us at <a href="mailto:support@m2epro.com">support@m2epro.com</a>.'
    )}
</h2>

HTML;
    }
}
