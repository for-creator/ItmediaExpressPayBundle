<?php

namespace Itmedia\ExpressPayBundle\Tests;

use Itmedia\ExpressPayBundle\Service\ApiClient;
use Itmedia\ExpressPayBundle\Service\SignatureProvider;
use PHPUnit\Framework\TestCase;

class ApiClientTest extends TestCase
{
    const TOKEN_API_DISABLED = 'a75b74cbcfe446509e8ee874f421bd63';
    const TOKEN_SIGNATURE_DISABLED = 'a75b74cbcfe446509e8ee874f421bd64';
    const TOKEN_SECRET_EMPTY = 'a75b74cbcfe446509e8ee874f421bd65';
    const TOKEN_WITH_SECRET = 'a75b74cbcfe446509e8ee874f421bd66';

    const SECRET_WORD = 'sandbox.expresspay.by';
    const BASE_URL = 'https://sandbox-api.express-pay.by/v1/';
    const VERSION = 1;

    /**
     * Client with signature and secret word
     *
     * @return ApiClient
     */
    private function getClientWithSecret()
    {
        $signatureProvider = new SignatureProvider(true, self::SECRET_WORD, true, self::SECRET_WORD);

        return new ApiClient($signatureProvider, self::TOKEN_WITH_SECRET, self::BASE_URL, self::VERSION, 'test', 'test');
    }

    /**
     * Client with signature and secret word
     *
     * @return ApiClient
     */
    private function getClientSecretEmpty()
    {
        $signatureProvider = new SignatureProvider(true, null, true, null);

        return new ApiClient($signatureProvider, self::TOKEN_SECRET_EMPTY, self::BASE_URL, self::VERSION, 'test', 'test');
    }

    /**
     * Client without signature
     *
     * @return ApiClient
     */
    private function getClientSignatureDisabled()
    {
        $signatureProvider = new SignatureProvider(false, null, false, null);

        return new ApiClient($signatureProvider, self::TOKEN_SIGNATURE_DISABLED, self::BASE_URL, self::VERSION, 'test', 'test');
    }

    /**
     * Client with API disabled
     *
     * @return ApiClient
     */
    private function getClientApiDisabled()
    {
        $signatureProvider = new SignatureProvider(false, null, false, null);

        return new ApiClient($signatureProvider, self::TOKEN_API_DISABLED, self::BASE_URL, self::VERSION, 'test', 'test');
    }

    public function testListInvoices()
    {
        $client = $this->getClientSecretEmpty();

        $result = $client->getListInvoices();
        $this->assertGreaterThan(0, count($result));

        $result = $client->getListInvoices(new \DateTime('01-01-2015 10:00'), new \DateTime('02-01-2015 24:00'));
        $this->assertGreaterThan(0, count($result));

        $result = $client->getListInvoices(null, null, 10, ApiClient::STATUS_PENDING);
        $this->assertGreaterThan(0, count($result));
    }

    public function testAddInvoice()
    {
        $client = $this->getClientSecretEmpty();
        $result = $client->addInvoice(10, 100.5);

        $this->assertGreaterThan(0, $result);
    }

    public function testAddInvoiceByCard()
    {
        $client = $this->getClientWithSecret();
        $result = $client->addInvoiceByCard(10, 100.5, 'Test');

        $this->assertGreaterThan(0, $result);
    }

    public function testGetDetailsInvoice()
    {
        $client = $this->getClientSecretEmpty();
        $result = $client->getDetailsInvoice(1);

        $this->assertArrayHasKey('AccountNo', $result);
    }

    public function testStatusInvoice()
    {
        $client = $this->getClientSecretEmpty();
        $result = $client->statusInvoice(1);

        $this->assertEquals(ApiClient::STATUS_PENDING, $result);
    }

    public function testStatusCardInvoice()
    {
        $client = $this->getClientWithSecret();
        $result = $client->statusCardInvoice(100);

        $this->assertEquals(ApiClient::STATUS_CARD_PENDING, $result);
    }

    public function testCancelInvoice()
    {
        $client = $this->getClientSecretEmpty();
        $client->cancelInvoice(1);

        $this->assertTrue(true);
    }

    public function testReverseCardInvoice()
    {
        $client = $this->getClientWithSecret();
        $client->reverseCardInvoice(102);

        $this->assertTrue(true);
    }

    public function testListPayments()
    {
        $client = $this->getClientSecretEmpty();

        $result = $client->getListPayments();
        $this->assertGreaterThan(0, count($result));

        $result = $client->getListPayments(new \DateTime('01-01-2015 10:00'), new \DateTime('02-01-2015 24:00'));
        $this->assertGreaterThan(0, count($result));

        $result = $client->getListPayments(null, null, 10);
        $this->assertGreaterThan(0, count($result));
    }

    public function testGetDetailsPayment()
    {
        $client = $this->getClientSecretEmpty();
        $result = $client->getDetailsPayment(1);

        $this->assertArrayHasKey('AccountNo', $result);
    }

    public function testGetPaymentFormat()
    {
        $client = $this->getClientWithSecret();
        $result = $client->getPaymentFormat(100);

        $this->assertTrue((bool)$result);
    }
}
