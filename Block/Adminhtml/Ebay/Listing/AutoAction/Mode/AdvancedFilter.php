<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode;

class AdvancedFilter extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\AbstractAdvancedFilterMode
{
    protected function prepareGrid(): void
    {
        $grid = $this
            ->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode\AdvancedFilter\Grid::class,
                '',
                ['listing' => $this->getListing()]
            );
        $this->setChild(self::GRID_BLOCK_ALIAS, $grid);
    }

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Ebay\Listing::class)
        );

        return parent::_afterToHtml($html);
    }
}
