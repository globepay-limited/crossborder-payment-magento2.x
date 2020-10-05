<?php
/**
 * Copyright © 2016 Globeweb. All rights reserved.
 * See more information at http://www.hellomagento2.com
 */
namespace Globeweb\Globepayali\Controller\Direct;

use Magento\Framework\App\Action\Action;
use Globeweb\Globepayali\Model\Globepayali as GlobepayaliCode;
use Magento\Payment\Helper\Data as PaymentHelper;

/**
 * Class Notify
 */
class Success extends Action
{

     /**
     * @var \Globeweb\Globepayali\Model\Api\RedirectApi
     */
   // protected $_api;

    /**
     * @var \Globeweb\DeviceDetect\Model\DeviceDetect
     */
   // protected $_detect;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    protected $currency;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $_checkoutData;
    /**
     *
     * @var \Magento\Sales\Model\Order
     */
    protected $_orderFactory;
    private $_paymentInstance;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Helper\Data $checkoutData
    ) {
        parent::__construct($context);
        $this->_orderFactory =$this->_objectManager->get('\Magento\Sales\Model\Order');
        $this->_checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->currency = $currency;
        $this->encryptor = $encryptor;
        $this->_customerSession = $customerSession;
        $this->_checkoutData = $checkoutData;

        $this->_paymentInstance = $this->_objectManager->get('Magento\Payment\Helper\Data')->getMethodInstance(GlobepayaliCode::ALIPAY_DIRECT_CODE);
    }

    public function execute()
    {
        $order_id_prefix = $this->_paymentInstance->getConfigData('order_id_prefix');
        $credential_code = $this->_paymentInstance->getConfigData('md5_key');
        $partner_code = $this->_paymentInstance->getConfigData('partner_id');
        $order_id = isset($_REQUEST['id'])?$_REQUEST['id']:null;

        $url = "https://pay.globepay.co/api/v1.0/gateway/partners/$partner_code/orders/$order_id";

        $time = time() . '000';
        $nonce_str = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10);
        $valid_string = "$partner_code&$time&$nonce_str&$credential_code";
        $sign = strtolower(hash('sha256', $valid_string));
        $url .= "?time=$time&nonce_str=$nonce_str&sign=$sign";

        $head_arr = array();
        $head_arr[] = 'Accept: application/json';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $head_arr);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $result = curl_exec($ch);
        curl_close($ch);

        $resArr = json_decode($result, false);
        if (! $resArr) {
           $this->messageManager->addError("订单支付失败! ");
           $this->_redirect('checkout/cart/');
			return;
        }

        if (!isset($resArr->result_code)|| $resArr->result_code != 'PAY_SUCCESS') {
            $this->messageManager->addError("订单支付失败! ".print_r($resArr,true));
            $this->_redirect('checkout/cart/');
			return;
        }

        $quote_id = substr($order_id, strlen($order_id_prefix), - 6);
        $quote = $this->quoteRepository->get($quote_id);
        if (! $quote) {
            $this->messageManager->addError("订单支付失败! 购物车信息异常");
            $this->_redirect('checkout/cart/');
			return;
        }

        $order = $this->_orderFactory->loadByIncrementId($quote->getReservedOrderId());
        // 根据orderid 判断该笔订单是否处理过

        if (! $order->getId()) {
            $transaction_id = $resArr->order_id;
            $this->_objectManager->get('\Magento\Quote\Api\CartManagementInterface')->placeOrder($quote->getId());
			$order = $this->_orderFactory->loadByIncrementId($quote->getReservedOrderId());

            $status = $order->getPayment()->getMethodInstance()->getOrderStatus();

            $order->setStatus($status);

            $order->addStatusToHistory($status, sprintf('Trade finished. Trade No in Wallet is %s', $transaction_id), true);

            $order->save();

            $order->getPayment()
			->setTransactionId($transaction_id)
            ->setAdditionalInformation('transaction_id', $transaction_id)
            ->save();

            $orderSender = $this->_objectManager->get('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
            $orderSender->send($order);
        }

        $quote->setIsActive(false);
        $this->_checkoutSession->setLastSuccessQuoteId($quote->getId());
        $this->_checkoutSession->setLastQuoteId($quote->getId());
        $this->_checkoutSession->setLastOrderId($order->getId());
        $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->_redirect('checkout/onepage/success');
        return;
    }
}
