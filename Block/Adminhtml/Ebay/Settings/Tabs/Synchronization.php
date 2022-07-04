<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs;

class Synchronization extends \Ess\M2ePro\Block\Adminhtml\Settings\Tabs\AbstractTab
{
    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $moduleConfiguration;

    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration
     * @param \Ess\M2ePro\Model\Config\Manager $config
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->config = $config;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @var int
     */
    private $inspectorMode;

    protected function _prepareForm()
    {
        // ---------------------------------------
        $instructionsMode = $this->config->getGroupValue(
            '/cron/task/ebay/listing/product/process_instructions/',
            'mode'
        );
        // ---------------------------------------

        // ---------------------------------------
        $this->inspectorMode = $this->moduleConfiguration->isEnableListingProductInspectorMode();
        // ---------------------------------------

        $form = $this->_formFactory->create([
            'data' => [
                'method' => 'post',
                'action' => $this->getUrl('*/*/save')
            ]
        ]);

        $fieldset = $form->addFieldset(
            'ebay_synchronization_templates',
            [
                'legend' => $this->__('M2E Pro Listings Synchronization'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'instructions_mode',
            self::SELECT,
            [
                'name'        => 'instructions_mode',
                'label'       => $this->__('Enabled'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes')
                ],
                'value' => $instructionsMode,
                'tooltip' => $this->__(
                    '<p>This synchronization includes import of changes made on eBay channel as well as the ability
                     to enable/disable the data synchronization managed by the Synchronization Policy Rules.</p><br>
                     <p>However, it does not exclude the ability to manually manage
                     Items in Listings using the available List, Revise, Relist or Stop Action options.</p>'
                )
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        parent::_prepareForm();
    }

    protected function _toHtml()
    {
        $js = "require([
                'M2ePro/Synchronization'
            ], function() {

            SynchronizationObj = new Synchronization();";

        $js .= '})';

        $this->js->addOnReadyJs($js);

        $this->jsTranslator->addTranslations(
            [
                'Synchronization Settings have been saved.' => 'Synchronization Settings have been saved.',
            ]
        );

        $this->jsUrl->addUrls([
            \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs::TAB_ID_SYNCHRONIZATION => $this->getUrl(
                '*/ebay_synchronization/save'
            ),
            'synch_formSubmit' => $this->getUrl('*/ebay_synchronization/save'),
            'logViewUrl' => $this->getUrl('*/ebay_synchronization_log/index', ['back'=>$this->dataHelper
                ->makeBackUrlParam('*/ebay_synchronization/index')]),
        ]);

        return parent::_toHtml();
    }

    //########################################

    protected function getGlobalNotice()
    {
        return '';
    }

    //########################################
}
