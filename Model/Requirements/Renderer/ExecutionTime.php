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
class ExecutionTime extends AbstractRenderer
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
        return $this->getHelper('Module\Translation')->__('Max Execution Time');
    }

    // ---------------------------------------

    public function getMin()
    {
        return <<<HTML
<span style="color: grey;">
      <span>{$this->getCheckObject()->getMin()}</span>&nbsp;/
      <span>{$this->getCheckObject()->getReader()->getExecutionTimeData('recommended')}</span>&nbsp;
      <span>{$this->getCheckObject()->getReader()->getExecutionTimeData('measure')}</span>
</span>
HTML;
    }

    public function getReal()
    {
        $helper = $this->getHelper('Module\Translation');
        $color = $this->getCheckObject()->isMeet() ? 'green' : 'red';

        if ($this->getCheckObject()->getReal() === null) {
            $url = $this->getHelper('Module_Support')->getKnowledgebaseArticleUrl('1563888');
            $html = <<<HTML
<span style="color: orange;">
    <span>{$helper->__('unknown')}</span>&nbsp;
    <a href="{$url}" target="_blank">{$helper->__('[read more]')}</a>&nbsp;
</span>
HTML;
        } elseif ($this->getCheckObject()->getReal() <= 0) {
            $html = <<<HTML
<span style="color: {$color};">
    <span>{$helper->__('unlimited')}</span>&nbsp;
</span>
HTML;
        } else {
            $html = <<<HTML
<span style="color: {$color};">
    <span>{$this->getCheckObject()->getReal()}</span>&nbsp;
    <span>{$this->getCheckObject()->getReader()->getExecutionTimeData('measure')}</span>
</span>
HTML;
        }

        return $html;
    }

    public function getAdditional()
    {
        $helper = $this->getHelper('Module\Translation');
        $testUrl = $this->urlBuilder->getUrl('*/support/testExecutionTime');
        $testResultUrl = $this->urlBuilder->getUrl('*/support/testExecutionTimeResult');
        $knowledgeBaseUrl = $this->getHelper('Module\Support')->getKnowledgeBaseUrl('1535371');

        $button = $this->layout->createBlock('Ess\M2ePro\Block\Adminhtml\Magento\Button')->setData([
            'label'   => $helper->__('Check'),
            'class'   => 'delete',
            'onclick' => "openExecutionTimeTestPopup();"
        ]);

        return <<<HTML
<script>

function executionTimeTest(seconds)
{
    seconds = parseInt(seconds);
    if (isNaN(seconds) || seconds <= 0) {
        return false;
    }
    
    jQuery('#execution_time_modal').modal('closeModal');
    
    new Ajax.Request('{$testUrl}', {
        method: 'post',
        asynchronous: true,
        parameters: { seconds: seconds },
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
                                '{$helper->__('Actual max execution time is %value% sec.')}'
                                .replace('%value%', response['result'])
                            );
                        }
                    });
                }
            });
        }
    });
}

function openExecutionTimeTestPopup()
{
    var modalBlock = $('execution_time_modal');
    modalBlock.update('{$this->getPopupHtml()}');

    var popup = jQuery(modalBlock).modal({
        title: '{$helper->__('Check execution time:')}',
        type: 'popup',
        modalClass: 'width-50',
        buttons: [
            {
                text: '{$helper->__('Check')}',
                class: 'action primary',
                click: function () {
                    executionTimeTest($('execution_time_value').value);
                }
            }
        ]
    });
    
    popup.modal('openModal');
}

function checkExecutionTimeValue(el) {
  if (Number(el.value) < Number('{$this->getCheckObject()->getMin()}')) {
        el.value = '{$this->getCheckObject()->getMin()}';
    }
}
</script>

{$button->toHtml()}&nbsp;
<div id="execution_time_modal"></div>
HTML;
    }

    protected function getPopupHtml()
    {
        $helper = $this->getHelper('Module\Translation');

        return $this->getHelper('Data')->escapeJs(<<<HTML
<div style="margin-top: 10px;">
    {$helper->__(
        'Enter the time you want to test. The minimum required value is %min% sec.<br><br>
        <strong>Note:</strong> Module interface will be unavailable during the check. 
        Synchronization processes won’t be affected.\'',
        $this->getCheckObject()->getMin()
    )}
    <br><br>
    <label>{$helper->__('Seconds')}</label>:&nbsp;
    <input type="text" id="execution_time_value" value="{$this->getCheckObject()->getMin()}" 
    onchange="return checkExecutionTimeValue(this);" />
</div>
HTML
        );
    }

    protected function getTestWarningMessage()
    {
        return $this->getHelper('Data')->escapeJs(
            $this->getHelper('Module\Translation')->__(
                'Actual max execution time is %value% sec. 
                The value must be increased on your server for the proper synchronization work. 
                Read <a href="%url%" target="_blank">here</a> how to do it.'
            )
        );
    }

    //########################################
}
