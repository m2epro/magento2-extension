<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\WalmartMigrationToProductTypes;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Wizard\AbstractWalmartMigrationToProductTypes
{
    public function execute()
    {
        if ($this->isNotStarted() || $this->isActive()) {
            $content = $this->getLayout()->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Wizard\WalmartMigrationToProductTypes\Content::class
            );

            $this->getResultPage()->getConfig()->getTitle()->prepend(
                __('M2E Pro Walmart Integration Updates')
            );
            $this->addContent($content);

            return $this->getResultPage();
        }

        return $this->_redirect('*/walmart_listing/index');
    }
}
