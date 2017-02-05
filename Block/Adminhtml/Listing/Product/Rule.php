<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Product;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Rule extends AbstractForm
{
    protected $_isShowHideProductsOption = false;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingProductRule');
        // ---------------------------------------
    }

    //########################################

    public function setShowHideProductsOption($isShow = true)
    {
        $this->_isShowHideProductsOption = $isShow;
        return $this;
    }

    public function isShowHideProductsOption()
    {
        return $this->_isShowHideProductsOption;
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->add(<<<CSS

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

        return parent::_prepareLayout();
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id'      => 'rule_form',
                'action'  => 'javascript:void(0)',
                'method'  => 'post',
                'enctype' => 'multipart/form-data',
                'onsubmit' => $this->getGridJsObjectName() . '.doFilter(event)'
            ]
        ]);

        $fieldset = $form->addFieldset('listing_product_rules',
            [
                'legend' => '',
                'collapsable' => false,
                'class' => 'advanced-filter-fieldset'
            ]
        );

        $ruleModel = $this->getHelper('Data\GlobalData')->getValue('rule_model');
        $ruleBlock = $this->createBlock('Magento\Product\Rule')
            ->setData(['rule_model' => $ruleModel]);

        $fieldset->addField('advanced_filter',
            self::CUSTOM_CONTAINER,
            [
                'text' => $ruleBlock->toHtml()
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}