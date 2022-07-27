<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode;

class Category extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\AbstractCategory
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /**
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        parent::__construct($context, $registry, $formFactory, $dataHelper, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingAutoActionModeCategory');
        // ---------------------------------------
    }

    protected function prepareGroupsGrid()
    {
        $groupGrid = $this->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode\Category\Group\Grid::class);
        $groupGrid->prepareGrid();
        $this->setChild('group_grid', $groupGrid);

        return $groupGrid;
    }

    /**
     * @param $html
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \ReflectionException
     */
    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Amazon\Listing::class)
        );

        return parent::_afterToHtml($html);
    }

    /**
     * @return string
     */
    protected function _toHtml(): string
    {
        $helpBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)->setData([
            'content' => $this->__(
                '<p>These Rules of automatic product adding and removal come into action when a Magento Product is
                added to the Magento Category with regard to the Store View selected for the M2E Pro Listing. In other
                words, after a Magento Product is added to the selected Magento Category, it can be automatically
                added to M2E Pro Listing if the settings are enabled.</p><br>
                <p>Please note if a product is already presented in another M2E Pro Listing with the related Channel
                account and marketplace, the Item won’t be added to the Listing to prevent listing duplicates on the
                Channel.</p><br>
                <p>Accordingly, if a Magento Product presented in the M2E Pro Listing is removed from the Magento
                Category, the Item will be removed from the Listing and its sale will be stopped on Channel.</p><br>
                <p>You should combine Magento Categories into groups to apply the Auto Add/Remove Rules. You can
                create as many groups as you need, but one Magento Category can be used only in one Rule.</p><br>
                <p>More detailed information you can find
                <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                $this->supportHelper->getDocumentationArticleUrl('x/uAMVB')
            )
        ]);

        return $helpBlock->toHtml() . parent::_toHtml();
    }
}
