<?php

namespace Dpsoft\Mellat\Tests;

use Dpsoft\Mellat\Mellat;
use PHPUnit\Framework\TestCase;

class MellatTest extends TestCase
{
    public function test_invalid_ip()
    {
        $this->mellat = new Mellat(1, 'un', 'ps');
        $this->mellat->setSoapClient($this->getSoapMockWithInvalidResponse());
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(421);
        $this->mellat->request(1000, '/');
    }

    public function test_valid_payment_request()
    {
        $response = $this->mellat->request(1000, '/');
        $this->assertEquals($response['token'], '__SAMPLE__TOKEN__');
        $this->assertContains('__SAMPLE__TOKEN__', $this->mellat->redirectScript());
    }

    public function test_valid_verify_request()
    {
        $response = $this->mellat->verify(['RefId' => '__REF__ID__', 'ResCode' => 0, 'SaleOrderId' => '123456']);
        $this->assertEquals('__REF__ID__', $response['reference_id']);
        $this->assertEquals(123456, $response['order_id']);
    }

    /**
     * @var Mellat
     */
    private $mellat;

    /**
     * @return array
     * @throws \ReflectionException
     */
    public function getMock(): array
    {
        $soapClientMock = $this->getMockFromWsdl(__DIR__."/wsdl/pgw.xml");
        $result = (new \stdClass);

        return array($soapClientMock, $result);
    }

    public function getSoapMock()
    {
        list($soapClientMock, $result) = $this->getMock();
        $result->return = '0,__SAMPLE__TOKEN__';
        $soapClientMock->method('bpPayRequest')->willReturn($result);
        $soapClientMock->method('bpVerifyRequest')->willReturn($result);
        $soapClientMock->method('bpSettleRequest')->willReturn($result);

        return $soapClientMock;
    }

    public function getSoapMockWithInvalidResponse()
    {
        list($soapClientMock, $result) = $this->getMock();
        $result->return = '421';
        $soapClientMock->method('bpPayRequest')->willReturn($result);

        return $soapClientMock;
    }

    protected function setUp()
    {
        $this->mellat = new Mellat(1, 'un', 'ps');
        $soapClientMock = $this->getSoapMock();
        $this->mellat->setSoapClient($soapClientMock);
        parent::setUp();
    }


}
