<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Renderer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsRenderer
 */
class JsRenderer extends AbstractRenderer
{
    protected $js = [];

    public function add($script, $sOrder = 1)
    {
        $this->js[(string)$sOrder][] = $script;

        return $this;
    }

    /**
     * @param array $dependencies variable => module
     * @param $script
     * @return $this
     */
    public function addRequireJs(array $dependencies, $script, $sOrder = 1)
    {
        $parameters = array_keys($dependencies);
        $modules = array_values($dependencies);

        $preparedParameters = implode(',', $parameters);
        $preparedModules = implode('","', $modules);

        $this->js[(string)$sOrder][] = /** @lang JavaScript */
            <<<JS
require(["{$preparedModules}"], function({$preparedParameters}){
    {$script}
});
JS;

        return $this;
    }

    public function addOnReadyJs($script)
    {
        return $this->addRequireJs(['jQuery' => 'jquery'], "jQuery(function(){{$script}});");
    }

    /**
     * @param array $viewModels
     * @return $this
     */
    public function addKnockoutJs(array $viewModels)
    {
        $viewModelsScript = '';
        foreach ($viewModels as $alias => $viewModel) {
            $viewModelsScript .= "$alias: $alias,";
        }

        return $this->addRequireJs(
            array_merge(['ko' => 'knockout'], $viewModels),
            "var viewModels = {{$viewModelsScript}}; ko.applyBindings(viewModels);"
        );
    }

    public function render()
    {
        if (empty($this->js)) {
            return '';
        }

        ksort($this->js);

        $result = '';
        foreach ($this->js as $orderIndex => $jsS) {
            $result .= implode(PHP_EOL, array_values($jsS)) . PHP_EOL;
        }

        $this->js = [];

        return $result;
    }
}
