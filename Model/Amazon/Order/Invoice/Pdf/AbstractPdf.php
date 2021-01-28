<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Invoice\Pdf;

/**
 * Class Ess\M2ePro\Model\Amazon\Order\Invoice\Pdf\AbstractPdf
 */
abstract class AbstractPdf extends \Magento\Sales\Model\Order\Pdf\AbstractPdf
{
    protected $bottomMinY = 25;

    /** @var \Ess\M2ePro\Model\Order */
    protected $order;

    /** @var \Ess\M2ePro\Model\Amazon\Order\Invoice */
    protected $invoice;

    protected $helperFactory;
    protected $modelFactory;

    protected $countryFactory;
    protected $regionFactory;
    protected $moduleReader;

    protected $_storeManager;

    protected $_localeResolver;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Sales\Model\Order\Pdf\Config $pdfConfig,
        \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory,
        \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        array $data = []
    ) {
        $this->helperFactory = $helperFactory;
        $this->modelFactory = $modelFactory;
        $this->countryFactory = $countryFactory;
        $this->regionFactory = $regionFactory;
        $this->moduleReader = $moduleReader;

        $this->_storeManager = $storeManager;
        $this->_localeResolver = $localeResolver;

        parent::__construct(
            $paymentData,
            $string,
            $scopeConfig,
            $filesystem,
            $pdfConfig,
            $pdfTotalFactory,
            $pdfItemsFactory,
            $localeDate,
            $inlineTranslation,
            $addressRenderer,
            $data
        );
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @param \Ess\M2ePro\Model\Amazon\Order\Invoice $invoice
     */
    public function setInvocie($invoice)
    {
        $this->invoice = $invoice;
    }

    //########################################

    public function getDocumentTotal()
    {
        $documentData = $this->invoice->getSettings('document_data');

        return array_sum([
            $this->sumByField($documentData['items'], 'item-vat-incl-amount'),
            $this->sumByField($documentData['items'], 'item-promo-vat-incl-amount'),
            $this->sumByField($documentData['items'], 'shipping-vat-incl-amount'),
            $this->sumByField($documentData['items'], 'shipping-promo-vat-incl-amount'),
            $this->sumByField($documentData['items'], 'gift-wrap-vat-incl-amount'),
            $this->sumByField($documentData['items'], 'gift-promo-vat-incl-amount')
        ]);
    }

    public function getDocumentExclVatTotal()
    {
        $documentData = $this->invoice->getSettings('document_data');

        return array_sum([
            $this->sumByField($documentData['items'], 'item-vat-excl-amount'),
            $this->sumByField($documentData['items'], 'item-promo-vat-excl-amount'),
            $this->sumByField($documentData['items'], 'shipping-vat-excl-amount'),
            $this->sumByField($documentData['items'], 'shipping-promo-vat-excl-amount'),
            $this->sumByField($documentData['items'], 'gift-wrap-vat-excl-amount'),
            $this->sumByField($documentData['items'], 'gift-promo-vat-excl-amount')
        ]);
    }

    public function getDocumentVatTotal()
    {
        $documentData = $this->invoice->getSettings('document_data');

        return array_sum([
            $this->sumByField($documentData['items'], 'item-vat-amount'),
            $this->sumByField($documentData['items'], 'item-promo-vat-amount'),
            $this->sumByField($documentData['items'], 'shipping-vat-amount'),
            $this->sumByField($documentData['items'], 'shipping-promo-vat-amount'),
            $this->sumByField($documentData['items'], 'gift-wrap-vat-amount'),
            $this->sumByField($documentData['items'], 'gift-promo-vat-amount'),
        ]);
    }

    //########################################

    protected function getFormatedAddress($data, $delimiter = ', ')
    {
        return implode($delimiter, array_filter($data));
    }

    protected function getFormatedPrice($value)
    {
        return $this->modelFactory->getObject('Currency')->formatPrice(
            $this->invoice->getSetting('document_data', 'currency'),
            $value
        );
    }

    protected function getFormatedVAT($value)
    {
        return sprintf('%s%%', $value * 100);
    }

    protected function sumByField($data, $field)
    {
        $value = 0;
        foreach ($data as $item) {
            $value += $item[$field];
        }

        return $value;
    }

    //########################################

    protected function prepareColumnData(array $data, $strMaxLength)
    {
        $prepared = [];
        foreach ($data as $value) {
            if ($value !== '') {
                $text = [];
                foreach ($this->string->split($value, $strMaxLength, true, true)
                as
                $_value) {
                    $text[] = $_value;
                }
                foreach ($text as $part) {
                    $prepared[] = strip_tags(ltrim($part));
                }
            }
        }

        return $prepared;
    }

    protected function drawLabelValueData($page, array $data)
    {
        $labelText = $data['label'] . ': ';
        $valueText = $data['value'];

        $x = $data['x'];
        $y = empty($data['y']) ? $this->y : $data['y'];

        $textAlign = empty($data['align']) ? 'left' : $data['align'];
        switch ($textAlign) {
            case 'right':
                $this->_setFontBold($page, $data['font_size']);
                $x -= $this->widthForStringUsingFontSize($labelText, $page->getFont(), $page->getFontSize());

                $this->_setFontRegular($page, $data['font_size']);
                $x -= $this->widthForStringUsingFontSize($valueText, $page->getFont(), $page->getFontSize());
                break;
        }

        $this->_setFontBold($page, $data['font_size']);
        $page->drawText($labelText, $x, $y, 'UTF-8');

        $strWidth = $this->widthForStringUsingFontSize($labelText, $page->getFont(), $page->getFontSize());

        $this->_setFontRegular($page, $data['font_size']);
        $page->drawText($data['value'], $x + $strWidth, $y, 'UTF-8');

        if (empty($data['y'])) {
            $this->y -= $data['line_height'];
        }
    }

    //########################################

    protected function drawTitle($page, $title)
    {
        $this->drawLineBlocks($page, [
            [
                'lines'  => [
                    [
                        [
                            'text'      => $title,
                            'feed'      => 575,
                            'font'      => 'bold',
                            'font_size' => 10,
                            'align'     => 'right'
                        ]
                    ],
                ],
                'height' => 10
            ]
        ]);
    }

    protected function drawInfoBlock(\Zend_Pdf_Page $page, $lablesTitle)
    {
        $x = 360;

        $color = new \Zend_Pdf_Color_Html('#F5F6FA');
        $page->setFillColor($color);
        $page->setLineColor($color);
        $page->drawRectangle($x, $this->y, $x + 215, $this->y - 58);

        $page->setLineColor(new \Zend_Pdf_Color_Html('#0B151E'));
        $page->setLineWidth(0.7);
        $page->drawRectangle($x + 5, $this->y - 5, $x + 210, $this->y - 53);

        $page->setFillColor(new \Zend_Pdf_Color_Html('#0B151E'));

        $this->drawLabelValueData($page, [
            'label'     => $this->getHelper('Module\Translation')->__($lablesTitle . ' date'),
            'value'     => $this->_localeDate->formatDate(
                $this->invoice->getData('create_date'),
                \IntlDateFormatter::MEDIUM,
                false
            ),
            'x'         => $x + 20,
            'y'         => $this->y - 25,
            'font_size' => 9,
        ]);

        $this->drawLabelValueData($page, [
            'label'     => $this->getHelper('Module\Translation')->__($lablesTitle . ' #'),
            'value'     => $this->invoice->getDocumentNumber(),
            'x'         => $x + 20,
            'y'         => $this->y - 38,
            'font_size' => 9,
        ]);

        $this->y -= 90;
    }

    protected function drawAdresses($page)
    {
        $storeData = $this->_scopeConfig->getValue(
            'general/store_information',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->order->getStore()
        );
        $documentData = $this->invoice->getSettings('document_data');

        $page->setFillColor(new \Zend_Pdf_Color_Html('#0E0621'));
        $this->drawLineBlocks($page, [
            [
                'lines'  => [
                    [
                        [
                            'text'      => $this->getHelper('Module\Translation')->__('Billing address'),
                            'feed'      => 25,
                            'font'      => 'bold',
                            'font_size' => 12
                        ],
                        [
                            'text'      => $this->getHelper('Module\Translation')->__('Delivery address'),
                            'feed'      => 205,
                            'font'      => 'bold',
                            'font_size' => 12
                        ],
                        [
                            'text'      => $this->getHelper('Module\Translation')->__('Sold by'),
                            'feed'      => 395,
                            'font'      => 'bold',
                            'font_size' => 12
                        ]
                    ]
                ],
                'height' => 16
            ]
        ]);

        $country = $this->countryFactory->create()->loadByCode($storeData['country_id'])->getName();
        if (empty($country)) {
            $country = $storeData['country_id'];
        }

        $region = $this->regionFactory->create()->load($storeData['region_id'])->getName();
        if (empty($region)) {
            $region = $storeData['region_id'];
        }

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->drawLineBlocks($page, [
            [
                'lines'  => [
                    [
                        [
                            'text'      => $this->prepareColumnData([
                                $documentData['billing-address']['billing-name'],
                                $documentData['buyer-company-name'],
                                $this->getFormatedAddress([
                                    $documentData['billing-address']['bill-address-1'],
                                    $documentData['billing-address']['bill-address-2'],
                                    $documentData['billing-address']['bill-address-3'],
                                    $documentData['billing-address']['bill-city']
                                ]),
                                $this->getFormatedAddress([
                                    $documentData['billing-address']['bill-state'],
                                    $documentData['billing-address']['bill-postal-code']
                                ]),
                                $documentData['billing-address']['bill-country'],
                                $documentData['billing-address']['billing-phone-number'],
                                $documentData['buyer-vat-number'],
                            ], 35),
                            'feed'      => 25,
                            'font_size' => 8
                        ],
                        [
                            'text'      => $this->prepareColumnData([
                                $documentData['shipping-address']['recipient-name'],
                                $this->getFormatedAddress([
                                    $documentData['shipping-address']['ship-address-1'],
                                    $documentData['shipping-address']['ship-address-2'],
                                    $documentData['shipping-address']['ship-address-3'],
                                    $documentData['shipping-address']['ship-city']
                                ]),
                                $this->getFormatedAddress([
                                    $documentData['shipping-address']['ship-state'],
                                    $documentData['shipping-address']['ship-postal-code']
                                ]),
                                $documentData['shipping-address']['ship-country'],
                                $documentData['shipping-address']['ship-phone-number'],
                            ], 35),
                            'feed'      => 205,
                            'font_size' => 8
                        ],
                        [
                            'text'      => $this->prepareColumnData([
                                $storeData['name'],
                                $this->getFormatedAddress([
                                    $storeData['street_line1'],
                                    $storeData['street_line2'],
                                    $storeData['city']
                                ]),
                                $this->getFormatedAddress([
                                    $region,
                                    $storeData['postcode']
                                ]),
                                $country,
                                $this->getHelper('Module\Translation')->__('VAT Number') . ': ' .
                                $documentData['seller-vat-number']
                            ], 35),
                            'feed'      => 395,
                            'font_size' => 8
                        ]
                    ]
                ],
                'height' => 12
            ]
        ]);
        $this->y -= 20;
    }

    protected function drawOrderInfo($page)
    {
        $documentData = $this->invoice->getSettings('document_data');

        $page->setFillColor(new \Zend_Pdf_Color_Html('#0E0621'));
        $this->drawLineBlocks($page, [
            [
                'lines'  => [
                    [
                        [
                            'text'      => $this->getHelper('Module\Translation')->__('Order Information'),
                            'feed'      => 25,
                            'font'      => 'bold',
                            'font_size' => 12
                        ]
                    ]
                ],
                'height' => 16
            ]
        ]);

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->drawLineBlocks($page, [
            [
                'lines'  => [
                    [
                        [
                            'text'      => $this->prepareColumnData([
                                $this->getHelper('Module\Translation')->__('Order date') . ': ' .
                                $this->_localeDate->formatDate(
                                    $documentData['order-date'],
                                    \IntlDateFormatter::MEDIUM,
                                    false
                                ),
                                $this->getHelper('Module\Translation')->__('Order #') . ': ' .
                                $documentData['order-id']
                            ], 35),
                            'feed'      => 25,
                            'font_size' => 8
                        ]
                    ]
                ],
                'height' => 12
            ]
        ]);
        $this->y -= 20;
    }

    //########################################

    protected function drawItemsHeader(\Zend_Pdf_Page $page)
    {
        $color = new \Zend_Pdf_Color_Html('#F5F6FA');
        $page->setFillColor($color);
        $page->setLineColor($color);
        $page->drawRectangle(25, $this->y, 575, 25);

        $color = new \Zend_Pdf_Color_Html('#0B151E');
        $page->setFillColor($color);
        $page->setLineColor($color);
        $page->drawRectangle(25, $this->y, 575, $this->y - 30);
        $this->y -= 12;
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));

        //columns headers
        $lines[0][] = [
            'text'      => $this->getHelper('Module\Translation')->__('Description'),
            'feed'      => 30,
            'font'      => 'bold',
            'font_size' => 8
        ];

        $lines[0][] = [
            'text'      => $this->getHelper('Module\Translation')->__('Qty'),
            'feed'      => 240,
            'align'     => 'right',
            'font'      => 'bold',
            'font_size' => 8
        ];

        $lines[0][] = [
            'text'      => $this->getHelper('Module\Translation')->__('Unit price'),
            'feed'      => 320,
            'align'     => 'right',
            'font'      => 'bold',
            'font_size' => 8
        ];

        $lines[0][] = [
            'text'      => $this->getHelper('Module\Translation')->__('VAT rate'),
            'feed'      => 400,
            'align'     => 'right',
            'font'      => 'bold',
            'font_size' => 8
        ];

        $lines[0][] = [
            'text'      => $this->getHelper('Module\Translation')->__('Unit price'),
            'feed'      => 480,
            'align'     => 'right',
            'font'      => 'bold',
            'font_size' => 8
        ];

        $lines[0][] = [
            'text'      => $this->getHelper('Module\Translation')->__('Item subtotal'),
            'feed'      => 570,
            'align'     => 'right',
            'font'      => 'bold',
            'font_size' => 8
        ];

        $lines[1][] = [
            'text'      => $this->getHelper('Module\Translation')->__('(excl. VAT)'),
            'feed'      => 320,
            'align'     => 'right',
            'font_size' => 8
        ];

        $lines[1][] = [
            'text'      => $this->getHelper('Module\Translation')->__('(incl. VAT)'),
            'feed'      => 480,
            'align'     => 'right',
            'font_size' => 8
        ];

        $lines[1][] = [
            'text'      => $this->getHelper('Module\Translation')->__('(incl. VAT)'),
            'feed'      => 570,
            'align'     => 'right',
            'font_size' => 8
        ];

        $lineBlock = [
            'lines'  => $lines,
            'height' => 10
        ];

        $page = $this->drawLineBlocks($page, [$lineBlock]);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->y -= 8;

        return $page;
    }

    protected function drawItem($item, $page)
    {
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->y -= 8;

        $lines[0] = [
            [
                'text'      => array_merge(
                    $this->string->split($item['product-name'], 45, true, true),
                    [
                        $item['asin']
                    ]
                ),
                'feed'      => 30,
                'font_size' => 8
            ]
        ];

        $lines[0][] = [
            'text'      => $item['quantity-purchased'],
            'feed'      => 240,
            'align'     => 'right',
            'font_size' => 8
        ];

        $lines[0][] = [
            'text'      => $this->getFormatedPrice($item['item-vat-excl-amount'] / $item['quantity-purchased']),
            'feed'      => 320,
            'align'     => 'right',
            'font_size' => 8
        ];

        $lines[0][] = [
            'text'      => $this->getFormatedVAT($item['item-vat-rate']),
            'feed'      => 400,
            'align'     => 'right',
            'font_size' => 8
        ];

        $lines[0][] = [
            'text'      => $this->getFormatedPrice($item['item-vat-incl-amount'] / $item['quantity-purchased']),
            'feed'      => 480,
            'align'     => 'right',
            'font_size' => 8
        ];

        $lines[0][] = [
            'text'      => $this->getFormatedPrice($item['item-vat-incl-amount']),
            'feed'      => 570,
            'align'     => 'right',
            'font_size' => 8
        ];

        $lineBlock = [
            'lines'  => $lines,
            'height' => 10
        ];

        $page = $this->drawLineBlocks($page, [$lineBlock], ['table_header_method' => 'drawItemsHeader']);
        $this->y -= 4;

        return $page;
    }

    protected function drawItemsFooter(\Zend_Pdf_Page $page)
    {
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(1));
        $page->drawRectangle(25, $this->y, 575, 25);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
    }

    protected function drawAdditionalInfo($page)
    {
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $documentData = $this->invoice->getSettings('document_data');

        $this->drawLabelValueData($page, [
            'label'       => $this->getHelper('Module\Translation')->__('Shipping Charge'),
            'value'       => $this->getFormatedPrice($this->sumByField($documentData['items'],
                'shipping-vat-incl-amount')),
            'x'           => 570,
            'align'       => 'right',
            'font_size'   => 8,
            'line_height' => 12,
        ]);

        $this->drawLabelValueData($page, [
            'label'       => $this->getHelper('Module\Translation')->__('Gift Wrap'),
            'value'       => $this->getFormatedPrice($this->sumByField($documentData['items'],
                'gift-wrap-vat-incl-amount')),
            'x'           => 570,
            'align'       => 'right',
            'font_size'   => 8,
            'line_height' => 12,
        ]);

        $this->drawLabelValueData($page, [
            'label'       => $this->getHelper('Module\Translation')->__('Promotions'),
            'value'       => $this->getFormatedPrice(array_sum([
                $this->sumByField($documentData['items'], 'item-promo-vat-incl-amount'),
                $this->sumByField($documentData['items'], 'gift-promo-vat-incl-amount'),
                $this->sumByField($documentData['items'], 'shipping-promo-vat-incl-amount')
            ])),
            'x'           => 570,
            'align'       => 'right',
            'font_size'   => 8,
            'line_height' => 12,
        ]);

        $this->y -= 8;

        return $page;
    }

    protected function drawSubtotalHeader(\Zend_Pdf_Page $page)
    {
        $color = new \Zend_Pdf_Color_Html('#F5F6FA');
        $page->setFillColor($color);
        $page->setLineColor($color);
        $page->drawRectangle(290, $this->y, 575, 25);

        $color = new \Zend_Pdf_Color_Html('#0B151E');
        $page->setFillColor($color);
        $page->setLineColor($color);
        $page->drawRectangle(290, $this->y, 575, $this->y - 30);
        $this->y -= 12;
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));

        $lines[0][] = [
            'text'      => $this->getHelper('Module\Translation')->__('Vat rate'),
            'feed'      => 295,
            'font'      => 'bold',
            'font_size' => 8
        ];

        $lines[0][] = [
            'text'      => $this->getHelper('Module\Translation')->__('Items subtotal'),
            'feed'      => 470,
            'align'     => 'right',
            'font'      => 'bold',
            'font_size' => 8
        ];

        $lines[0][] = [
            'text'      => $this->getHelper('Module\Translation')->__('VAT subtotal'),
            'feed'      => 570,
            'align'     => 'right',
            'font'      => 'bold',
            'font_size' => 8
        ];

        $lines[1][] = [
            'text'      => $this->getHelper('Module\Translation')->__('(excl. VAT)'),
            'feed'      => 470,
            'align'     => 'right',
            'font_size' => 8
        ];

        $lineBlock = [
            'lines'  => $lines,
            'height' => 10
        ];

        $page = $this->drawLineBlocks($page, [$lineBlock]);
        $this->y -= 8;

        return $page;
    }

    protected function drawSubtotalItem(\Zend_Pdf_Page $page, $item)
    {
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->y -= 8;

        $lines[0][] = [
            'text'      => $this->getFormatedVAT($item['item-vat-rate']),
            'feed'      => 295,
            'font_size' => 8
        ];

        $lines[0][] = [
            'text'      => $this->getFormatedPrice(array_sum([
                $item['item-vat-excl-amount'],
                $item['item-promo-vat-excl-amount'],
                $item['shipping-vat-excl-amount'],
                $item['shipping-promo-vat-excl-amount'],
                $item['gift-wrap-vat-excl-amount'],
                $item['gift-promo-vat-excl-amount']
            ])),
            'feed'      => 470,
            'align'     => 'right',
            'font_size' => 8
        ];

        $lines[0][] = [
            'text'      => $this->getFormatedPrice(array_sum([
                $item['item-vat-amount'],
                $item['item-promo-vat-amount'],
                $item['shipping-vat-amount'],
                $item['shipping-promo-vat-amount'],
                $item['gift-wrap-vat-amount'],
                $item['gift-promo-vat-amount']
            ])),
            'feed'      => 570,
            'align'     => 'right',
            'font_size' => 8
        ];

        $lineBlock = [
            'lines'  => $lines,
            'height' => 10
        ];

        $page = $this->drawLineBlocks($page, [$lineBlock], ['table_header_method' => 'drawSubtotalHeader']);
        $this->y -= 4;

        return $page;
    }

    protected function drawSubtotalFooter(\Zend_Pdf_Page $page)
    {
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(1));
        $page->drawRectangle(290, $this->y, 575, 25);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
    }

    //########################################

    protected function drawLayout($page)
    {
        $height = 842;
        $with = 600;

        $color = new \Zend_Pdf_Color_Html('#0C151E');
        $page->setFillColor($color);
        $page->setLineColor($color);
        $page->drawRectangle(0, $height, $with, $height - 7);
        $page->drawRectangle(0, 0, $with, 7);
        $page->setFillColor(new \Zend_Pdf_Color_RGB(0, 0, 0));

        $this->drawCitation($page);
    }

    protected function drawCitation($page)
    {
        $documentData = $this->invoice->getSettings('document_data');
        $marketplaceCode = strtolower($this->order->getMarketplace()->getCode());

        if (empty($documentData['citation-' . $marketplaceCode])) {
            $marketplaceCode = strtolower($documentData['marketplace-id']);
        }

        if (!empty($documentData['citation-' . $marketplaceCode])) {
            $this->_setFontRegular($page, 8);

            $valueSplit = $this->string->split(
                $documentData['citation-' . $marketplaceCode], 90, true, true
            );

            $y = 10 + count($valueSplit) * 8;
            $this->bottomMinY = ($y > $this->bottomMinY) ? $y : $this->bottomMinY;
            foreach ($valueSplit as $part) {
                $textWidth = $this->widthForStringUsingFontSize($part, $page->getFont(), $page->getFontSize());
                $page->drawText($part, 300 - $textWidth / 2, $y, 'UTF-8');
                $y -= 8;
            }
        }

        return $page;
    }

    //########################################

    public function newPage(array $settings = [])
    {
        $page = $this->_getPdf()->newPage(\Zend_Pdf_Page::SIZE_A4);
        $this->_getPdf()->pages[] = $page;

        $this->drawLayout($page);
        $this->y = 815;

        if (!empty($settings['table_header_method'])) {
            $this->{$settings['table_header_method']}($page);
        }

        return $page;
    }

    protected function insertLogo(&$page, $store = null)
    {
        $this->y = $this->y ? $this->y : 815;
        $image = $this->_scopeConfig->getValue(
            'sales/identity/logo',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        if ($image) {
            $imagePath = '/sales/store/logo/' . $image;
            if ($this->_mediaDirectory->isFile($imagePath)) {
                $image = \Zend_Pdf_Image::imageWithPath($this->_mediaDirectory->getAbsolutePath($imagePath));
                $top = $this->y;
                //top border of the page
                $widthLimit = 220;
                //half of the page width
                $heightLimit = 220;
                //assuming the image is not a "skyscraper"
                $width = $image->getPixelWidth();
                $height = $image->getPixelHeight();

                //preserving aspect ratio (proportions)
                $ratio = $width / $height;
                if ($ratio > 1 && $width > $widthLimit) {
                    $width = $widthLimit;
                    $height = $width / $ratio;
                } elseif ($ratio < 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width = $height * $ratio;
                } elseif ($ratio == 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width = $widthLimit;
                }

                $y1 = $top - $height;
                $y2 = $top;
                $x1 = 25;
                $x2 = $x1 + $width;

                //coordinates after transformation are rounded by Zend
                $page->drawImage($image, $x1, $y1, $x2, $y2);

                $this->y = $y1 - 25;
            }
        }
    }

    protected function _setFontRegular($object, $size = 7)
    {
        $viewDir = $this->moduleReader->getModuleDir(
            \Magento\Framework\Module\Dir::MODULE_VIEW_DIR,
            'Ess_M2ePro'
        );

        $font = \Zend_Pdf_Font::fontWithPath(
            $viewDir . '/adminhtml/web/fonts/WorkSans/WorkSans-Regular.ttf'
        );
        $object->setFont($font, $size);
        return $font;
    }

    protected function _setFontBold($object, $size = 7)
    {
        $viewDir = $this->moduleReader->getModuleDir(
            \Magento\Framework\Module\Dir::MODULE_VIEW_DIR,
            'Ess_M2ePro'
        );

        $font = \Zend_Pdf_Font::fontWithPath(
            $viewDir . '/adminhtml/web/fonts/WorkSans/WorkSans-Bold.ttf'
        );
        $object->setFont($font, $size);
        return $font;
    }

    public function drawLineBlocks(\Zend_Pdf_Page $page, array $draw, array $pageSettings = [])
    {
        foreach ($draw as $itemsProp) {
            if (!isset($itemsProp['lines']) || !is_array($itemsProp['lines'])) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('We don\'t recognize the draw line data. Please define the "lines" array.')
                );
            }
            $lines = $itemsProp['lines'];
            $height = isset($itemsProp['height']) ? $itemsProp['height'] : 10;

            if (empty($itemsProp['shift'])) {
                $shift = 0;
                foreach ($lines as $line) {
                    $maxHeight = 0;
                    foreach ($line as $column) {
                        $lineSpacing = !empty($column['height']) ? $column['height'] : $height;
                        if (!is_array($column['text'])) {
                            $column['text'] = [$column['text']];
                        }
                        $top = 0;
                        foreach ($column['text'] as $part) {
                            $top += $lineSpacing;
                        }

                        $maxHeight = $top > $maxHeight ? $top : $maxHeight;
                    }
                    $shift += $maxHeight;
                }
                $itemsProp['shift'] = $shift;
            }

            if ($this->y - $itemsProp['shift'] < $this->bottomMinY) {
                $page = $this->newPage($pageSettings);
            }

            foreach ($lines as $line) {
                $maxHeight = 0;
                foreach ($line as $column) {
                    $font = $this->setFont($page, $column);
                    $fontSize = $column['font_size'];

                    if (!is_array($column['text'])) {
                        $column['text'] = [$column['text']];
                    }

                    $lineSpacing = !empty($column['height']) ? $column['height'] : $height;
                    $top = 0;
                    foreach ($column['text'] as $part) {
                        if ($this->y - $lineSpacing < $this->bottomMinY) {
                            $page = $this->newPage($pageSettings);
                            $font = $this->setFont($page, $column);
                            $fontSize = $column['font_size'];
                        }

                        $feed = $column['feed'];
                        $textAlign = empty($column['align']) ? 'left' : $column['align'];
                        $width = empty($column['width']) ? 0 : $column['width'];
                        switch ($textAlign) {
                            case 'right':
                                if ($width) {
                                    $feed = $this->getAlignRight($part, $feed, $width, $font, $fontSize);
                                } else {
                                    $feed = $feed - $this->widthForStringUsingFontSize($part, $font, $fontSize);
                                }
                                break;
                            case 'center':
                                if ($width) {
                                    $feed = $this->getAlignCenter($part, $feed, $width, $font, $fontSize);
                                }
                                break;
                            default:
                                break;
                        }
                        $page->drawText($part, $feed, $this->y - $top, 'UTF-8');
                        $top += $lineSpacing;
                    }

                    $maxHeight = $top > $maxHeight ? $top : $maxHeight;
                }
                $this->y -= $maxHeight;
            }
        }

        return $page;
    }

    private function setFont($page, &$column)
    {
        $fontSize = empty($column['font_size']) ? 10 : $column['font_size'];
        $column['font_size'] = $fontSize;
        if (!empty($column['font_file'])) {
            $font = \Zend_Pdf_Font::fontWithPath($column['font_file']);
            $page->setFont($font, $fontSize);
        } else {
            $fontStyle = empty($column['font']) ? 'regular' : $column['font'];
            switch ($fontStyle) {
                case 'bold':
                    $font = $this->_setFontBold($page, $fontSize);
                    break;
                case 'italic':
                    $font = $this->_setFontItalic($page, $fontSize);
                    break;
                default:
                    $font = $this->_setFontRegular($page, $fontSize);
                    break;
            }
        }

        return $font;
    }

    //########################################

    /**
     * @param $helperName
     * @param array $arguments
     * @return \Magento\Framework\App\Helper\AbstractHelper
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getHelper($helperName, array $arguments = [])
    {
        return $this->helperFactory->getObject($helperName, $arguments);
    }

    //########################################
}
