var terapeakResearchConfig = (function(){
    var self = this;
    var host = document.location.host;
    var data = {};

    var alibabaConfig = [
        {// search results page
            product_container_selector: '.item-main',
            title_element_selector: '.title',
            productid_element_selector: '',
            image_element_selector: '.image img',
            price_element_selector: '.cwrap .lwrap .attr',
            description_element_selector: ['.attr.company .ellipsis a.dot-product', '.kv-prop', '.prop'],
            terapeak_research_button_selector: '.tp-research .button',
            affiliate_id: '00000000',
            user_price_element_selector: '',
            default_report: '',
            require_subscription: false
        },
        {// product detail page
            product_container_selector: '#J-ls-content',
            title_element_selector: '.action-main .title[itemprop="name"]',
            productid_element_selector: '',
            image_element_selector: '.pic',
            price_element_selector: '.price',
            description_element_selector: ['.brand', '.btable tr:nth-child(2)', '.btable tr:nth-child(3)', '.btable tr:nth-child(4)', '.btable tr:nth-child(5)', '#J-quick-detail td'],
            terapeak_research_button_selector: '.tp-research .ui-button',
            affiliate_id: '00000000',
            user_price_element_selector: '',
            default_report: '',
            require_subscription: false
        }
    ];
    var amazonConfig = [
        {// search results page
            product_container_selector: '.s-item-container',
            title_element_selector: '.s-access-detail-page',
            productid_element_selector: '',
            image_element_selector: '.s-access-image',
            price_element_selector: '.s-price',
            description_element_selector: ['.s-access-detail-page', 'div:nth-child(2) div.a-row.a-spacing-mini', 'a-declarative'],
            terapeak_research_button_selector: '.tp-research .tp-button',
            affiliate_id: '00000000',
            user_price_element_selector: '',
            default_report: '',
            require_subscription: false
        },
        {// product detail page
            product_container_selector: '.a-container',
            title_element_selector: '#productTitle',
            productid_element_selector: '',
            image_element_selector: '#main-image-container img',
            price_element_selector: '#priceblock_ourprice',
            description_element_selector: ['#featurebullets_feature_div', '#detail_bullets_id li:nth-child(0)', '#detail_bullets_id li:nth-child(1)', '#detail_bullets_id li:nth-child(2)', '#detail_bullets_id li:nth-child(3)', '#detail_bullets_id li:nth-child(4)'],
            terapeak_research_button_selector: '.tp-research',
            affiliate_id: '00000000',
            user_price_element_selector: '',
            default_report: '',
            require_subscription: false
        }
    ];

    var craigslistConfig = [
        {
            product_container_selector: '.row',
            title_element_selector: '.hdrlnk',
            productid_element_selector: '',
            image_element_selector: '.i img',
            price_element_selector: '.l2 .price',
            description_element_selector: ['.txt'],
            terapeak_research_button_selector: '.tp-research',
            affiliate_id: '00000000',
            user_price_element_selector: '',
            default_report: '',
            require_subscription: false
        },
        {
            product_container_selector: '.body',
            title_element_selector: '.postingtitle',
            productid_element_selector: '',
            image_element_selector: '.carousel .tray img',
            price_element_selector: '',
            description_element_selector: ['#postingbody'],
            terapeak_research_button_selector: '.tp-research',
            affiliate_id: '00000000',
            user_price_element_selector: '',
            default_report: '',
            require_subscription: false
        }
    ];

    /* buttonAdder is just to insert Research buttons onto partners' search listings  */
    function buttonAdder(selector, buttonHTML) {
        var divs = document.querySelectorAll(selector);
        for (var i = 0; i < divs.length; ++i) {
            var button = document.createElement('div');
            button.setAttribute('class', 'tp-research');
            button.setAttribute('style', 'margin-top: 4px;');
            button.innerHTML = buttonHTML;
            divs[i].appendChild(button);
        }
    }

    function identifyPartner() {
        /* var _tpwidget can be set externally and will be used as ad-hoc configuration */
        _tpwidget = typeof _tpwidget == 'undefined' ? (function () { return; })() : _tpwidget;
        if(_tpwidget) {
            return _tpwidget;
        } else {
            _tpwidget = {};
        }

        if(host.indexOf('alibaba') > -1) {
            if(document.location.pathname.lastIndexOf('/product-detail') === 0) {
                /* Alibaba Product Details */
                buttonAdder('.main-inner .buttons', '<a class="tp-research-button ui-button ui-button-large" target="_blank" style="cursor: pointer; color: white; background: #00a0d7 repeating-linear-gradient(#00a0d7, #00a0d7) repeat-x;box-shadow: 0 1px 2px 0 rgba(0,0,0,.1); border: 1px solid #628193; border-radius: 2px;">Sell it on eBay</a>');
                return alibabaConfig[1];
            } else {
                /* Alibaba Search Results */
                buttonAdder('.contact', '<a class="button dot-product" target="_blank" style="background-color: #00a0d7; cursor: pointer;">Sell it on eBay</a>');
                return alibabaConfig[0];
            }
        } else if (host.indexOf('amazon') > -1) {
            if (document.location.search.indexOf("url=search-alias") >= 0) {
                /* Amazon Search Results */
                buttonAdder('.s-result-item .s-item-container', '<a class="tp-button" target="_blank" style="-webkit-border-radius: 2px; -moz-border-radius: 2px; border-radius: 2px; text-shadow: 1px 1px 30px #666666; font-family: Arial; color: #ffffff; font-size: 12px; background: #00a0d7; padding: 5px 10px 5px 10px; border: solid rgb(53, 126, 189) 1px; text-decoration: none; margin-top: 4px;" >Sell it on eBay</a>');
                return amazonConfig[0];
            } else if(document.location.pathname.indexOf('dp') >= 0) {
                /* Amazon Product Details */
                buttonAdder('#centerCol', '<a class="tp-research-button button" target="_blank" style="-webkit-appearance: none; -webkit-user-select: none; -webkit-writing-mode: horizontal-tb; align-items: flex-start; background-color: rgb(0, 160, 215); background-image: none; border-bottom-color: rgb(53, 126, 189); border-bottom-left-radius: 0px; border-bottom-right-radius: 0px; border-bottom-style: solid; border-bottom-width: 1px; border-left-color: rgb(53, 126, 189); border-left-style: solid; border-left-width: 1px; border-right-color: rgb(53, 126, 189); border-right-style: solid; border-right-width: 1px; border-top-color: rgb(53, 126, 189); border-top-left-radius: 0px; border-top-right-radius: 0px; border-top-style: solid; border-top-width: 01px; box-sizing: border-box; color: rgb(255, 255, 255); cursor: pointer; display: block; font-size: 14px; font-stretch: normal; font-style: normal; font-variant: normal; font-weight: normal; height: 32px; letter-spacing: normal; line-height: 18px; margin-bottom: 0px; margin-left: 0px; margin-right: 0px; margin-top: 0px; min-width: 50px; padding-bottom: 6px; padding-left: 12px; padding-right: 12px; padding-top: 6px; text-align: center; text-indent: 0; text-shadow: none; text-transform: none; vertical-align: middle; white-space: nowrap; width: 90px; word-spacing: 0;">Sell it on eBay</a>');
                return amazonConfig[1];
            }
        } else if(host.indexOf('craigslist') > -1) {
            if(document.location.pathname.indexOf('search') >= 0) {
                buttonAdder('.row', '<a class="tp-button" target="_blank" style="cursor: pointer; -webkit-border-radius: 2px; -moz-border-radius: 2px; border-radius: 2px; text-shadow: 1px 1px 10px #666666; font-family: Arial; color: #ffffff; font-size: 12px; background: #00a0d7; padding: 3px 5px 3px 5px; border: solid rgb(53, 126, 189) 1px; text-decoration: none; position: absolute; top: 0; right: 0" >Sell it on eBay</a>');
                return craigslistConfig[0];
            } else {
                buttonAdder('#postingbody', '<a class="tp-button" target="_blank" style="cursor: pointer; -webkit-border-radius: 2px; -moz-border-radius: 2px; border-radius: 2px; text-shadow: 1px 1px 10px #666666; font-family: Arial; color: #ffffff; font-size: 12px; background: #00a0d7; padding: 3px 5px 3px 5px; border: solid rgb(53, 126, 189) 1px; text-decoration: none;" >Sell it on eBay</a>');
                return craigslistConfig[1];
            }

        } else {
            // FIXME: this is kind of not good.
            return {};
        }
    }

    function setupTPResearchButton(selector, tpWidget) {
        var buttons = document.querySelectorAll(selector);
        for (var i = 0; i < buttons.length; ++i) {
            var item = buttons[i];
            item.onclick = tpWidget.researchButtonClick;
        }
    }

    function insertCJPixel(pid) {
        // CJAID
        var CJAID = 12158383;
        // insert element: <img src="http://www.ftjcfx.com/image-<pid>-12113679" width="1" height="1" border="0"/>
        if(pid) {
            var pixel = document.createElement('img');
            pixel.setAttribute('style', 'width=1px; height=1px;');
            pixel.setAttribute('id', 'cj-pixel');
            pixel.setAttribute('src', '//www.ftjcfx.com/image-' + pid + '-' + CJAID);
            document.getElementsByTagName('body')[0].appendChild(pixel);
        }
    }

    var showWidget = function () {};

    function initialize() {
        loadAndInitWidget(identifyPartner());
    }

    function loadAndInitWidget(config) {
        var DATA = function () {
            var self = this;
            var tpJquery;
            var dataSourceHostname = '//d27p8j5zfo994i.cloudfront.net/';
            // use a constructor to build the fingerprint on object creation
            __construct = function() {

            }();

            self.researchButtonClick = function(event) {
                //var selectorData = self.collectDataFromSelectors(event.currentTarget);

                // make a call to show the widget with data from the selectors as parameters
                self.showResearchModalDialog(event.currentTarget);
            };

            function collectDataFromSelectors(target, config) {
                var sConfig = self.config;
                if(config) {
                    sConfig = config;
                } else {
                }

                /* Validate the configuration */
                if(self.validateConfiguration(sConfig)) {
                    var container = jQuery(target).parents(sConfig.product_container_selector);
                    var title = container.find(sConfig.title_element_selector).text().trim();
                    var upc = verifyUPCData(container.find(sConfig.productid_element_selector).text().trim());
                    var imageSrc;
                    if(document.location.host.indexOf("doba.com") > -1){
                        var imageInStyle = jQuery("#product_image").attr("style").split("'");
                        if(imageInStyle.length === 3){
                            imageSrc = imageInStyle[1];
                        }
                    } else {
                        imageSrc= container.find(sConfig.image_element_selector).attr('src');
                    }
                    var price = container.find(sConfig.price_element_selector).text().trim();
                    var userPrice = container.find(sConfig.user_price_element_selector).text().trim();
                    var description = [];

                    var mapfunction = function(it) { return jQuery(this).text().trim();};
                    for (var i = 0; i < sConfig.description_element_selector.length; i++) {
                        var array = container.find(sConfig.description_element_selector[i]);
                        array = jQuery.makeArray(array.map(mapfunction));
                        description = description.concat(array);
                    }

                    return {
                        title: title,
                        query: title,
                        upc: upc,
                        imageURL: imageSrc,
                        price: price,
                        description: description,
                        pid: sConfig.affiliate_id,
                        userPrice: userPrice,
                        defaultReport: sConfig.default_report,
                        requireSubscription: sConfig.require_subscription
                    };
                } else {
                    console.log("configuration error");
                }
            }

            function verifyUPCData(upc) {
                var regexUPCAndISBN = /(\d{10}|\d{13}|\d{9}X|\d{12})|(ISBN[-]*(1[03])*[ ]*(: ){0,1})*(([0-9Xx][- ]*){13}|([0-9Xx][- ]*){10})|((upc|UPC)[-]*(1[03])*[ ]*(: ){0,1})*(([0-9Xx][- ]*){13}|([0-9Xx][- ]*){10})/;
                if( regexUPCAndISBN.test(upc)) {
                    var invalidUPCs = /((ISBN[-]*(1[03])*[ ]*(: ){0,1})*(0{10}|0{13}|0{9}[Xx]|0{12})|(ISBN[-]*(1[03])*[ ]*(: ){0,1})*(([0Xx][- ]*){13}|([0Xx][- ]*){10}))/;
                    if( ! invalidUPCs.test(upc)) {
                        return upc;
                    } else {
                        return null;
                    }
                } else {
                    return null;
                }
            }

            self.debugConfiguration = function(target, config) {
                var selectorData;
                if(config) {
                    selectorData = collectDataFromSelectors(target, config);
                } else {
                    selectorData = collectDataFromSelectors(target);
                }

                var data = {
                    a: {name: "title", value: selectorData.title},
                    b: {name: "query", value: selectorData.query},
                    c: {name: "upc", value: selectorData.upc},
                    d: {name: "image URL", value: selectorData.imageURL},
                    e: {name: "price", value: selectorData.price},
                    f: {name: "description", value: selectorData.description.join(", ")},
                    g: {name: "affiliate id", value: selectorData.pid},
                    h: {name: "user price", value: selectorData.userPrice}
                };

                console.table(data);
                return config;
            };

            function assert(condition, message) {
                if (!condition) {
                    throw message || "Assertion failed";
                }
            }

            self.validateConfiguration = function(config) {

                // this has to be an 'object'
                assert(typeof config === 'object', 'given config is not an object');
                // it has to have six specific properties
                assert(config.hasOwnProperty('product_container_selector'), 'config is missing product_container_selector property');
                assert(config.hasOwnProperty('title_element_selector'), 'config is missing title_element_selector property');
                assert(config.hasOwnProperty('productid_element_selector'), 'config is missing productid_element_selector property');
                assert(config.hasOwnProperty('image_element_selector'), 'config is missing image_element_selector property');
                assert(config.hasOwnProperty('price_element_selector'), 'config is missing price_element_selector property');
                assert(config.hasOwnProperty('description_element_selector'), 'config is missing description_element_selector property');
                assert(config.hasOwnProperty('terapeak_research_button_selector'), 'config is missing terapeak_research_button_selector property');
                assert(config.hasOwnProperty('affiliate_id'), 'config is missing affiliate_id property');
                // affiliate id cannot be an empty string
                assert(config.affiliate_id.length > 0, 'affiliate_id property must contain a correct CJ affiliate ID number');
                // 'description_element_selector' property must be an array object
                assert(Array.isArray(config.description_element_selector), 'description_element_selector is not an array');

                return true;
            };

            function validateAffiliateID(affiliateID) {
                // affiliate id cannot be an empty string
                assert(affiliateID.length > 0, 'ERROR: affiliate_ID property must contain a valid CJ affiliate ID number.');

                return true;
            }

            self.setConfig = function(config) {
                self.config = config;
            };

            self.generateContent = function(data) {
                return {
                    title: data.title.length > 90 ? ('<div class="item-title" data-toggle="tooltip" data-placement="bottom" title="' + data.title + '">' + data.title.substring(0, 87) + '...</div>') : '<div class="item-title">' + data.title + '</div>',
                    query: data.query,
                    upc: data.upc,
                    description: data.description.join("<br/>"),
                    price: data.price,
                    imageURL: data.imageURL
                };
            };

            function createIframe() {
                var iframe = document.createElement("iframe");
                iframe.setAttribute('id', 'terapeakResearchModal');
                iframe.setAttribute('name', 'terapeakResearchModal');
                iframe.setAttribute('frameborder', '0');
                iframe.setAttribute('allowtransparency', 'true');
                iframe.setAttribute('style', 'background-color: transparent; border: 0px none transparent; overflow: hidden; display: block; position: fixed; visibility: visible; margin: 0px; padding: 0px; left: 0px; top: 0px; width: 100%; height: 100%; z-index: 9999;');
                document.getElementsByTagName('body')[0].appendChild(iframe);

                var openIframe = document.getElementById('terapeakResearchModal');
                return openIframe;
            }

            function openModalAndSetListeners(openIframe) {
                jQuery(openIframe.contentWindow.document.getElementById('tp-modal-close')).on('click', function () {
                    self.hideDATA();
                });
                jQuery(openIframe.contentWindow.document.getElementById('myModal')).terapeakModal('show');
                jQuery(openIframe.contentWindow.document.getElementById('myModal')).on('hidden.bs.modal', function (e) {
                    self.hideDATA();
                });
            }

            function populateTemplate(htmlData, itemDetails, openIframe) {
                var template = htmlData;
                // inject data into the HTML template
                var defaultImageURL = "//sell.terapeak.com/framework/img/tp-sidebar-logo.png";
                var html = template.replace('ITEMDETAILS', itemDetails.description).replace('ITEMTITLE', itemDetails.title).replace('ITEMPRICE', itemDetails.price).replace('IMAGEURL', itemDetails.imageURL ? itemDetails.imageURL : defaultImageURL);
                // open new iframe
                openIframe.contentWindow.document.open();
                openIframe.contentWindow.document.write(html);
                openIframe.contentWindow.document.close();
            }

            function transformHTML(openIframe, itemDetails) {
                // arbitrary transformations of the html belong here
                if(itemDetails.price === '') {
                    jQuery(openIframe).contents().find('#item-details').find('.product-card .row .col-xs-2').find('strong').attr('style','display: none;');
                }
            }

            function setModalBackgroundStyle(openIframe) {
                openIframe.contentWindow.document.getElementsByTagName('body')[0].style.backgroundColor = 'transparent';
                openIframe.contentWindow.document.getElementsByTagName('body')[0].style.backgroundImage = '-webkit-radial-gradient(rgba(255, 255, 255, 0.3), rgba(0, 0, 0, 0.15))';
            }

            function pollingForWolfsharkData(openIframe) {
                var intervalCount = 0;
                var interval = setInterval(function () {
                    if (typeof openIframe.contentWindow.wolfshark !== 'undefined' && typeof openIframe.contentWindow.setItemData !== 'undefined') {
                        // save the data extracted from the page (the title, image url, etc) into tpItemData, which is accessible from inside the iframe.
                        openIframe.contentWindow.setItemData(self.wolfsharkData);
                        // start initial search
                        openIframe.contentWindow.wolfshark.search(self.wolfsharkData.query, self.wolfsharkData.upc, true, false);
                        openIframe.contentWindow.hideWidget = self.hideDATA;
                        clearInterval(interval);
                    } else if (intervalCount > 200) {
                        clearInterval(interval);
                        var message = "Unexpected error while loading research dialogue window";
                        alert(message);
                        self.hideDATA();
                    }
                    intervalCount++;
                }, 50);
            }

            self.showResearchModalDialog = function(target, pregenerated) {
                var data;
                var itemDetails;

                if(pregenerated) {
                    if (typeof pregenerated === "boolean") {
                        if (validateAffiliateID(target.affiliateID)) {
                            itemDetails = data = target;
                        } else {
                            console.log("ERROR: missing affiliate id.");
                        }
                    } else {
                        data = collectDataFromSelectors(target, pregenerated);
                        itemDetails = self.generateContent(data);
                    }
                } else {
                    data = collectDataFromSelectors(target);
                    itemDetails = self.generateContent(data);
                }

                // save the title, price, etc. information
                self.wolfsharkData = data;

                var openIframe = createIframe();

                jQuery.get( dataSourceHostname + "tools/terapeak-research-widget.html", function( htmlData ) {
                    populateTemplate(htmlData, itemDetails, openIframe);
                    transformHTML(openIframe, itemDetails);
                    openModalAndSetListeners(openIframe);
                    setModalBackgroundStyle(openIframe);

                    pollingForWolfsharkData(openIframe);
                });
            };

            self.startResearch = function (affiliateID) {
                self.showResearchModalDialog(
                    {
                        title: '',
                        query: '',
                        productID: '',
                        imageURL: '',
                        price: '',
                        description: 'Start your research',
                        affiliateID: affiliateID,
                        defaultReport: 'Check Results'
                    },
                    true
                );
            };

            self.hideDATA = function (){
                var openIframe = document.getElementById('terapeakResearchModal');
                openIframe.parentNode.removeChild(openIframe);
            };
        };

        window.terapeakResearchModalDialog = new DATA();

        if (typeof _tpwidget != 'undefined' && typeof _tpwidget != 'string' && typeof _tpwidget != 'boolean') {
            setupTPResearchButton(config.terapeak_research_button_selector, terapeakResearchModalDialog);
        } else {
        }

        terapeakResearchModalDialog.setConfig(config);
        // insert a CJ pixel
        insertCJPixel(config.affiliate_id);
    }

    // returned properties are public
    return {
        init: initialize,
        showTerapeakResearchModal: showWidget,
        setupButtonClickHandler: setupTPResearchButton
    };
})();

terapeakResearchConfig.init();