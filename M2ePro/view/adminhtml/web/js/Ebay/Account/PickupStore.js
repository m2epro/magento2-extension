define([
    'jquery',
    'mage/apply/main',
    'M2ePro/Common'
], function (jQuery, mage) {

    window.EbayPickupStore = Class.create(Common, {

        autogenerate: false,
        geocodeObject: {},

        defaultBusinessHours: {},

        // ---------------------------------------

        initialize: function()
        {
            this.setValidationCheckRepetitionValue('M2ePro-pickup-store-location-id',
                M2ePro.translator.translate('The specified Title is already used for another In-Store Pickup. In-Store Pickup Title must be unique.'),
                'Ebay\\Account\\PickupStore', 'location_id', 'id',
                M2ePro.formData.id
            );

            jQuery.validator.addMethod('M2ePro-pickup-store-location-id-length', function(value) {
                return value.length <= 32;
            }, M2ePro.translator.translate('Max length 32 character.'));

            jQuery.validator.addMethod('M2ePro-validate-must-arrive-date', function(value, el) {
                if (el.up('#special_hours_grid') && !el.up('#special_hours_grid').visible()) {
                    return true;
                }

                return value.match('^[0-9]{4}-[0-9]{2}-[0-9]{1,2}$');
            }, M2ePro.translator.translate('Please enter a valid date.'));

            jQuery.validator.addMethod('M2ePro-pickup-store-dropdown', function(value) {
                return value != '';
            }, M2ePro.translator.translate('Select value.'));

            jQuery.validator.addMethod('M2ePro-validate-schedule-week-days', function(value, el) {

                var countOfCheckedDays = 0;

                $$('.week_day_mode').each(function(el) {
                    el.checked && countOfCheckedDays++;
                });

                return countOfCheckedDays > 0;
            }, M2ePro.translator.translate('You need to choose at set at least one time.'));

            jQuery.validator.addMethod('M2ePro-validate-selected-schedule-time', function(value, el) {

                var countUnselectedControls = 0;

                if (el.up('#special_hours_grid')) {
                    if (!el.up('#special_hours_grid').visible()) {
                        return true;
                    }
                } else {
                    if (!el.up('tr').select('.week_day_mode').shift().checked) {
                        return true;
                    }
                }

                el.up('td').select('select').each(function(el) {
                    el.value == '' && countUnselectedControls++;
                });

                return countUnselectedControls == 0;
            }, M2ePro.translator.translate('You should specify time.'));

            jQuery.validator.addMethod('M2ePro-validate-schedule-wrong-interval-time', function(value, el) {

                if (el.up('#special_hours_grid')) {
                    if (!el.up('#special_hours_grid').visible()) {
                        return true;
                    }
                } else {
                    if (!el.up('tr').select('.week_day_mode').shift().checked) {
                        return true;
                    }
                }

                var now      = new Date(),
                    fromTime = new Date(now.toDateString() + ' ' + $(el.id.replace('validator_to','from')).value),
                    toTime   = new Date(now.toDateString() + ' ' + $(el.id.replace('validator_to','to')).value);

                return (toTime - fromTime) > 0;
            }, M2ePro.translator.translate('Must be greater than "Open Time".'));

            jQuery.validator.addMethod('M2ePro-validate-max-length-128', function(value) {
                return value.length <= 128;
            }, M2ePro.translator.translate('Max length 128 character.'));

            this.validateLocation();
        },

        init: function()
        {
            this.observeFields();
        },

        // ---------------------------------------

        observeFields: function()
        {
            var self = this;

            if ($('google_map').href.indexOf('#empty') != -1) {
                $('google_map').hide();
            }

            if ($('auto_generate_field').visible()) {
                var autoGenerate = $('auto_generate');
                autoGenerate.observe('click', function() {
                    self.generateLocationId();
                });
                autoGenerate.observe('change', self.switchAutoGeneration)
                    .simulate('change');
            }

            $('country').observe('change', self.countryChange)
                .simulate('change');

            $$('.get_geocord').shift().observe('click', self.calculateLatitudeLongitude);

            $('utc_offset').observe('change', function(event) {
                if (event.target.value != '' && this.down('option').value == '') {
                    this.down('option').remove();
                }
            });

            $$('.week_day_mode').each(function(el){
                el.observe('change', self.scheduleDayModeChange)
                    .simulate('change');
            });

            $$('.week_time_select').each(function(el){
                el.observe('change', self.scheduleTimeSelectChange)
                    .simulate('change');
            });

            $$('.date_time_select').each(function(el){
                el.observe('change', self.scheduleTimeSelectChange)
                    .simulate('change');
            });

            $$('.add_date').shift().observe('click', self.addDateColumn);
            $$('.remove_date').each(function(el) {
                el.observe('click', self.removeDateColumn);
            });

            self.hideSpecialHoursGrid();

            $('default_mode')
                .observe('change', self.changeDefaultMode)
                .simulate('change');

            $('qty_mode')
                .observe('change', self.qtyModeChange)
                .simulate('change');

            $('qty_modification_mode')
                .observe('change', self.qtyPostedModeChange)
                .simulate('change');

            setTimeout(function() {
                self.initGeocodeAPI();
            }, 0);
        },

        // ---------------------------------------

        switchAutoGeneration: function(event)
        {
            var self = EbayPickupStoreObj,
                nameField = $('name');

            self.autogenerate = !self.autogenerate;

            if (!self.autogenerate) {
                nameField.stopObserving('keyup', self.generateLocationId);
            } else {
                nameField.observe('keyup', self.generateLocationId);
            }
        },

        generateLocationId: function()
        {
            var nameField = $('name'),
                locationIdFiled = $('location_id');

            if (nameField.value) {
                locationIdFiled.value = nameField.value.toLowerCase()
                    .replace(/[^a-z0-9 _]+/g, '')
                    .replace(/\s/g, '_').slice(0, 32);
            } else {
                locationIdFiled.value = '';
            }
        },

        // ---------------------------------------

        countryChange: function(event) {
            var self = EbayPickupStoreObj,
                countryCode = '';

            if (event.target.value != '' && this.down('option').value == '') {
                this.down('option').remove();
            }

            if (event.target.selectedOptions.length) {
                countryCode = event.target.value;
            }

            self.updateHiddenValue(this, $('marketplace_id'));

            new Ajax.Request(M2ePro.url.get('getRegions'), {
                method: 'post',
                parameters: {
                    country_code : countryCode
                },
                onSuccess: function(transport) {
                    var regions = transport.responseText.evalJSON(),
                        insertData,
                        defaultValue = $('region_hidden').value;

                    $('region') && $('region').remove();

                    if (!regions.length) {
                        insertData = {
                            top: new Element('input', {
                                type: 'text',
                                id: 'region',
                                name: 'region',
                                class: 'input-text M2ePro-required-when-visible',
                                value: defaultValue
                            })
                        };
                    } else {
                        insertData = {
                            top: self.renderRegions(regions, defaultValue)
                        };
                    }

                    $('region_container').up().insert(insertData)
                }
            });
        },

        renderRegions: function(regionsData, defaultValue)
        {
            var select = new Element('select', {
                id: 'region',
                name: 'region',
                class: 'M2ePro-pickup-store-dropdown'
            });

            select.observe('change', function(event) {
                if (event.target.value != '' && this.down('option').value == '') {
                    this.down('option').remove();
                }
            });

            var emptyOption = new Element('option', {value: ''});
            select.appendChild(emptyOption);

            regionsData.each(function(region) {
                var option = new Element('option', {
                    value: region.name
                });

                if (defaultValue == region.name) {
                    option.selected = true;
                }

                option.innerHTML = region.name;
                select.appendChild(option);
            });

            return select;
        },

        // ---------------------------------------

        initGeocodeAPI: function()
        {
            var src = '//maps.googleapis.com/maps/api/js?' +
                      'key=AIzaSyC2t7txYJJe10PryITwaIL8FmjdjxR9xeQ&signed_in=true&' +
                      'callback=EbayPickupStoreObj.getGeocodeObject';

            require([src]);
        },

        getGeocodeObject: function()
        {
            EbayPickupStoreObj.geocodeObject = new google.maps.Geocoder();
        },

        calculateLatitudeLongitude: function(event)
        {
            event.preventDefault();

            var self = EbayPickupStoreObj,
                address = '';

            if ($('country').selectedOptions.length) {
                address += $('country').selectedOptions[0].innerHTML;
            }

            var region = $('region').value;
            region && (address += ', ' + region);

            var city = $('city').value;
            city && (address += ', ' + city);

            var address1 = $('address_1').value;
            address1 && (address += ', ' + address1);

            self.geocodeObject.geocode({'address': address}, function(results, status) {
                if (status === google.maps.GeocoderStatus.OK) {
                    if (results.length) {
                        var geometry = results[0].geometry;
                        window.test_geo = geometry;

                        var lat = +geometry.location.lat().toFixed(3),
                            lng = +geometry.location.lng().toFixed(3);

                        $('latitude').value = lat;
                        $('longitude').value = lng;

                        $('google_map').href = 'https://www.google.com/maps/place/'+lat+','+lng;
                        $('google_map').show();
                    }
                } else {
                    self.alert('Location not found.');
                }
            });
        },

        // ---------------------------------------

        scheduleDayModeChange: function(event)
        {
            var self = EbayPickupStoreObj,
                topElement = event.target.up('tr'),
                containerFrom = topElement.down('#' + this.id.replace('mode','') + 'container_from'),
                containerTo   = topElement.down('#' + this.id.replace('mode','') + 'container_to');

            containerFrom.hide();
            containerTo.hide();

            if (this.checked) {
                if (self.defaultBusinessHours.from && self.defaultBusinessHours.to) {
                    var timeFrom = self.defaultBusinessHours.from || [],
                        timeTo = self.defaultBusinessHours.to || [];

                    topElement.down('#' + this.id.replace('mode','') + 'from_hours').value = timeFrom[0] || '';
                    topElement.down('#' + this.id.replace('mode','') + 'from_minutes').value = timeFrom[1] || '';
                    topElement.down('#' + this.id.replace('mode','') + 'from')
                        .value = (timeFrom[0] || '00')+':'+(timeFrom[1] || '00')+':00';

                    topElement.down('#' + this.id.replace('mode','') + 'to_hours').value = timeTo[0] || '';
                    topElement.down('#' + this.id.replace('mode','') + 'to_minutes').value = timeTo[1] || '';
                    topElement.down('#' + this.id.replace('mode','') + 'to')
                        .value = (timeTo[0] || '00')+':'+(timeTo[1] || '00')+':00';
                }

                containerFrom.show();
                containerTo.show();
            }
        },

        scheduleTimeSelectChange: function(event)
        {
            var topElement = event.target.up('td'),
                inputId = this.id.match('(.)*(?=_)')[0];

            var hours   = topElement.down('#' + inputId + '_hours').value,
                minutes = topElement.down('#' + inputId + '_minutes').value;

            if (hours == '' || minutes == '') {
                return;
            }

            if ((event.target.id.indexOf('week_day') != -1) && (event.target.id.indexOf('from') != -1)) {
                EbayPickupStoreObj.defaultBusinessHours.from = [hours, minutes];
            } else if((event.target.id.indexOf('week_day') != -1) && (event.target.id.indexOf('to') != -1)) {
                EbayPickupStoreObj.defaultBusinessHours.to = [hours, minutes];
            }

            topElement.up('tr').down('#'+inputId).value =  hours + ':' + minutes + ':00';
        },

        addDateColumn: function(event)
        {
            event.preventDefault();

            var self = EbayPickupStoreObj,
                template = $('special_hours_template'),
                specialHoursFieldsCount = ++($$('.date-field').length);

            if (!$('special_hours_grid').visible()) {
                self.showSpecialHoursGrid();
            }

            var newColumn = jQuery('<div>').html(template.innerHTML)[0];
            newColumn = newColumn.down('tr');

            newColumn.down('input[name^="date_validator_to_"]').name = 'date_validator_to_'
                                                                        +specialHoursFieldsCount;
            newColumn.down('input[name^="date_validator_from_"]').name = 'date_validator_from_'
                                                                         +specialHoursFieldsCount;

            $('special_hours_grid_rows').down('tbody').appendChild(newColumn);

            newColumn.down('.remove_date').observe('click', self.removeDateColumn);
            newColumn.getElementsBySelector('.date_time_select').each(function(el) {
                el.observe('change', self.scheduleTimeSelectChange)
                .simulate('change');
            });

            var parent = newColumn.down('#date').up();
            parent.innerHTML = '';
            var newElementKey = 'date-' + specialHoursFieldsCount;

            parent.insert({
                top: new Element('input', {
                    type: 'text',
                    id: newElementKey,
                    value: '',
                    name: newElementKey,
                    class: 'input-text date-field M2ePro-required-when-visible M2ePro-validate-must-arrive-date',
                    'data-mage-init': '{"calendar": ' +
                                        '{"showTime": false, "dateFormat": "yy-mm-dd", "showButtonPanel": false}' +
                                      '}',
                    onchange: 'EbayPickupStoreObj.dateOnChange(this)'
                })
            });

            mage.apply();
        },

        dateOnChange: function(el) {
            var dateContainer = el,
                dateStr = dateContainer.value,
                dateFrom = dateContainer.up('tr').down('#date_from'),
                dateTo = dateContainer.up('tr').down('#date_to');

            dateFrom.name = 'special_hours[date_settings][' + dateStr + '][open]';
            dateTo.name = 'special_hours[date_settings][' + dateStr + '][close]';
        },

        removeDateColumn: function(event)
        {
            var self = EbayPickupStoreObj;

            event.preventDefault();
            event.target.up('tr').remove();
            self.hideSpecialHoursGrid();
        },

        // ---------------------------------------

        changeDefaultMode: function()
        {
            var self = EbayPickupStoreObj;

            if (+this.value) {
                $('magento_block_ebay_account_pickup_store_form_data_quantity_custom_settings').show();

                var mode = M2ePro.formData.qty_mode;
                if (mode == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Account_PickupStore::QTY_MODE_SELLING_FORMAT_TEMPLATE')) {
                    M2ePro.formData.qty_mode = M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Account_PickupStore::QTY_MODE_PRODUCT');
                }
            } else {
                $('magento_block_ebay_account_pickup_store_form_data_quantity_custom_settings').hide();
            }
        },

        qtyModeChange: function()
        {
            var self               = EbayPickupStoreObj,

                customValueTr      = $('qty_mode_cv_tr'),
                attributeElement   = $('qty_custom_attribute'),

                maxPostedValueTr   = $('qty_modification_mode_tr'),
                maxPostedValueMode = $('qty_modification_mode');

            customValueTr.hide();
            attributeElement.value = '';

            if (isNaN(+this.value) || $('default_mode').value == '0') {
                return;
            }

             if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Account_PickupStore::QTY_MODE_NUMBER')) {
                customValueTr.show();
            } else if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Account_PickupStore::QTY_MODE_ATTRIBUTE')) {
                self.updateHiddenValue(this, attributeElement);
            }

            maxPostedValueTr.hide();
            maxPostedValueMode.value = 0;

            if (self.isMaxPostedQtyAvailable(this.value)) {

                maxPostedValueTr.show();
                maxPostedValueMode.value = 1;

                if (self.isMaxPostedQtyAvailable(M2ePro.formData.qty_mode)) {
                    maxPostedValueMode.value = M2ePro.formData.qty_modification_mode;
                }
            }

            maxPostedValueMode.simulate('change');

            self.updateQtyPercentage();
        },

        isMaxPostedQtyAvailable: function(qtyMode)
        {
            return qtyMode == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Account_PickupStore::QTY_MODE_PRODUCT') ||
                   qtyMode == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Account_PickupStore::QTY_MODE_ATTRIBUTE') ||
                   qtyMode == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Account_PickupStore::QTY_MODE_PRODUCT_FIXED');
        },

        qtyPostedModeChange: function()
        {
            var minPosterValueTr = $('qty_min_posted_value_tr'),
                maxPosterValueTr = $('qty_max_posted_value_tr');

            minPosterValueTr.hide();
            maxPosterValueTr.hide();

            if (this.value == 1) {
                minPosterValueTr.show();
                maxPosterValueTr.show();
            }
        },

        updateQtyPercentage: function()
        {
            var qtyPercentageTr = $('qty_percentage_tr');

            qtyPercentageTr.hide();

            var qtyMode = $('qty_mode').value;

            if (qtyMode == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Account_PickupStore::QTY_MODE_SINGLE') ||
                qtyMode == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Account_PickupStore::QTY_MODE_NUMBER')) {
                return;
            }

            qtyPercentageTr.show();
        },

        // ---------------------------------------

        hideSpecialHoursGrid: function()
        {
            if ($$('.date-field').length > 0) {
                return;
            }

            $('special_hours_grid').hide();

            var addDateBtn = $$('.add_date').shift();
            addDateBtn.removeClassName('right');
            addDateBtn.style.width = '';
            addDateBtn.up('div')
                      .removeClassName('add_btn_wrapper')
                      .addClassName('add_btn_wrapper_center');
        },

        showSpecialHoursGrid: function()
        {
            $('special_hours_grid').show();

            var addDateBtn = $$('.add_date').shift();
            addDateBtn.addClassName('right');
            addDateBtn.style.width = '12.5%';
            addDateBtn.up('div')
                      .removeClassName('add_btn_wrapper_center')
                      .addClassName('add_btn_wrapper');
        },

        // ---------------------------------------

        duplicateClick: function($headId, chapter_when_duplicate_text)
        {
            $$('.loading-mask').invoke('show');

            this.setValidationCheckRepetitionValue('M2ePro-pickup-store-location-id',
                M2ePro.translator.translate('The specified Title is already used for another In-Store Pickup. In-Store Pickup Title must be unique.'),
                'Ebay\\Account\\PickupStore', 'location_id', 'id',0
            );

            M2ePro.formData.id = 0;

            $('name').value = $('location_id').value = $('pickup_store_id').value =  '';
            $('location_id').disabled = false;

            if (!$('auto_generate_field').visible()) {
                $('auto_generate_field').show();
                $('auto_generate').observe('change', EbayPickupStoreObj.switchAutoGeneration)
                    .simulate('change');
            }

            $$('.page-title').each(function(o) { o.innerHTML = chapter_when_duplicate_text; });
            $$('.M2ePro_duplicate_button').each(function(o) { o.hide(); });
            $$('.M2ePro_delete_button').each(function(o) { o.hide(); });

            window.setTimeout(function() {
                $$('.loading-mask').invoke('hide')
            }, 1200);
        },

        // ---------------------------------------

        isValidForm: function()
        {
            var result = jQuery('#edit_form').valid();

            if (result) {
                $('location_id').disabled = false;
            }

            return result;
        },

        validateLocation: function()
        {
            jQuery.validator.addMethod('M2ePro-check-location', function(value) {
                var preValidation = true;
                ['country', 'region', 'city', 'address_1',
                'postal_code', 'latitude', 'longitude'].each(function(el) {
                    if (!$(el) || $(el).value == '') {
                        preValidation = false;
                    }
                });

                if (!preValidation) {
                    return true;
                }

                var checkResult = false;
                new Ajax.Request(M2ePro.url.get('validateLocation'), {
                    method: 'post',
                    asynchronous: false,
                    parameters: {
                        id: M2ePro.formData.id,
                        country: $('country').value,
                        region: $('region').value,
                        city: $('city').value,
                        address_1: $('address_1').value,
                        address_2: $('address_2').value,
                        postal_code: $('postal_code').value,
                        latitude: $('latitude').value,
                        longitude: $('longitude').value,
                        utc_offset: $('utc_offset').value
                    },
                    onSuccess: function(transport) {
                        checkResult = transport.responseText.evalJSON()['result'];
                    }
                });

                return checkResult;
            }, M2ePro.translator.translate('Same Location is already exists.'));
        }

        // ---------------------------------------
    });
});