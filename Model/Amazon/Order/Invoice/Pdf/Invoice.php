<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Invoice\Pdf;

/**
 * Class Ess\M2ePro\Model\Amazon\Order\Invoice\Pdf\Invoice
 */
class Invoice extends \Ess\M2ePro\Model\Amazon\Order\Invoice\Pdf\AbstractPdf
{
    //########################################

    public function getPdf()
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        $documentData = $this->invoice->getSettings('document_data');

        $pdf = new \Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new \Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        if ($this->order->getStoreId()) {
            $this->_localeResolver->emulate($this->order->getStoreId());
            $this->_storeManager->setCurrentStore($this->order->getStoreId());
        }

        $page = $this->newPage();
        $this->y = $this->y ? $this->y : 815;

        $this->drawTitle($page, $this->getHelper('Module\Translation')->__('INVOICE'));

        $yTmp = $this->y;
        $this->drawInfoBlock($page, $this->getHelper('Module\Translation')->__('Invoice'));
        $yAfterInfoBlock = $this->y;

        $this->y = $yTmp;
        $this->insertLogo($page, $this->order->getStore());

        $this->y = $this->y > $yAfterInfoBlock ? $yAfterInfoBlock : $this->y;

        $this->drawAdresses($page);
        $this->drawOrderInfo($page);

        $page->setFillColor(new \Zend_Pdf_Color_Html('#0E0621'));
        $this->_setFontBold($page, 12);
        $page->drawText($this->getHelper('Module\Translation')->__('Invoice details'), 25, $this->y, 'UTF-8');
        $this->y -= 8;

        $page = $this->drawItemsHeader($page);

        foreach ($documentData['items'] as $item) {
            $page = $this->drawItem($item, $page);
        }

        $this->drawItemsFooter($page);

        $this->y -= 20;
        $page = $this->drawAdditionalInfo($page);
        $this->y -= 20;

        $page->setFillColor(new \Zend_Pdf_Color_Html('#0E0621'));
        $page = $this->drawLineBlocks($page, [
            [
                'lines'  => [
                    [
                        [
                            'text'      => $this->getHelper('Module\Translation')->__('Invoice Total'),
                            'feed'      => 290,
                            'font'      => 'bold',
                            'font_size' => 12
                        ],
                        [
                            'text'      => $this->getFormatedPrice($this->getDocumentTotal()),
                            'feed'      => 570,
                            'align'     => 'right',
                            'font'      => 'bold',
                            'font_size' => 12
                        ]
                    ]
                ],
                'height' => 8
            ]
        ]);

        $page = $this->drawSubtotalHeader($page);

        foreach ($documentData['items'] as $item) {
            $page = $this->drawSubtotalItem($page, $item);
        }

        $this->drawSubtotalFooter($page);

        $page->setFillColor(new \Zend_Pdf_Color_Html('#0E0621'));
        $this->y -= 20;
        $page = $this->drawLineBlocks($page, [
            [
                'lines'  => [
                    [
                        [
                            'text' => $this->getHelper('Module\Translation')->__('Total'),
                            'feed' => 290,
                            'font' => 'bold',
                            'font_size' => 14
                        ],
                        [
                            'text'  => $this->getFormatedPrice($this->getDocumentExclVatTotal()),
                            'feed'  => 470,
                            'align' => 'right',
                            'font'  => 'bold',
                            'font_size' => 14
                        ],
                        [
                            'text'  => $this->getFormatedPrice($this->getDocumentVatTotal()),
                            'feed'  => 570,
                            'align' => 'right',
                            'font'  => 'bold',
                            'font_size' => 14
                        ]
                    ],
                ],
                'height' => 20
            ]
        ]);

        if ($this->order->getStoreId()) {
            $this->_localeResolver->revert();
        }

        $this->_afterGetPdf();
        return $pdf;
    }

    //########################################
}
