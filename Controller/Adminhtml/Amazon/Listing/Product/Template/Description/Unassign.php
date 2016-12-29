<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Description;

class Unassign extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Description
{
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');

        if (empty($productsIds)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $productsIdsTemp = $this->filterProductsForMapOrUnmapDescriptionTemplate($productsIds);

        $messages = array();

        if (count($productsIdsTemp) == 0) {
            $messages[] = array(
                'type' => 'warning',
                'text' => '<p>' . $this->__(
                        'Description Policy cannot be unassigned from some Products because they are
                     participating in the new ASIN(s)/ISBN(s) creation.') . '</p>'
            );
        } else {
            $productsIdsLocked = $this->filterLockedProducts($productsIdsTemp);

            if (count($productsIdsLocked) < count($productsIds)) {
                $messages[] = array(
                    'type' => 'warning',
                    'text' => '<p>' . $this->__(
                            'Description Policy cannot be unassigned because the Products are in Action or
                         in the process of new ASIN(s)/ISBN(s) Creation.'). '</p>'
                );
            }
        }

        if (!empty($productsIdsLocked)) {
            $messages[] = array(
                'type' => 'success',
                'text' => $this->__('Description Policy was successfully unassigned.')
            );

            $this->setDescriptionTemplateForProducts($productsIdsLocked, NULL);
            $this->runProcessorForParents($productsIdsLocked);
        }

        $this->setJsonContent([
            'messages' => $messages
        ]);

        return $this->getResult();
    }
}