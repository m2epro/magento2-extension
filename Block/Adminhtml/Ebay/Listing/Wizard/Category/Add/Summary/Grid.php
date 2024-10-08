<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\Add\Summary;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Category\Summary\Grid as SummaryGrid;

class Grid extends SummaryGrid
{
    public function setWizardId(int $wizardId): self
    {
        $this->setData('wizard_id', $wizardId);

        return $this;
    }

    private function getWizardId(): int
    {
        return (int)$this->getData('wizard_id');
    }

    protected function _toHtml()
    {
        $html = parent::_toHtml();

        $this->jsUrl->add(
            $this->getUrl(
                '*/ebay_listing_wizard_product/removeProductsByCategory',
                ['id' => $this->getWizardId()],
            ),
            'ebay_listing_product_add/removeSessionProductsByCategory',
        );

        return $html;
    }
}
