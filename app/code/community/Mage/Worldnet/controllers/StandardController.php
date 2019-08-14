<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_Worldnet
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * WorldnetTPS Form Method Front Controller
 *
 * @category   Mage
 * @package    Mage_Worldnet
 * @name       Mage_Worldnet_StandardController
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Worldnet_StandardController extends Mage_Core_Controller_Front_Action
{
    public $isValidResponse = false;

    /**
     * Get singleton with worldnet strandard
     *
     * @return object Mage_Worldnet_Model_Standard
     */
    public function getStandard()
    {
        return Mage::getSingleton('worldnet/standard');
    }

    /**
     * Get Config model
     *
     * @return object Mage_Worldnet_Model_Config
     */
    public function getConfig()
    {
        return $this->getStandard()->getConfig();
    }

    /**
     *  Return debug flag
     *
     *  @return  boolean
     */
    public function getDebug ()
    {
        return $this->getStandard()->getDebug();
    }

    /**
     * When a customer chooses Worldnet on Checkout/Payment page
     *
     */
    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setWorldnetStandardQuoteId($session->getQuoteId());

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        $order->addStatusToHistory(
            $order->getStatus(),
            Mage::helper('worldnet')->__('Customer was redirected to WorldnetTPS')
        );
        $order->save();

        $this->getResponse()
            ->setBody($this->getLayout()
                ->createBlock('worldnet/standard_redirect')
                ->setOrder($order)
                ->toHtml());

        $session->unsQuoteId();
    }

    /**
     *  Success response from Worldnet
     *
     *  @return	  void
     */
    public function  successResponseAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getWorldnetStandardQuoteId(true));

        $wnOrderId = $this->getRequest()->ORDERID;
        $wnApprovalCode = $this->getRequest()->APPROVALCODE;
        $wnResponseCode = $this->getRequest()->RESPONSECODE;
        $wnResponseText = $this->getRequest()->RESPONSETEXT;
        $wnDateTime = $this->getRequest()->DATETIME;
        $wnHash = $this->getRequest()->HASH;

        $order = Mage::getModel('sales/order');

        Mage::log("Session Order ID : ".$session->getLastRealOrderId());
        Mage::log("Worldnet Order ID : ".$wnOrderId);
        $order->loadByIncrementId($session->getLastRealOrderId());

        if (!$order->getId()) {
            Mage::log('No order found.');
            /*
            * need to have logic when there is no order with the order id from WorldnetTPS
            */
            return false;
        }
        Mage::log('Order Found');
        if($wnResponseCode == 'A'){
            $order->addStatusToHistory(
                $order->getStatus(),
                Mage::helper('worldnet')->__('Worldnet TPS: Transaction Authorised.')
            );

	  //  $order->setStatus("complete");
            $order->sendNewOrderEmail();
            $order->getPayment()->setTransactionId($wnOrderId);

            $this->saveInvoice($order);
            $order->save();

            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($wnOrderId);
            Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
            $this->_redirect('checkout/onepage/success');
        }
        else{
            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($session->getWorldnetStandardQuoteId(true));

            if($wnResponseCode == 'R') $order->addStatusToHistory($order->getStatus(), Mage::helper('worldnet')->__("Worldnet TPS: Transaction Authorised. TRANSACTION REFERRED."));
            else $order->addStatusToHistory($order->getStatus(), Mage::helper('worldnet')->__("Worldnet TPS: Transaction Authorised. TRANSACTION DECLINED."));

            $session->setErrorMessage("Transaction was not authorised by your Issuer.<br /><br />Response Text : ".$wnResponseText."<br />Response Code :".$wnResponseCode);
            // cancel order in anyway
            $order->cancel();
            $order->save();

            $this->_redirect('worldnet/standard/failure');
        }
    }

    /**
     *  Save invoice for order
     *
     *  @param    Mage_Sales_Model_Order $order
     *  @return	  boolean Can save invoice or not
     */
    protected function saveInvoice (Mage_Sales_Model_Order $order)
    {
        if ($order->canInvoice()) {
            $invoice = $order->prepareInvoice();

            $invoice->register()->capture();
            Mage::getModel('core/resource_transaction')
               ->addObject($invoice)
               ->addObject($invoice->getOrder())
               ->save();
            return true;
        }

        return false;
    }

    /**
     *  Failure response from Worldnet
     *
     *  @return	  void
     */
    public function failureResponseAction ()
    {
        $this->preResponse();

        if (!$this->isValidResponse) {
            $this->_redirect('');
            return ;
        }

        $transactionId = $this->responseArr['VendorTxCode'];

        if ($this->getDebug()) {
            Mage::getModel('worldnet/api_debug')
                ->setResponseBody(print_r($this->responseArr,1))
                ->save();
        }

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($transactionId);

        if (!$order->getId()) {
            /**
             * need to have logic when there is no order with the order id from Worldnet
             */
            return false;
        }

        // cancel order in anyway
        $order->cancel();

        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getWorldnetStandardQuoteId(true));

        // Customer clicked CANCEL Butoon
        if ($this->responseArr['Status'] == 'ABORT') {
            $history = Mage::helper('worldnet')->__('Order '.$order->getId().' was canceled by customer');
            $redirectTo = 'checkout/cart';
        } else {
            $history = Mage::helper('worldnet')->__($this->responseArr['StatusDetail']);
            $session->setErrorMessage($this->responseArr['StatusDetail']);
            $redirectTo = 'worldnet/standard/failure';
        }

        $history = Mage::helper('worldnet')->__('Customer was returned from WorldnetTPS.') . ' ' . $history;
        $order->addStatusToHistory($order->getStatus(), $history);
        $order->save();

        $this->_redirect($redirectTo);
    }

    /**
     *  Expected GET HTTP Method
     *
     *  @return	  void
     */
    protected function preResponse ()
    {
        $responseCryptString = $this->getRequest()->crypt;

        if ($responseCryptString != '') {
            $rArr = $this->getStandard()->cryptToArray($responseCryptString);
            $ok = is_array($rArr)
                && isset($rArr['Status']) && $rArr['Status'] != ''
                && isset($rArr['VendorTxCode']) && $rArr['VendorTxCode'] != ''
                && isset($rArr['Amount']) && $rArr['Amount'] != '';

            if ($ok) {
                $this->responseArr = $rArr;
                $this->isValidResponse = true;
            }
        }
    }

    /**
     *  Failure Action
     *
     *  @return	  void
     */
    public function failureAction ()
    {
        $session = Mage::getSingleton('checkout/session');

        if (!$session->getErrorMessage()) {
            $this->_redirect('checkout/cart');
            return;
        }
        $this->_redirect('checkout/onepage/failure');
    }
}
