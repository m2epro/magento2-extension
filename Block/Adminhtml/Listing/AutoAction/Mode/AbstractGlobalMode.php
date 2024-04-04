<?php

namespace Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode;

abstract class AbstractGlobalMode extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var mixed */
    protected $listing;

    /** @var array  */
    public $formData = [];

    /** @var \Ess\M2ePro\Helper\Data */
    protected $dataHelper;

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

        $this->setId('listingAutoActionModeGlobal');
        $this->formData = $this->getFormData();
    }

    public function hasFormData()
    {
        return $this->getListing()->getData('auto_mode') == \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL;
    }

    public function getFormData()
    {
        $formData = $this->getListing()->getData();
        $formData = array_merge($formData, $this->getListing()->getChildObject()->getData());
        $default = $this->getDefault();

        return array_merge($default, $formData);
    }

    public function getDefault()
    {
        return [
            'auto_global_adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_ADD,
            'auto_global_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_global_deleting_mode' => \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE,
        ];
    }

    /**
     * @return \Ess\M2ePro\Model\Listing
     * @throws \Exception
     */
    public function getListing()
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

        $hasFormData = $this->hasFormData() ? 'true' : 'false';

        $this->js->add(
            <<<JS
        $('auto_global_adding_mode')
            .observe('change', ListingAutoActionObj.addingModeChange)
            .simulate('change');

        if ({$hasFormData}) {
            $('global_reset_button').show();
        }
JS
        );

        return parent::_afterToHtml($html);
    }

    protected function _toHtml()
    {
        return '<div id="additional_autoaction_title_text" style="display: none">' . $this->getBlockTitle() . '</div>'
            . '<div id="block-content-wrapper"><div id="data_container">' . parent::_toHtml() . '</div></div>';
    }

    // ---------------------------------------

    protected function getBlockTitle()
    {
        return $this->__('Global all Products');
    }
}
