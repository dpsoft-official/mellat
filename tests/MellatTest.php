<?php

namespace DpSoft\Mellat\Tests;

use DpSoft\Mellat\Mellat;
use PHPUnit\Framework\TestCase;

class MellatTest extends TestCase
{

    /**
     * @var Mellat
     */
    private $mellat;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     * @throws \ReflectionException
     */
    public function getSoapMock(): \PHPUnit\Framework\MockObject\MockObject
    {
        $soapClientMock = $this->getMockFromWsdl(__DIR__."/wsdl/pgw.xml");
        $result = (new \stdClass);
        $result->return = '0,__SAMPLE__TOKEN__';
        $soapClientMock->method('bpPayRequest')->willReturn($result);
        $soapClientMock->method('bpVerifyRequest')->willReturn($result);
        $soapClientMock->method('bpSettleRequest')->willReturn($result);

        return $soapClientMock;
    }

    protected function setUp()
    {
        $this->mellat = new Mellat(1, 'un', 'ps');
        $soapClientMock = $this->getSoapMock();
        $this->mellat->setSoapClient($soapClientMock);
        parent::setUp();
    }

    /**
     * @test real world scenario
     */
    public function test_invalid_terminal()
    {
        $this->mellat = new Mellat(1, 'un', 'ps');
        $this->expectException(\Exception::class);
        $this->mellat->request(1000, '');
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
}
