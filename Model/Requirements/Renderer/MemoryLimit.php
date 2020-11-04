<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Requirements\Renderer;

/**
 * @method \Ess\M2ePro\Model\Requirements\Checks\ExecutionTime getCheckObject()
 */
class MemoryLimit extends AbstractRenderer
{
    /** @var \Magento\Framework\UrlInterface */
    protected $urlBuilder;

    /** @var \Magento\Framework\View\LayoutInterface */
    protected $layout;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Requirements\Checks\AbstractCheck $checkObject,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\View\LayoutInterface $layout,
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->layout = $layout;
        parent::__construct($helperFactory, $modelFactory, $checkObject, $data);
    }

    //########################################

    public function getTitle()
    {
        return $this->getHelper('Module\Translation')->__('Memory Limit');
    }

    // ---------------------------------------

    public function getMin()
    {
        return <<<HTML
<span style="color: grey;">
      <span>{$this->getCheckObject()->getMin()}</span>&nbsp;/
      <span>{$this->getCheckObject()->getReader()->getMemoryLimitData('recommended')}</span>&nbsp;
      <span>{$this->getCheckObject()->getReader()->getMemoryLimitData('measure')}</span>
</span>
HTML;
    }

    public function getReal()
    {
        $color = $this->getCheckObject()->isMeet() ? 'green' : 'red';
        return <<<HTML
<span style="color: {$color};">
    <span>{$this->getCheckObject()->getReal()}</span>&nbsp;
    <span>{$this->getCheckObject()->getReader()->getMemoryLimitData('measure')}</span>
</span>
HTML;
    }

    public function getAdditional()
    {
        $helper = $this->getHelper('Module\Translation');
        $testUrl = $this->urlBuilder->getUrl('*/support/testMemoryLimit');
        $testResultUrl = $this->urlBuilder->getUrl('*/support/testMemoryLimitResult');
        $knowledgeBaseUrl = $this->getHelper('Module\Support')->getKnowledgeBaseUrl('1535371');

        $button = $this->layout->createBlock('Ess\M2ePro\Block\Adminhtml\Magento\Button')->setData([
            'label'   => $helper->__('Check'),
            'class'   => 'delete',
            'onclick' => "memoryLimitTest();"
        ]);

        return <<<HTML
<script>

function memoryLimitTest()
{
    new Ajax.Request('{$testUrl}', {
        method: 'post',
        asynchronous: true,
        onComplete: function(transport) {
            
            new Ajax.Request('{$testResultUrl}', {
                method: 'post',
                asynchronous: true,
                onComplete: function(transport) {
                    require(['M2ePro/Plugin/Messages'], function (MessageObj) {
                        MessageObj.clearAll();
                        var response = transport.responseText.evalJSON();
                        if (typeof response['result'] === 'undefined') {
                            MessageObj.addError('{$helper->__('Something went wrong. Please try again later.')}');
                            return;
                        }
                        
                        if (response['result'] < {$this->getCheckObject()->getMin()}) {
                            MessageObj.addWarning(
                                '{$this->getTestWarningMessage()}'
                                .replace('%value%', response['result'])
                                .replace('%url%', '{$knowledgeBaseUrl}')
                            );
                        } else {
                            MessageObj.addSuccess(
                                '{$helper->__('Actual memory limit is %value% Mb.')}'
                                .replace('%value%', response['result'])
                            );
                        }
                    });
                }
            });
        }
    });
}
</script>

{$button->toHtml()}&nbsp;
HTML;
    }

    protected function getTestWarningMessage()
    {
        return $this->getHelper('Data')->escapeJs(
            $this->getHelper('Module\Translation')->__(
                'Actual memory limit is %value% Mb. 
                The value must be increased on your server for the proper synchronization work. 
                Read <a href="%url%" target="_blank">here</a> how to do it.'
            )
        );
    }

    //########################################
}
