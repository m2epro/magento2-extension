<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\PickupStore\Step\Stores;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\PickupStore\Step\Stores\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $listing;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('temp_data');

        $this->setId('ebayListingProductPickupStoreGrid');

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        $pickupStoreCollection = $this->activeRecordFactory->getObject('Ebay_Account_PickupStore')
                                                           ->getCollection();
        $pickupStoreCollection->addFieldToFilter('account_id', $this->listing->getAccountId());
        $pickupStoreCollection->addFieldToFilter('marketplace_id', $this->listing->getMarketplaceId());

        // Set collection to grid
        $this->setCollection($pickupStoreCollection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('name', [
            'header'    => $this->__('Name / Location ID'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'name',
            'escape'    => false,
            'frame_callback' => [$this, 'callbackColumnTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('location_id', [
            'header'    => $this->__('Address'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'location_id',
            'width'     => '350px',
            'sortable'  => false,
            'escape'    => true,
            'frame_callback' => [$this, 'callbackColumnLocationId'],
            'filter_condition_callback' => [$this, 'callbackFilterLocation']
        ]);

        $this->addColumn('phone', [
            'header'    => $this->__('Details'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'phone',
            'width'     => '250px',
            'sortable'  => false,
            'escape'    => true,
            'frame_callback' => [$this, 'callbackColumnDetails'],
            'filter_condition_callback' => [$this, 'callbackFilterDetails']
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // Set fake action
        // ---------------------------------------
        if ($this->getMassactionBlock()->getCount() == 0) {
            $this->getMassactionBlock()->addItem('fake', [
                'label' => '&nbsp;&nbsp;&nbsp;&nbsp;',
                'url'   => '#',
            ]);
            // Header of grid with massactions is rendering in other way, than with no massaction
            // so it causes broken layout when the actions are absent
            $this->css->add(<<<CSS
            #{$this->getId()} .admin__data-grid-header {
                display: -webkit-flex;
                display: flex;
                -webkit-flex-wrap: wrap;
                flex-wrap: wrap;
            }

            #{$this->getId()} > .admin__data-grid-header > .admin__data-grid-header-row:first-child {
                width: 20%;
                margin-top: 1.1em;
            }
            #{$this->getId()} > .admin__data-grid-header > .admin__data-grid-header-row:last-child {
                width: 79%;
            }
CSS
            );
        }
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $name = $row->getData('name');
        $locationId = $row->getData('location_id');

        $locationIdLabel = $this->__('Location ID');

        return "<div>{$name} <br/>
                    <strong>{$locationIdLabel}</strong>:&nbsp;{$locationId} <br/>
                </div>";
    }

    public function callbackColumnLocationId($value, $row, $column, $isExport)
    {
        $countryCode = $row->getData('country');
        $countries = $this->getHelper('Magento')->getCountries();

        $realCountry = $countryCode;
        foreach ($countries as $country) {
            if ($country['value'] == $countryCode) {
                $realCountry = $country['label'];
                break;
            }
        }

        $region = $row->getData('region');
        $city = $row->getData('city');
        $address1 = $row->getData('address_1');
        $address2 = $row->getData('address_2');

        $addressHtml = "{$realCountry}, {$region}, {$city}, {$address1}";
        if (!empty($address2)) {
            $addressHtml .= '<br/>' . $address2;
        }
        $addressHtml .= ', ' .$row->getData('postal_code');

        return "<div>{$addressHtml}</div>";
    }

    public function callbackColumnDetails($value, $row, $column, $isExport)
    {
        $phone = $row->getData('phone');
        $url = $row->getData('url');

        if (!empty($url)) {
            $urlPath = strpos($url, 'http') === 0 ? $url : 'http://'.$url;
            $url = "<a href='{$urlPath}' target='_blank'>{$url}</a>";
        }

        $phoneLabel = $this->__('Phone');
        return "<div><strong>{$phoneLabel}</strong>:&nbsp{$phone} <br/>{$url}</div>";
    }

    // ---------------------------------------

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            "main_table.name LIKE '%{$value}%'
            OR main_table.location_id LIKE '%{$value}%'"
        );
    }

    protected function callbackFilterLocation($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $countryCodes = [];
        $countries = $this->getHelper('Magento')->getCountries();

        foreach ($countries as $country) {
            $pos = strpos(strtolower($country['label']), strtolower($value));
            if ($pos !== false) {
                $countryCodes[] = $country['value'];
            }
        }

        $countryCodes = !empty($countryCodes) ? $countryCodes : [$value];
        $countryWhere = "country LIKE '%" . implode("%' OR country LIKE '%", $countryCodes) . "%' ";
        $collection->getSelect()->where(
            "{$countryWhere}
            OR region LIKE '%{$value}%'
            OR city LIKE '%{$value}%'
            OR address_1 LIKE '%{$value}%'
            OR address_2 LIKE '%{$value}%'
            OR postal_code LIKE '%{$value}%'"
        );
    }

    protected function callbackFilterDetails($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            "phone LIKE '%{$value}%'
            OR url LIKE '%{$value}%'"
        );
    }

    //########################################

    protected function _toHtml()
    {
        $this->css->add(<<<CSS
            #{$this->getHtmlId()}_massaction .admin__grid-massaction-form {
                display: none;
            }
            #{$this->getHtmlId()}_massaction .mass-select-wrap {
                margin-left: -24%;
            }
CSS
        );

        $this->js->addOnReadyJs(
            <<<JS
            require([
                'jquery',
                'M2ePro/Magento/Product/Grid',
                'M2ePro/Ebay/Listing/PickupStore/Step/Stores/Grid'
            ], function(jQuery){

                window.PickupStoreStoresGridObj = new MagentoProductGrid();
                PickupStoreStoresGridObj.setGridId('{$this->getJsObjectName()}');
                PickupStoreStoresGridObj.isMassActionExists = false;

                window.EbayListingPickupStoreStepStoresGridObj = new EbayListingPickupStoreStepStoresGrid();
                EbayListingPickupStoreStepStoresGridObj.gridId = '{$this->getId()}';

                jQuery(function() {
                    {$this->getJsObjectName()}.doFilter = PickupStoreStoresGridObj.setFilter;
                    {$this->getJsObjectName()}.resetFilter = PickupStoreStoresGridObj.resetFilter;
                });
            });
JS
        );

        return parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/storesStepGrid', [
            'id' => $this->listing->getId()
        ]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################
}
