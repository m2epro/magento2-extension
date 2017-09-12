<?php

namespace Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer;

use Ess\M2ePro\Block\Adminhtml\Traits;
use Ess\M2ePro\Block\Adminhtml\Magento\Renderer;

class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Action
{
    use Traits\RendererTrait;

    public function __construct(
        Renderer\CssRenderer $css,
        Renderer\JsPhpRenderer $jsPhp,
        Renderer\JsRenderer $js,
        Renderer\JsTranslatorRenderer $jsTranslatorRenderer,
        Renderer\JsUrlRenderer $jsUrlRenderer,
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        array $data = []
    )
    {
        $this->css = $css;
        $this->jsPhp = $jsPhp;
        $this->js = $js;
        $this->jsTranslator = $jsTranslatorRenderer;
        $this->jsUrl = $jsUrlRenderer;

        parent::__construct($context, $jsonEncoder, $data);
    }

    protected function _prepareLayout()
    {
        $this->js->add(<<<JS
    window.M2eProVarienGridAction = {
        execute: function (select, id) {
            if(!select.value || !select.value.isJSON()) {
                return;
            }

            var config = select.value.evalJSON();
            if (config.onclick_action) {
                var method = config.onclick_action + '(';
                if (id) {
                    method = method + id;
                }
                method = method + ')';
                eval(method);

                select.value = '';
            } else if (config.confirm) {
                CommonObj.confirm({
                    content: config.confirm,
                    actions: {
                        confirm: function () {
                            setLocation(config.href);
                        }.bind(this),
                        cancel: function () {
                            return false;
                        }
                    }
                });
            } else {
                varienGridAction.execute(select);
            }
        }
    };
JS
);

        return parent::_prepareLayout();
    }

    public function render(\Magento\Framework\DataObject $row)
    {
        $actions = $this->getColumn()->getActions();
        if (empty($actions) || !is_array($actions)) {
            return '&nbsp;';
        }

        if (sizeof($actions) == 1 && !$this->getColumn()->getNoLink()) {
            foreach ($actions as $action) {
                if (is_array($action)) {
                    return $this->_toLinkHtml($action, $row);
                }
            }
        }

        $itemId     = $row->getId();
        $field      = $this->getColumn()->getData('field');
        $groupOrder = $this->getColumn()->getGroupOrder();

        if (!empty($field)) {
            $itemId = $row->getData($field);
        }
        $itemId = (int)$itemId;

        if (!empty($groupOrder) && is_array($groupOrder)) {
            $actions = $this->sortActionsByGroupsOrder($groupOrder, $actions);
        }

        return ' <select class="admin__control-select" onchange="M2eProVarienGridAction.execute(this, '.$itemId.');">'
        . '<option value=""></option>'
        . $this->renderOptions($actions, $row)
        . '</select>';
    }

    protected function sortActionsByGroupsOrder(array $groupOrder, array $actions)
    {
        $sorted = array();

        foreach ($groupOrder as $groupId => $groupLabel) {

            $sorted[$groupId] = array(
                'label' => $groupLabel,
                'actions' => array()
            );

            foreach ($actions as $actionId => $actionData) {
                if (isset($actionData['group']) && ($actionData['group'] == $groupId)) {
                    $sorted[$groupId]['actions'][$actionId] = $actionData;
                    unset($actions[$actionId]);
                }
            }
        }

        return array_merge($sorted, $actions);
    }

    protected function renderOptions(array $actions, \Magento\Framework\DataObject $row)
    {
        $outHtml           = '';
        $notGroupedOptions = '';

        foreach ($actions as $groupId => $group) {
            if (isset($group['label']) && empty($group['actions'])) {
                continue;
            }

            if (!isset($group['label']) && !empty($group)) {
                $notGroupedOptions .= $this->_toOptionHtml($group, $row);
                continue;
            }

            $outHtml .= "<optgroup label='{$group['label']}'>";

            foreach ($group['actions'] as $actionId => $actionData) {
                $outHtml .= $this->_toOptionHtml($actionData, $row);
            }

            $outHtml .= "</optgroup>";
        }

        return $outHtml . $notGroupedOptions;
    }

    protected function _toLinkHtml($action, \Magento\Framework\DataObject $row)
    {
        $actionAttributes = new \Magento\Framework\DataObject();

        $actionCaption = '';
        $this->_transformActionData($action, $actionCaption, $row);

        if (isset($action['confirm'])) {
            $action['onclick'] = 'CommonObj.confirm({
                content: \''.addslashes($this->escapeHtml($action['confirm'])).'\',
                actions: {
                    confirm: function () {
                        setLocation(this.href);
                    }.bind(this),
                    cancel: function () {
                        return false;
                    }
                }
            }); return false;';
            unset($action['confirm']);
        }

        $actionAttributes->setData($action);
        return '<a ' . $actionAttributes->serialize() . '>' . $actionCaption . '</a>';
    }

    //########################################

    /**
     * In some causes default Magento logic in foreach method is not working.
     * In result variables located in $action['url']['params'] will not we replaced.
     *
     * @param array $action
     * @param string $actionCaption
     * @param \Magento\Framework\DataObject $row
     * @return \Magento\Backend\Block\Widget\Grid\Column\Renderer\Action
     */
    protected function _transformActionData(&$action, &$actionCaption, \Magento\Framework\DataObject $row)
    {
        if (!empty($action['url']['params']) && is_array($action['url']['params'])) {
            foreach ($action['url']['params'] as $paramKey => $paramValue) {

                if (strpos($paramValue, '$') === 0) {
                    $paramValue = str_replace('$', '', $paramValue);
                    $action['url']['params'][$paramKey] = $row->getData($paramValue);
                }
            }
        }

        return parent::_transformActionData($action, $actionCaption, $row);
    }
}
