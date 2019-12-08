<?php

namespace DpSoft\Mellat;

/**
 * Mellat payment
 *
 * Class Mellat
 * @package DpSoft\Mellat
 */
class Mellat
{
    private $soapUrl = "https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl";
    private $payUrl = "https://bpm.shaparak.ir/pgwchannel/startpay.mellat";
    private $namespace = "http://interfaces.core.sw.bps.com/";
    private $terminalId;
    private $userName;
    private $userPassword;
    private $token;
    private $client = null;

    public function __construct(int $terminalId, $userName, $userPassword)
    {
        $this->terminalId = $terminalId;
        $this->userName = $userName;
        $this->userPassword = $userPassword;
    }

    /**
     * Get mellat soap client
     * @return \SoapClient
     * @throws \Exception
     */
    public function getSoapClient()
    {
        try {
            return $this->client ? $this->client : new \SoapClient($this->soapUrl);
        } catch (\SoapFault $e) {
            throw new \Exception('SoapFault: '.$e->getMessage().' #'.$e->getCode(), $e->getCode());
        }
    }

    public function setSoapClient($client)
    {
        $this->client = $client;
    }

    /**
     * @param  int  $amount
     * @param $callback
     * @param  int  $orderId
     * @return array
     * @throws \Exception
     */
    public function request(int $amount, $callback, int $orderId = null)
    {
        $client = $this->getSoapClient();
        $payParam = [
            'terminalId' => $this->terminalId,
            'userName' => $this->userName,
            'userPassword' => $this->userPassword,
            'orderId' => $orderId ? $orderId : $this->uniqueNumber(),
            'amount' => $amount,
            'localDate' => date("Ymd"),
            'localTime' => date("His"),
            'additionalData' => "",
            'callBackUrl' => $callback,
            'payerId' => 0,
        ];
        $result = $client->bpPayRequest($payParam, $this->namespace);

        $response = $this->getResponse($result);

        if ($response[0] == 0) {
            $this->token = $response[1];

            return ['order_id' => $payParam['orderId'], 'token' => $response[1]];
        } else {
            $this->throwError($response);
        }

    }


    /**
     * @param  array|null  $postData
     * @return array
     * @throws \Exception
     */
    public function verify(array $postData = null)
    {
        $data = $postData ? $postData : $_POST;

        $RefId = $data['RefId'] ?? null;
        $ResCode = $data['ResCode'] ?? null;
        $saleOrderId = intval($data['SaleOrderId'] ?? null);
        $SaleReferenceId = $data['SaleReferenceId'] ?? null;

        if ($ResCode == 0) {

            $parameters = [
                'terminalId' => $this->terminalId,
                'userName' => $this->userName,
                'userPassword' => $this->userPassword,
                'orderId' => $saleOrderId,
                'saleOrderId' => $saleOrderId,
                'saleReferenceId' => $SaleReferenceId,
            ];

            $client = $this->getSoapClient();
            $result = $client->bpVerifyRequest($parameters, $this->namespace);

            $response = $this->getResponse($result);

            if ($response[0] == 0) {
                $result = $client->bpSettleRequest($parameters, $this->namespace);
                $response = $this->getResponse($result);
                if ($response[0] == 0) {
                    return [
                        'reference_id' => $RefId,
                        'card_number' => null,
                        'order_id' => $saleOrderId,
                    ];
                } else {
                    $this->throwError($response);
                }
            } else {
                $this->throwError($response);
            }
        } else {
            throw new \Exception('پاسخ نامعتبر از درگاه بانک!',0);
        }
    }

    public function redirectScript()
    {
        $jsScript = <<<JS
var form = document.createElement("form");
form.setAttribute("method", "POST");
form.setAttribute("target", "_self");

var hiddenField = document.createElement("input");
hiddenField.setAttribute("type", "hidden");
hiddenField.setAttribute("value", "%s");
form.setAttribute("action", "%s");
hiddenField.setAttribute("name", "RefId");

form.appendChild(hiddenField);
document.body.appendChild(form);
form.submit();
JS;

        return sprintf($jsScript, $this->token, $this->payUrl);
    }


    public function redirectToBank()
    {
        echo $this->redirectScript();
    }

