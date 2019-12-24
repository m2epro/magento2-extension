<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Traits;

/**
 * Trait \Ess\M2ePro\Block\Adminhtml\Traits\BlockTrait
 */
trait BlockTrait
{
    protected function getBlockClass($block)
    {
        // fix for Magento2 sniffs that forcing to use ::class
        $block = str_replace('_', '\\', $block);

        return 'Ess\M2ePro\Block\Adminhtml\\' . $block;
    }

    /**
     * @param $block
     * @param $name
     * @param $arguments
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    public function createBlock($block, $name = '', array $arguments = [])
    {
        return $this->getLayout()->createBlock($this->getBlockClass($block), $name, $arguments);
    }

    public function getHelper($helper, array $arguments = [])
    {
        return $this->helperFactory->getObject($helper, $arguments);
    }

    public function __()
    {
        return $this->getHelper('Module\Translation')->translate(func_get_args());
    }

    public function getTooltipHtml($content, $directionToRight = false)
    {
        $directionToRightClass = $directionToRight ? 'm2epro-field-tooltip-right' : '';
        return <<<HTML
<div class="m2epro-field-tooltip admin__field-tooltip {$directionToRightClass}">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content">
        {$content}
    </div>
</div>
HTML;
    }

    public function appendHelpBlock($data)
    {
        return $this->getLayout()->addBlock($this->getBlockClass('HelpBlock'), '', 'main.top')->setData($data);
    }

    /**
     * @param $block
     * @param string $name
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setPageActionsBlock($block, $name = '')
    {
        return $this->getLayout()->addBlock($this->getBlockClass($block), $name, 'page.main.actions');
    }
}
