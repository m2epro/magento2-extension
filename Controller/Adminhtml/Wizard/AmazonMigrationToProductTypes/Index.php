<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\AmazonMigrationToProductTypes;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Wizard\AmazonMigrationToProductTypes
{
    public function execute()
    {
        if ($this->isNotStarted() || $this->isActive()) {
            $content = $this->getLayout()->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Wizard\AmazonMigrationToProductTypes\Content::class
            );

            $this->getResultPage()->getConfig()->getTitle()->prepend(
                $this->__('M2E Pro Amazon Integration updates')
            );
            $this->addContent($content);

            return $this->getResultPage();
        }

        return $this->_redirect('*/amazon_listing/index');
    }
}
