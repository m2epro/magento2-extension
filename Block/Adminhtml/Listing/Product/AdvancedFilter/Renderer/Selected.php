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
        $buttons = $this->wrapFilterHtmlBtn($searchBtnHtml . $resetBtnHtml);

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

        $view = __('View');
        $edit = __('Edit');
        $or = __('or');
        $addNew = __('Add New');

        $element = $this->_formFactory->create()->addField(
            'advanced_filter_list',
            self::SELECT,
            [
                'name' => 'rule_entity_id',
                'label' => __('Saved Filter'),
                'values' => $values,
                'value' => $this->selectedRuleId,
                'class' => 'advanced-filter-select',
                'after_element_html' => <<<HTML
&nbsp;
<span style="line-height: 30px;">
    <a href="javascript: void(0);" style="" onclick="ListingProductAdvancedFilterSelectObj.updateFilter();">
        {$view}&nbsp;/&nbsp;{$edit}
    </a>
    <span>{$or}</span>
    <a href="javascript: void(0);" onclick="ListingProductAdvancedFilterSelectObj.createNewFilter()">{$addNew}</a>
</span>
HTML
                ,
            ]
        );

        return sprintf('<div class="advanced-filter-select-container">%s</div>', $element->toHtml());
    }
}
