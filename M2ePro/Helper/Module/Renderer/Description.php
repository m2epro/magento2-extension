<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Renderer;

/**
 * Class \Ess\M2ePro\Helper\Module\Renderer\Description
 */
class Description extends \Ess\M2ePro\Helper\AbstractHelper
{
    const IMAGES_MODE_DEFAULT    = 0;
    /**
     * Is not supported more. Links to non eBay resources are not allowed due to eBay regulations.
     */
    const IMAGES_MODE_NEW_WINDOW = 1;
    const IMAGES_MODE_GALLERY    = 2;

    const IMAGES_QTY_ALL = 0;

    const LAYOUT_MODE_ROW    = 'row';
    const LAYOUT_MODE_COLUMN = 'column';

    protected $appEmulation;
    protected $filter;
    protected $layout;

    //########################################

    public function __construct(
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Email\Model\Template\Filter $filter,
        \Magento\Framework\View\LayoutInterface $layout,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->appEmulation = $appEmulation;
        $this->filter = $filter;
        $this->layout = $layout;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function parseTemplate($text, \Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        // Start store emulation process
        $this->appEmulation->startEnvironmentEmulation(
            $magentoProduct->getStoreId(),
            \Magento\Framework\App\Area::AREA_FRONTEND,
            true
        );
        //--

        $text = $this->insertAttributes($text, $magentoProduct);
        $text = $this->insertImages($text, $magentoProduct);
        $text = $this->insertMediaGalleries($text, $magentoProduct);

        // the CMS static block replacement i.e. {{media url=’image.jpg’}}
        $this->filter->setVariables(['product'=>$magentoProduct->getProduct()]);
        $text = $this->filter->filter($text);

        //-- Stop store emulation process
        $this->appEmulation->stopEnvironmentEmulation();
        //--

        return $text;
    }

    //########################################

    private function insertAttributes($text, \Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        preg_match_all("/#([a-z_0-9]+?)#/", $text, $matches);

        if (empty($matches[0])) {
            return $text;
        }

        $search = [];
        $replace = [];
        foreach ($matches[1] as $attributeCode) {
            $value = $magentoProduct->getAttributeValue($attributeCode);

            if (!is_array($value) && $value != '') {
                if ($attributeCode == 'description') {
                    $value = $this->normalizeDescription($value);
                } elseif ($attributeCode == 'weight') {
                    $value = (float)$value;
                } elseif (in_array($attributeCode, ['price', 'special_price'])) {
                    $value = $magentoProduct->getProduct()->getFormatedPrice();
                }
                $search[] = '#' . $attributeCode . '#';
                $replace[] = $value;
            } else {
                $search[] = '#' . $attributeCode . '#';
                $replace[] = '';
            }
        }

        $text = str_replace($search, $replace, $text);

        return $text;
    }

    private function insertImages($text, \Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        preg_match_all("/#image\[(.*?)\]#/", $text, $matches);

        if (empty($matches[0])) {
            return $text;
        }

        $mainImage     = $magentoProduct->getImage('image');
        $mainImageLink = $mainImage ? $mainImage->getUrl() : '';

        $search = [];
        $replace = [];

        foreach ($matches[0] as $key => $match) {
            $tempImageAttributes = explode(',', $matches[1][$key]);
            $realImageAttributes = [];
            for ($i=0; $i<6; $i++) {
                if (!isset($tempImageAttributes[$i])) {
                    $realImageAttributes[$i] = 0;
                } else {
                    $realImageAttributes[$i] = (int)$tempImageAttributes[$i];
                }
            }

            $tempImageLink = $mainImageLink;
            if ($realImageAttributes[5] != 0) {
                $tempImage = $magentoProduct->getGalleryImageByPosition($realImageAttributes[5]);
                $tempImageLink = empty($tempImage) ? '' : $tempImage->getUrl();
            }

            if (!in_array($realImageAttributes[3], [self::IMAGES_MODE_DEFAULT])) {
                $realImageAttributes[3] = self::IMAGES_MODE_DEFAULT;
            }

            $blockObj = $this->layout->createBlock(
                'Ess\M2ePro\Block\Adminhtml\Renderer\Description\Image'
            );

            $data = [
                'width'        => $realImageAttributes[0],
                'height'       => $realImageAttributes[1],
                'margin'       => $realImageAttributes[2],
                'linked_mode'  => $realImageAttributes[3],
                'watermark'    => $realImageAttributes[4],
                'src'          => $tempImageLink,
                'index_number' => $key
            ];
            $search[] = $match;
            $replace[] = ($tempImageLink == '')
                ? '' :
                preg_replace('/\s{2,}/', '', $blockObj->addData($data)->toHtml());
        }

        $text = str_replace($search, $replace, $text);

        return $text;
    }

    private function insertMediaGalleries($text, \Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        preg_match_all("/#media_gallery\[(.*?)\]#/", $text, $matches);

        if (empty($matches[0])) {
            return $text;
        }

        $search = [];
        $replace = [];

        foreach ($matches[0] as $key => $match) {
            $tempMediaGalleryAttributes = explode(',', $matches[1][$key]);
            $realMediaGalleryAttributes = [];
            for ($i=0; $i<8; $i++) {
                if (!isset($tempMediaGalleryAttributes[$i])) {
                    $realMediaGalleryAttributes[$i] = '';
                } else {
                    $realMediaGalleryAttributes[$i] = $tempMediaGalleryAttributes[$i];
                }
            }

            $imagesQty = (int)$realMediaGalleryAttributes[5];
            if ($imagesQty == self::IMAGES_QTY_ALL) {
                $imagesQty = $realMediaGalleryAttributes[3] == self::IMAGES_MODE_GALLERY ? 100 : 25;
            }

            $galleryImagesLinks = [];
            foreach ($magentoProduct->getGalleryImages($imagesQty) as $image) {
                if (!$image->getUrl()) {
                    continue;
                }

                $galleryImagesLinks[] = $image->getUrl();
            }

            if (empty($galleryImagesLinks)) {
                $search = $matches[0];
                $replace = '';
                break;
            }

            if (!in_array($realMediaGalleryAttributes[3], [self::IMAGES_MODE_DEFAULT, self::IMAGES_MODE_GALLERY])) {
                $realMediaGalleryAttributes[3] = self::IMAGES_MODE_GALLERY;
            }

            if (!in_array($realMediaGalleryAttributes[4], [self::LAYOUT_MODE_ROW, self::LAYOUT_MODE_COLUMN])) {
                $realMediaGalleryAttributes[4] = self::LAYOUT_MODE_ROW;
            }

            $data = [
                'width'        => (int)$realMediaGalleryAttributes[0],
                'height'       => (int)$realMediaGalleryAttributes[1],
                'margin'       => (int)$realMediaGalleryAttributes[2],
                'linked_mode'  => (int)$realMediaGalleryAttributes[3],
                'layout'       => $realMediaGalleryAttributes[4],
                'gallery_hint' => trim($realMediaGalleryAttributes[6], '"'),
                'watermark'    => (int)$realMediaGalleryAttributes[7],
                'images'       => $galleryImagesLinks,
                'index_number' => $key
            ];

            $blockObj = $this->layout->createBlock(
                'Ess\M2ePro\Block\Adminhtml\Renderer\Description\Gallery'
            );
            $tempHtml = $blockObj->addData($data)->toHtml();

            $search[] = $match;
            $replace[] = preg_replace('/\s{2,}/', '', $tempHtml);
        }

        $text = str_replace($search, $replace, $text);

        return $text;
    }

    // ---------------------------------------

    private function normalizeDescription($str)
    {
        // Trim whitespace
        if (($str = trim($str)) === '') {
            return '';
        }

        // Standardize newlines
        $str = str_replace(["\r\n", "\r"], "\n", $str);

        // Trim whitespace on each line
        $str = preg_replace('~^[ \t]+~m', '', $str);
        $str = preg_replace('~[ \t]+$~m', '', $str);

        // The following regexes only need to be executed if the string contains html
        if ($html_found = (strpos($str, '<') !== false)) {
            // Elements that should not be surrounded by p tags
            $no_p  = '(?:p|div|h[1-6r]|ul|ol|li|blockquote|d[dlt]|pre|t[dhr]|t(?:able|body|foot|head)|';
            $no_p .= 'c(?:aption|olgroup)|form|s(?:elect|tyle)|a(?:ddress|rea)|ma(?:p|th))';

            // Put at least two linebreaks before and after $no_p elements
            $str = preg_replace('~^<' . $no_p . '[^>]*+>~im', "\n$0", $str);
            $str = preg_replace('~</' . $no_p . '\s*+>$~im', "$0\n", $str);
        }

        // Do the <p> magic!
        $str = '<p>' . trim($str) . '</p>';
        $str = preg_replace('~\n{2,}~', "</p>\n\n<p>", $str);

        // The following regexes only need to be executed if the string contains html
        if ($html_found !== false) {
            // Remove p tags around $no_p elements
            $str = preg_replace('~<p>(?=</?' . $no_p . '[^>]*+>)~i', '', $str);
            $str = preg_replace('~(</?' . $no_p . '[^>]*+>)</p>~i', '$1', $str);
        }

        // Convert single linebreaks to <br/>
        $br = $this->getHelper('Module')->getConfig()->getGroupValue('/renderer/description/', 'convert_linebreaks');
        if ($br === null || (bool)(int)$br === true) {
            $str = preg_replace('~(?<!\n)\n(?!\n)~', "<br/>\n", $str);
        }

        return $str;
    }

    //########################################
}
