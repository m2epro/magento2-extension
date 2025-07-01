<?php

namespace Ess\M2ePro\Block\Adminhtml\Wizard;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class Congratulation extends AbstractBlock
{
    protected function _toHtml()
    {
        $message = __(
            'The Installation Wizard has finished successfully. To finalize the setup, please clear the Magento cache.
If you experience any issues, feel free to contact our support team at <a href="mailto:%mail">%mail</a>.',
            ['mail' => 'support@m2epro.com']
        );

        return "<h2>$message</h2>";
    }
}
