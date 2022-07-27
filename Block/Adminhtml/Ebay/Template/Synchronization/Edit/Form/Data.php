<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class Data extends AbstractBlock
{
    protected $_template = 'ebay/template/synchronization/form/data.phtml';
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
    }

    protected function _prepareLayout()
    {
        $this->globalDataHelper->setValue('synchronization_form_data', $this->getFormData());

        $this->globalDataHelper->setValue('is_custom', $this->getData('is_custom'));
        $this->globalDataHelper->setValue('custom_title', $this->getData('custom_title'));

        $this->setChild('tabs', $this->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Tabs::class));

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Template\Synchronization::class)
        );
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Ebay\Template\Synchronization::class)
        );

        $this->jsTranslator->addTranslations([
            'Wrong value. Only integer numbers.' => $this->__('Wrong value. Only integer numbers.'),

            'Must be greater than "Min".' => $this->__('Must be greater than "Min".'),
            'Inconsistent Settings in Relist and Stop Rules.' => $this->__(
                'Inconsistent Settings in Relist and Stop Rules.'
            ),

            'You need to choose at set at least one time for the schedule to run.' => $this->__(
                'You need to choose at least one Time for the schedule to run.'
            ),
            'You should specify time.' => $this->__('You should specify time.'),

            'Wrong value.' => $this->__('Wrong value.'),
            'Must be greater than "Active From" Date.' => $this->__('Must be greater than "Active From" Date.'),
            'Must be greater than "From Time".' => $this->__('Must be greater than "From Time".'),
        ]);

        $this->css->add(<<<CSS
.field-advanced_filter ul.rule-param-children {
    margin-top: 1em;
}
.field-advanced_filter .rule-param .label {
    font-size: 14px;
    font-weight: 600;
}
CSS
        );

        $this->js->add(<<<JS
    require([
        'M2ePro/Ebay/Template/Synchronization'
    ], function(){
        window.EbayTemplateSynchronizationObj = new EbayTemplateSynchronization();
        EbayTemplateSynchronizationObj.initObservers();
    });
JS
        );

        return parent::_prepareLayout();
    }
}
