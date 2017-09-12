<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization\Templates\Synchronization;

use Ess\M2ePro\Model\Listing\Product\Action\Configurator;

class Runner extends \Ess\M2ePro\Model\AbstractModel
{
    private $items = array();

    /** @var \Ess\M2ePro\Model\Synchronization\Lock\Item\Manager $lockItem */
    private $lockItem      = NULL;
    private $percentsStart = 0;
    private $percentsEnd   = 100;

    private $maxProductsPerStep = 10;
    private $connectorModel     = NULL;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Synchronization\Lock\Item\Manager $object
     */
    public function setLockItem(\Ess\M2ePro\Model\Synchronization\Lock\Item\Manager $object)
    {
        $this->lockItem = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Lock\Item\Manager
     */
    public function getLockItem()
    {
        return $this->lockItem;
    }

    // ---------------------------------------

    /**
     * @param int $value
     */
    public function setPercentsStart($value)
    {
        $this->percentsStart = $value;
    }

    /**
     * @return int
     */
    public function getPercentsStart()
    {
        return $this->percentsStart;
    }

    // ---------------------------------------

    /**
     * @param int $value
     */
    public function setPercentsEnd($value)
    {
        $this->percentsEnd = $value;
    }

    /**
     * @return int
     */
    public function getPercentsEnd()
    {
        return $this->percentsEnd;
    }

    // ---------------------------------------

    /**
     * @param int $value
     */
    public function setMaxProductsPerStep($value)
    {
        $this->maxProductsPerStep = $value;
    }

    /**
     * @return int
     */
    public function getMaxProductsPerStep()
    {
        return $this->maxProductsPerStep;
    }

    // ---------------------------------------

    public function setConnectorModel($value)
    {
        $this->connectorModel = $value;
    }

    public function getConnectorModel()
    {
        return $this->connectorModel;
    }

    //########################################

    /**
     * @param $product
     * @param $action
     * @param \Ess\M2ePro\Model\Listing\Product\Action\Configurator $configurator
     * @return bool
     */
    public function addProduct($product,
                               $action,
                               \Ess\M2ePro\Model\Listing\Product\Action\Configurator $configurator)
    {
        if (isset($this->items[$product->getId()])) {

            $existedItem = $this->items[$product->getId()];

            if ($existedItem['action'] == $action) {

                /** @var \Ess\M2ePro\Model\Listing\Product\Action\Configurator $existedConfigurator */
                $existedConfigurator = $existedItem['product']->getActionConfigurator();
                $existedConfigurator->mergeData($configurator);
                $existedConfigurator->mergeParams($configurator);

                return true;
            }

            do {

                if ($action == \Ess\M2ePro\Model\Listing\Product::ACTION_STOP) {
                    $this->deleteProduct($existedItem['product']);
                    break;
                }

                if ($existedItem['action'] == \Ess\M2ePro\Model\Listing\Product::ACTION_STOP) {
                    return false;
                }

                if ($action == \Ess\M2ePro\Model\Listing\Product::ACTION_LIST) {
                    $this->deleteProduct($existedItem['product']);
                    break;
                }

                if ($existedItem['action'] == \Ess\M2ePro\Model\Listing\Product::ACTION_LIST) {
                    return false;
                }

                if ($action == \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST) {
                    $this->deleteProduct($existedItem['product']);
                    break;
                }

                if ($existedItem['action'] == \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST) {
                    return false;
                }

            } while (false);
        }

        $product->setActionConfigurator($configurator);

        $this->items[$product->getId()] = array(
            'product' => $product,
            'action'  => $action,
        );

        return true;
    }

    public function deleteProduct($product)
    {
        if (isset($this->items[$product->getId()])) {
            unset($this->items[$product->getId()]);
            return true;
        }

        return false;
    }

    // ---------------------------------------

    /**
     * @param $product
     * @param $action
     * @return bool
     */
    public function isExistProductWithAction($product, $action)
    {
        return isset($this->items[$product->getId()]) &&
               $this->items[$product->getId()]['action'] == $action;
    }

    /**
     * @param $product
     * @param $action
     * @param \Ess\M2ePro\Model\Listing\Product\Action\Configurator $configurator
     * @return bool
     */
    public function isExistProductWithCoveringConfigurator($product, $action, Configurator $configurator)
    {
        if (!$this->isExistProductWithAction($product, $action)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Listing\Product\Action\Configurator $existedConfigurator */
        $existedConfigurator = $this->items[$product->getId()]['product']->getActionConfigurator();

        return $existedConfigurator->isDataConsists($configurator) &&
               $existedConfigurator->isParamsConsists($configurator);
    }

    public function resetProducts()
    {
        $this->items = array();
    }

    //########################################

    public function execute()
    {
        $this->setPercents($this->getPercentsStart());

        $actions = array(
            \Ess\M2ePro\Model\Listing\Product::ACTION_STOP,
            \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST,
            \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
            \Ess\M2ePro\Model\Listing\Product::ACTION_LIST
        );

        $results = array();

        $iteration = 0;
        $percentsForOneIteration = $this->getPercentsInterval() / count($actions);

        foreach ($actions as $action) {

            $tempResults = $this->executeAction($action,
                                                $this->getPercentsStart() + $iteration*$percentsForOneIteration,
                                                $this->getPercentsStart() + (++$iteration)*$percentsForOneIteration);

            $results = array_merge($results,$tempResults);
        }

        $this->setPercents($this->getPercentsEnd());
        return $this->getHelper('Data')->getMainStatus($results);
    }

    public function executeAction($action, $percentsFrom, $percentsTo)
    {
        $this->setPercents($percentsFrom);

        $products = $this->getActionProducts($action);
        if (empty($products)) {
            return array();
        }

        $totalProductsCount = count($products);
        $processedProductsCount = 0;

        $percentsOneProduct = ($percentsTo - $percentsFrom)/$totalProductsCount;

        $results = array();

        foreach (array_chunk($products, $this->getMaxProductsPerStep()) as $stepProducts) {

            $countString = $this->getHelper('Module\Translation')->__('%perStep% from %total% Product(s).',
                                                      count($stepProducts), $totalProductsCount);

            if (count($stepProducts) < 10) {

                $productsIds = array();
                foreach ($stepProducts as $product) {
                    $productsIds[] = $product->getProductId();
                }

                $productsIds = implode('", "',$productsIds);
                $countString = $this->getHelper('Module\Translation')->__('Product(s) with ID(s)')
                    ." \"{$productsIds}\".";
            }

            $this->setStatus($this->getActionTitle($action).
                             ' '.$countString.
                             ' '.$this->getHelper('Module\Translation')->__('Please wait...'));

            $results[] = $this->modelFactory->getObject($this->getConnectorModel())->process(
                $action, $stepProducts,
                array('status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_SYNCH)
            );

            $processedProductsCount += count($stepProducts);
            $tempPercents = $percentsFrom + ($processedProductsCount * $percentsOneProduct);

            $this->setPercents($tempPercents > $percentsTo ? $percentsTo : $tempPercents);
            $this->activate();
        }

        $this->setPercents($percentsTo);
        return $results;
    }

    private function getActionTitle($action)
    {
        $title = $this->getHelper('Module\Translation')->__('Unknown');

        switch ($action) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                $title = $this->getHelper('Module\Translation')->__('Listing');
                break;
            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
                $title = $this->getHelper('Module\Translation')->__('Relisting');
                break;
            case \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE:
                $title = $this->getHelper('Module\Translation')->__('Revising');
                break;
            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                $title = $this->getHelper('Module\Translation')->__('Stopping');
                break;
            case \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE:
                $title = $this->getHelper('Module\Translation')->__('Deleting');
                break;
        }

        return $title;
    }

    //########################################

    private function getActionProducts($action)
    {
        $resultProducts = array();

        foreach ($this->items as $item) {
            if ($item['action'] != $action) {
                continue;
            }

            $resultProducts[] = $item['product'];
        }

        return $resultProducts;
    }

    // ---------------------------------------

    private function setPercents($value)
    {
        if (!$this->getLockItem()) {
            return;
        }

        $this->getLockItem()->setPercents($value);
    }

    private function setStatus($text)
    {
        if (!$this->getLockItem()) {
            return;
        }

        $this->getLockItem()->setStatus($text);
    }

    private function activate()
    {
        if (!$this->getLockItem()) {
            return;
        }

        $this->getLockItem()->activate();
    }

    // ---------------------------------------

    private function getPercentsInterval()
    {
        return $this->getPercentsEnd() - $this->getPercentsStart();
    }

    //########################################
}