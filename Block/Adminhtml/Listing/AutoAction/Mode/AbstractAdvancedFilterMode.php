<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode;

abstract class AbstractAdvancedFilterMode extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected const GRID_BLOCK_ALIAS = 'advanced_filter_grid';

    /** @var mixed */
    protected $listing;

    public array $formData = [];
    protected \Ess\M2ePro\Helper\Data $dataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->setId('listingAutoActionModeAdvancedFilter');
    }

    protected function _prepareForm()
    {
        $this->prepareGrid();

        $form = $this->_formFactory->create();

        $containerHtml = $this->getChildHtml(self::GRID_BLOCK_ALIAS);

        $form->addField(
            'custom_listing_auto_action_mode_advanced_filter',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\CustomContainer::class,
            [
                'text' => $containerHtml,
                'field_extra_attributes' => 'style="width: 100%"',
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    abstract protected function prepareGrid(): void;

    public function getListing(): \Ess\M2ePro\Model\Listing
    {
        if ($this->listing === null) {
            $this->listing = $this->activeRecordFactory->getCachedObjectLoaded(
                'Listing',
                $this->getRequest()->getParam('listing_id')
            );
        }

        return $this->listing;
    }

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Listing::class)
        );

        return parent::_afterToHtml($html);
    }

    protected function _toHtml()
    {
        $this->css->add(
            <<<CSS
#auto_action_advanced_filter ul.rule-param-children {
    margin-top: 1em;
}

#auto_action_advanced_filter .rule-param .label {
    font-size: 14px;
    font-weight: 600;
}
CSS
        );

        $helpBlockContent = __(
            '<p>These Rules of automatic product adding and removal act based on ' .
            'defined filter conditions. When a Magento Product meets the configured conditions, it will be ' .
            'automatically added to the current M2E Pro Listing if the settings are enabled.</p><br>' .
            '<p>Please note that if a product is already presented in another M2E Pro Listing with the ' .
            'related Channel account and marketplace, the Item won\'t be added to the Listing to prevent ' .
            'listing duplicates on the Channel.</p><br>' .
            '<p>Accordingly, if a Magento Product currently present in the M2E Pro Listing no longer meets ' .
            'the configured conditions, the Item will be removed from the Listing and its sale will ' .
            'be stopped on Channel.'
        );

        $helpBlock = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)
            ->setData(['content' => $helpBlockContent]);

        return $helpBlock->toHtml()
            . '<div id="additional_autoaction_title_text" style="display: none">' . $this->getBlockTitle() . '</div>'
            . '<div id="block-content-wrapper"><div id="data_container">' . parent::_toHtml() . '</div></div>';
    }

    // ---------------------------------------

    protected function getBlockTitle(): string
    {
        return (string)__('Advanced filter');
    }
}
