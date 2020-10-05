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
class Notify extends Action
{

    /**
     *
     * @var \Magento\Sales\Model\Order
     */
    protected $_orderFactory;

    /**
     *
     * @var PaymentHelper
     */
    protected $_paymentInstance;

    /**
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $_quoteRepository;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Sales\Model\Order $orderFactory, PaymentHelper $paymentHelper)
    {
        parent::__construct($context);
        $quoteRepository = $this->_objectManager->get('\Magento\Quote\Api\CartRepositoryInterface');

        $this->_orderFactory = $orderFactory;
        $this->_paymentInstance = $paymentHelper->getMethodInstance(GlobepayaliCode::ALIPAY_DIRECT_CODE);
        $this->_quoteRepository = $quoteRepository;
    }

    public function execute()
    {
        $json = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : '';
        if (empty($json)) {
            $json = file_get_contents("php://input");
        }

        if (empty($json)) {
            print json_encode(array(
                'return_code' => 'FAIL'
            ));
           return;
        }

        $object = json_decode($json, false);
        if (! $object) {
            print json_encode(array(
                'return_code' => 'FAIL'
            ));
           return;
        }

        $order_id_prefix = $this->_paymentInstance->getConfigData('order_id_prefix');
        $credential_code = $this->_paymentInstance->getConfigData('md5_key');
        $partner_code = $this->_paymentInstance->getConfigData('partner_id');
        $time = $object->time;
        $nonce_str = $object->nonce_str;

        $valid_string = "$partner_code&$time&$nonce_str&$credential_code";
        $sign = strtolower(hash('sha256', $valid_string));
        if ($sign != $object->sign) {
            print json_encode(array(
                'return_code' => 'FAIL'
            ));
            return;
        }

        $order_id = $object->partner_order_id;

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
            print json_encode(array(
                'return_code' => 'FAIL'
            ));
            return;
        }

        if (!isset($resArr->result_code)|| $resArr->result_code != 'PAY_SUCCESS') {
            print json_encode(array(
                'return_code' => 'FAIL'
            ));
            return;
        }

        $quote_id = substr($order_id, strlen($order_id_prefix), - 6);
        $quote = $this->_quoteRepository->get($quote_id);
        if (! $quote) {
            print json_encode(array(
                'return_code' => 'FAIL'
            ));
            return;
        }

        $order = $this->_orderFactory->loadByIncrementId($quote->getReservedOrderId());
        // 根据orderid 判断该笔订单是否处理过

        if (! $order->getId()) {
            $transaction_id = $object->order_id;
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

        print json_encode(array(
            'return_code' => 'SUCCESS'
        ));
    }
}
