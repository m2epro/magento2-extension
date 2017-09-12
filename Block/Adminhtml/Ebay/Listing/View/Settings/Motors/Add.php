<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors;

class Add extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    private $motorsType = null;

    private $productGridId = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('ebay/listing/view/settings/motors/add.phtml');
    }

    protected function _beforeToHtml()
    {
        if (is_null($this->motorsType)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Compatibility type was not set.');
        }

        //------------------------------
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Tabs $tabsBlock */
        $tabsBlock = $this->createBlock('Ebay\Listing\View\Settings\Motors\Add\Tabs');
        $tabsBlock->setMotorsType($this->getMotorsType());
        $this->setChild('motor_add_tabs', $tabsBlock);
        //------------------------------

        //------------------------------
        $data = [
            'id' => 'motors_add',
            'style' => 'margin-right: 5px',
            'label'   => $this->__('Add'),
            'class' => 'action-primary disabled',
            'onclick' => 'EbayListingViewSettingsMotorsObj.updateMotorsData(0);'
        ];
        $closeBtn = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('motor_add_btn', $closeBtn);
        //------------------------------

        //------------------------------
        $data = [
            'id' => 'motors_override',
            'label'   => $this->__('Override'),
            'class' => 'action-primary disabled',
            'onclick' => 'EbayListingViewSettingsMotorsObj.updateMotorsData(1);'
        ];
        $closeBtn = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('motor_override_btn', $closeBtn);
        //------------------------------

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Ebay\Motors')
        );

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Ebay\Motor\Group')
        );

        $this->jsTranslator->addTranslations([
            'Add Custom Compatible Vehicle' => $this->__('Add Custom Compatible Vehicle')
        ]);

        $this->js->add(<<<JS

    CommonObj.setValidationCheckRepetitionValue('M2ePro-filter-title',
        '{$this->__('The specified Title is already used for other Filter. Filter Title must be unique.')}',
        'Ebay\\\Motor\\\\Filter', 'title', 'id', null, null, 'type', {$this->getMotorsType()}
    );

    CommonObj.setValidationCheckRepetitionValue('M2ePro-group-title',
        '{$this->__('The specified Title is already used for other Group. Group Title must be unique.')}',
        'Ebay\\\\Motor\\\\Group', 'title', 'id', null, null, 'type', {$this->getMotorsType()}
    );

JS
        );

        return parent::_beforeToHtml();
    }

    //########################################

    public function setMotorsType($type)
    {
        $this->motorsType = $type;
        return $this;
    }

    public function getMotorsType()
    {
        return $this->motorsType;
    }

    // ---------------------------------------

    public function setProductGridId($gridId)
    {
        $this->productGridId = $gridId;
        return $this;
    }

    public function getProductGridId()
    {
        return $this->productGridId;
    }

    // ---------------------------------------

    public function isMotorsTypeKtype()
    {
        return $this->getHelper('Component\Ebay\Motors')->isTypeBasedOnKtypes($this->getMotorsType());
    }

    public function isMotorsTypeEpid()
    {
        return $this->getHelper('Component\Ebay\Motors')->isTypeBasedOnEpids($this->getMotorsType());
    }

    // Add Custom Compatible Vehicle
    //########################################

    public function getRecordColumns()
    {
        return $this->isMotorsTypeKtype() ? $this->getKtypeRecordColumns()
                                          : $this->getEpidRecordColumns();
    }

    private function getEpidRecordColumns()
    {
        return [
            [
                'name'        => 'epid',
                'title'       => 'ePID',
                'is_required' => true
            ],
            [
                'name'        => 'product_type',
                'title'       => 'Type',
                'is_required' => true,
                'options'     => [
                    \Ess\M2ePro\Helper\Component\Ebay\Motors::PRODUCT_TYPE_VEHICLE => $this->__('Car / Truck'),
                    \Ess\M2ePro\Helper\Component\Ebay\Motors::PRODUCT_TYPE_MOTORCYCLE => $this->__('Motorcycle'),
                    \Ess\M2ePro\Helper\Component\Ebay\Motors::PRODUCT_TYPE_ATV => $this->__('ATV / Snowmobiles'),
                ]
            ],
            [
                'name'        => 'make',
                'title'       => 'Make',
                'is_required' => true
            ],
            [
                'name'        => 'model',
                'title'       => 'Model',
                'is_required' => true
            ],
            [
                'name'        => 'submodel',
                'title'       => 'Submodel',
                'is_required' => false
            ],
            [
                'name'        => 'year',
                'title'       => 'Year',
                'is_required' => true,
                'type'        => 'numeric'
            ],
            [
                'name'        => 'trim',
                'title'       => 'Trim',
                'is_required' => false
            ],
            [
                'name'        => 'engine',
                'title'       => 'Engine',
                'is_required' => false
            ],
        ];
    }

    private function getKtypeRecordColumns()
    {
        return [
            [
                'name'        => 'ktype',
                'title'       => 'kType',
                'is_required' => true
            ],
            [
                'name'        => 'make',
                'title'       => 'Make',
                'is_required' => false
            ],
            [
                'name'        => 'model',
                'title'       => 'Model',
                'is_required' => false
            ],
            [
                'name'        => 'variant',
                'title'       => 'Variant',
                'is_required' => false
            ],
            [
                'name'        => 'body_style',
                'title'       => 'Body Style',
                'is_required' => false
            ],
            [
                'name'        => 'type',
                'title'       => 'Type',
                'is_required' => false
            ],
            [
                'name'        => 'from_year',
                'title'       => 'Year From',
                'is_required' => false,
                'type'        => 'numeric'
            ],
            [
                'name'        => 'to_year',
                'title'       => 'Year To',
                'is_required' => false,
                'type'        => 'numeric'
            ],
            [
                'name'        => 'engine',
                'title'       => 'Engine',
                'is_required' => false
            ]
        ];
    }

    //########################################
}