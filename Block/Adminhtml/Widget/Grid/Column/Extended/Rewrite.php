<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended;

use Magento\Backend\Block\Widget;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite
 */
class Rewrite extends \Magento\Backend\Block\Widget\Grid\Column\Extended
{
    /** @var \Ess\M2ePro\Helper\Factory $helperFactory */
    protected $helperFactory;

    //########################################

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helperFactory = $helperFactory;
    }

    //########################################

    public function getRowField(\Magento\Framework\DataObject $row)
    {
        $renderedValue = $this->getRenderer()->render($row);
        if ($this->getHtmlDecorators()) {
            $renderedValue = $this->_applyDecorators($renderedValue, $this->getHtmlDecorators());
        }

        /*
         * if column has determined callback for framing call
         * it before give away rendered value
         *
         * callback_function($renderedValue, $row, $column, $isExport)
         * should return new version of rendered value
         */
        $frameCallback = $this->getFrameCallback();
        if (is_array($frameCallback)) {
            try {
                $this->validateFrameCallback($frameCallback);
                $renderedValue = call_user_func($frameCallback, $renderedValue, $row, $this, false);
            } catch (\Exception $e) {
                $this->helperFactory->getObject('Module\Exception')->process($e);
                $msg = sprintf(
                    'An error occurred on calling %s callback. Message: %s',
                    isset($frameCallback[1]) ? $frameCallback[1] : '',
                    $e->getMessage()
                );

                $errorBlock = $this->getLayout()->createBlock(\Magento\Framework\View\Element\Messages::class);

                $errorBlock->addError($msg);

                return $errorBlock->toHtml();
            }
        }

        return $renderedValue;
    }

    /**
     * @param array $callback
     *
     * Copied from \Magento\Backend\Block\Widget\Grid\Column\Extended as method is private
     */
    private function validateFrameCallback(array $callback)
    {
        if (!is_object($callback[0]) || !$callback[0] instanceof Widget) {
            throw new \InvalidArgumentException(
                'Frame callback host must be instance of Magento\\Backend\\Block\\Widget'
            );
        }
    }

    //########################################
}
