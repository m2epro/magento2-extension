<?php

namespace Ess\M2ePro\Block\Adminhtml\Listing\Product;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Rule extends AbstractForm
{
    /** @var bool */
    private $isShowHideProductsOption = false;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var string */
    private $searchBtnHtml;
    /** @var string */
    private $resetBtnHtml;
    /** @var \Ess\M2ePro\Block\Adminhtml\Listing\Product\AdvancedFilter\AbstractRenderer */
    private $advancedFilterRenderer;
    /** @var \Ess\M2ePro\Block\Adminhtml\Listing\Product\AdvancedFilter\RendererFactory */
    private $rendererFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        AdvancedFilter\RendererFactory $rendererFactory,
        array $data = []
    ) {
        $this->globalDataHelper = $globalDataHelper;
        $this->rendererFactory = $rendererFactory;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingProductRule');
        // ---------------------------------------

        /** @var \Ess\M2ePro\Model\Magento\Product\Rule  $ruleModel */
        $ruleModel = $this->globalDataHelper->getValue('rule_model');
        $this->advancedFilterRenderer = $this->getRenderer($ruleModel);
    }

    public function setShowHideProductsOption($isShow = true)
    {
        $this->isShowHideProductsOption = $isShow;

        return $this;
    }

    public function isShowHideProductsOption()
    {
        return $this->isShowHideProductsOption;
    }

    public function setSearchBtnHtml(string $searchBtnHtml): void
    {
        $this->searchBtnHtml = $searchBtnHtml;
    }

    public function setResetBtnHtml(string $resetBtnHtml): void
    {
        $this->resetBtnHtml = $resetBtnHtml;
    }

    protected function _prepareLayout()
    {
        $this->css->add(
            <<<CSS

        #rule_form .field-advanced_filter .admin__field-control:first-child {
            width: calc( 100% - 30px );
        }

        .advanced-filter-fieldset {
            border-top: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
            margin-top: -12px;
            padding-top: 12px;
            margin-bottom: 1em;
            display: none;
        }

        .advanced-filter-fieldset-active {
            margin-top: 1em;
        }

        .advanced-filter-fieldset {
            clear: both;
        }

        .advanced-filter-fieldset > legend.legend {
            border-bottom: none !important;
            margin-bottom: 5px !important;
        }

        .advanced-filter-fieldset .field-advanced_filter {
            margin-bottom: 1.5em !important;
            float: left;
            min-width: 50%;
        }

        .advanced-filter-fieldset .rule-param .label {
            font-size: 14px;
            font-weight: 600;
        }

        .advanced-filter-fieldset ul.rule-param-children {
            margin-top: 1em;
        }

        .advanced-filter-fieldset .data-grid {
            overflow: hidden;
        }

        .advanced-filter-fieldset .rule-chooser {
            margin: 20px 0;
        }
CSS
        );

        $this->advancedFilterRenderer->addCss($this->css);

        return parent::_prepareLayout();
    }

    protected function _beforeToHtml()
    {
        $this->advancedFilterRenderer->renderJs(
            $this->js,
            $this->jsUrl,
            $this->jsTranslator
        );

        return parent::_beforeToHtml();
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'rule_form',
                'action' => 'javascript:void(0)',
                'method' => 'post',
                'enctype' => 'multipart/form-data',
                'onsubmit' => $this->getGridJsObjectName() . '.doFilter(event)',
            ],
        ]);

        $fieldset = $form->addFieldset(
            'listing_product_rules',
            [
                'legend' => '',
                'collapsable' => false,
                'class' => 'advanced-filter-fieldset',
            ]
        );

        $fieldset->addField(
            'advanced_filter',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->advancedFilterRenderer->renderHtml(
                    $this->searchBtnHtml,
                    $this->resetBtnHtml
                ),
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    private function getRenderer(\Ess\M2ePro\Model\Magento\Product\Rule $ruleModel): AdvancedFilter\AbstractRenderer
    {
        if (!$ruleModel->isExistsViewSate()) {
            throw new \LogicException('View state must be set');
        }

        $viewState = $ruleModel->getViewState();

        if ($viewState->isStateCreation()) {
            return $this->rendererFactory->createCreatingRenderer(
                $viewState->getViewKey(),
                $ruleModel,
                $this->getLayout()
            );
        }

        if ($viewState->isStateUpdating()) {
            return $this->rendererFactory->createUpdatingRenderer(
                $viewState->getUpdatedEntityId(),
                $viewState->getViewKey(),
                $ruleModel,
                $this->getLayout()
            );
        }

        if ($viewState->isStateSelected()) {
            return $this->rendererFactory->createSelectedRenderer(
                $viewState->getSelectedEntityId(),
                $viewState->getIsEntityRecentlyCreated(true),
                $this->getLayout()
            );
        }

        if ($viewState->isStateUnselected()) {
            return $this->rendererFactory->createUnselectedRenderer(
                $ruleModel->getNick(),
                $this->getLayout()
            );
        }

        throw new \LogicException('Unresolved View State');
    }
}
