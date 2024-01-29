<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Listing\Product\AdvancedFilter\Renderer;

class Selected extends \Ess\M2ePro\Block\Adminhtml\Listing\Product\AdvancedFilter\AbstractRenderer
{
    /** @var int */
    private $selectedRuleId;
    /** @var bool */
    private $isRuleRecentlyCreated;
    /** @var \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Repository */
    private $repository;

    public function __construct(
        int $selectedRuleId,
        bool $isRuleRecentlyCreated,
        \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Repository $repository,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);

        $this->selectedRuleId = $selectedRuleId;
        $this->repository = $repository;
        $this->isRuleRecentlyCreated = $isRuleRecentlyCreated;
    }

    public function renderJs(
        \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsRenderer $js,
        \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsUrlRenderer $jsUrl,
        \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsTranslatorRenderer $jsTranslator
    ): void {
        if ($this->isRuleRecentlyCreated) {
            $jsTranslator->addTranslations([
                'New filter have been saved' => __('New filter has been saved'),
            ]);

            $js->add(
                <<<JS
    require([
        'M2ePro/Plugin/Messages'
    ], function(MessageObj) {
       MessageObj.addSuccess(M2ePro.translator.translate('New filter have been saved'));
    });
JS
            );
        }

        $js->addRequireJs(
            ['select' => 'M2ePro/Listing/Product/AdvancedFilter/Select'],
            <<<JS
            window.ListingProductAdvancedFilterSelectObj = new ListingProductAdvancedFilterSelect();
            ListingProductAdvancedFilterSelectObj.init();
            ListingProductAdvancedFilterSelectObj.initEvents();
JS
        );
    }

    public function renderHtml(string $searchBtnHtml, string $resetBtnHtml): string
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Button $saveFilterBtn */
        $saveFilterBtn = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class);
        $saveFilterBtn->setData([
            'label' => __('Create New Filter'),
            'class' => 'action-default scalable action-primary',
            'onclick' => 'ListingProductAdvancedFilterSelectObj.createNewFilter()',
        ]);

        $buttons = $this->wrapFilterHtmlBtn(
            $searchBtnHtml
            . $resetBtnHtml
            . $saveFilterBtn->toHtml()
        );

        return $this->getFilterSelectHtml() . $buttons;
    }

    private function getFilterSelectHtml(): string
    {
        $ruleEntity = $this->repository->getAdvancedFilter($this->selectedRuleId);
        $entities = $this->repository->findItemsByModelNick($ruleEntity->getModelNick());

        $values = [''];
        foreach ($entities as $entity) {
            $values[$entity->getId()] = $entity->getTitle();
        }

        $view = $this->__('View');
        $edit = $this->__('Edit');

        $element = $this->_formFactory->create()->addField(
            'advanced_filter_list',
            self::SELECT,
            [
                'name' => 'rule_entity_id',
                'label' => __('Apply Saved Filter'),
                'values' => $values,
                'value' => $this->selectedRuleId,
                'class' => 'advanced-filter-select',
                'after_element_html' => <<<HTML
&nbsp;
<span style="line-height: 30px;">
    <span id="edit_selling_format_template_link" style="color:#41362f">
        <a href="javascript: void(0);" style="" onclick="ListingProductAdvancedFilterSelectObj.updateFilter();">
            {$view}&nbsp;/&nbsp;{$edit}
        </a>
        <div style="width: 45px;
                    display: inline-block;
                    margin-left: -10px;
                    margin-right: 5px;
                    position: relative;
                    bottom: 5px;">
        </div>
</span>
HTML
                ,
            ]
        );

        return $element->toHtml();
    }
}