    /**
     * @param $result
     * @return array
     * @throws \Exception
     */
    private function getResponse($result)
    {
        if (!isset($result->return)) {
            throw new \Exception('پاسخ نامعتبر از درگاه بانک!',0);
        }

        return explode(',', $result->return);
    }

    public function uniqueNumber()
    {
        return hexdec(uniqid());
    }

    private function error($code = '')
    {

        switch ($code) {
            case 11:
                $errorText = "شماره کارت معتبر نیست";
                break;
            case 12:
                $errorText = "موجودی کافی نیست";
                break;
            case 13:
                $errorText = "رمز دوم شما صحیح نیست";
                break;
            case 14:
                $errorText = "دفعات مجاز ورود رمز بیش از حد است";
                break;
            case 15:
                $errorText = "کارت معتبر نیست";
                break;
            case 16:
                $errorText = "دفعات برداشت وجه بیش از حد مجاز است";
                break;
            case 17:
                $errorText = "کاربر از انجام تراکنش منصرف شده است";
                break;
            case 18:
                $errorText = "تاریخ انقضای کارت گذشته است";
                break;
            case 19:
                $errorText = "مبلغ برداشت وجه بیش از حد مجاز است";
                break;
            case 21:
                $errorText = "پذیرنده معتبر نیست";
                break;
            case 23:
                $errorText = "خطای امنیتی رخ داده است";
                break;
            case 24:
                $errorText = "اطلاعات کاربری پذیرنده معتبر نیست";
                break;
            case 25:
                $errorText = "مبلغ نامعتبر است";
                break;
            case 31:
                $errorText = "پاسخ نامعتبر است";
                break;
            case 32:
                $errorText = "فرمت اطلاعات وارد شده صحیح نیست";
                break;
            case 33:
                $errorText = "حساب نامعتبر است";
                break;
            case 34:
                $errorText = "خطای سیستمی";
                break;
            case 35:
                $errorText = "تاریخ نامعتبر است";
                break;
            case 41:
                $errorText = "شماره درخواست تکراری است";
                break;
            case 42:
                $errorText = "تراکنش Sale یافت نشد";
                break;
            case 43:
                $errorText = "قبلا درخواست Verify داده شده است";
                break;
            case 44:
                $errorText = "درخواست Verify یافت نشد";
                break;
            case 45:
                $errorText = "تراکنش Settle شده است";
                break;
            case 46:
                $errorText = "تراکنش Settle نشده است";
                break;
            case 47:
                $errorText = "تراکنش Settle یافت نشد";
                break;
            case 48:
                $errorText = "تراکنش Reverse شده است";
                break;
            case 49:
                $errorText = "تراکنش Refund یافت نشد";
                break;
            case 51:
                $errorText = "تراکنش تکراری است";
                break;
            case 54:
                $errorText = "تراکنش مرجع موجود نیست";
                break;
            case 55:
                $errorText = "تراکنش نامعتبر است";
                break;
            case 61:
                $errorText = "خطا در واریز";
                break;
            case 111:
                $errorText = "صادر کننده کارت نامعتبر است";
                break;
            case 112:
                $errorText = "خطای سوییچ صادر کننده کارت";
                break;
            case 113:
                $errorText = "پاسخی از صادر کننده کارت دریافت نشد";
                break;
            case 114:
                $errorText = "دارنده کارت مجاز به انجام این تراکنش نمی باشد";
                break;
            case 412:
                $errorText = "شناسه قبض نادرست است";
                break;
            case 413:
                $errorText = "شناسه پرداخت نادرست است";
                break;
            case 414:
                $errorText = "سازمان صادر کننده قبض معتبر نیست";
                break;
            case 415:
                $errorText = "زمان جلسه کاری به پایان رسیده است";
                break;
            case 416:
                $errorText = "خطا در ثبت اطلاعات";
                break;
            case 417:
                $errorText = "شناسه پرداخت کننده نامعتبر است";
                break;
            case 418:
                $errorText = "اشکال در تعریف اطلاعات مشتری";
                break;
            case 419:
                $errorText = "تعداد دفعات ورود اطلاعات بیش از حد مجاز است";
                break;
            case 421:
                $errorText = "IP معتبر نیست";
                break;
        }

        return $errorText;
    }

    /**
     * @param  array  $response
     * @throws \Exception
     */
    private function throwError(array $response): void
    {
        throw new \Exception($this->error($response[0]), $response[0]);
    }
}
