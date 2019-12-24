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
        $supportUrl = $this->getUrl('*/support/index');

        return <<<HTML
<h2>
    {$this->__(
        'This wizard was already finished. Please
        <a href="%1%" class="external-link">Contact Us</a>, if it is need.', $supportUrl
    )}
</h2>

HTML;
    }
}
