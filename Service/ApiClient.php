<?php

namespace Itmedia\ExpressPayBundle\Service;

use GuzzleHttp\Client;
use Itmedia\ExpressPayBundle\Exception\ApiError;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;

class ApiClient
{
    /**
     * Type of page view for payment by card
     */
    const PAGE_VIEW_MOBILE = 'MOBILE';
    const PAGE_VIEW_DESKTOP = 'DESKTOP';

    /**
     * Notification command types
     */
    const NOTIFICATION_NEW_PAYMENT = 1;
    const NOTIFICATION_PAYMENT_CANCELLED = 2;
    const NOTIFICATION_INVOICE_CHANGED = 3;

    /**
     * Statuses of the invoice
     */
    const STATUS_PENDING = 1;
    const STATUS_EXPIRED = 2;
    const STATUS_PAID = 3;
    const STATUS_PAID_PART = 4;
    const STATUS_CANCELED = 5;

    /**
     * Statuses of the invoice by card
     */
    const STATUS_CARD_PENDING = 0;
    const STATUS_CARD_HOLD = 1;
    const STATUS_CARD_AUTHORIZED = 2;
    const STATUS_CARD_CANCELLED = 3;
    const STATUS_CARD_REFUNDED = 4;
    const STATUS_CARD_AUTHORIZE_ACS = 5;
    const STATUS_CARD_REJECTED = 6;

    /**
     * Currency code
     */
    const CURRENCY_BYN = 933;

    /**
     * Empty invoice data for simplifying adding invoice
     */
    const EMPTY_INVOICE = [
        'Expiration' => null,
        'Info' => null,
        'Surname' => null,
        'FirstName' => null,
        'Patronymic' => null,
        'City' => null,
        'Street' => null,
        'House' => null,
        'Building' => null,
        'Apartment' => null,
        'IsNameEditable' => null,
        'IsAddressEditable' => null,
        'IsAmountEditable' => null,
        'EmailNotification' => null,
    ];

    /**
     * Empty invoice data for simplifying adding invoice by card
     */
    const EMPTY_INVOICE_BY_CARD = [
        'Expiration' => null,
        'Language' => null,
        'PageView' => null,
        'SessionTimeoutSecs' => null,
        'ExpirationDate' => null,
    ];

    /**
     * Signature Provider
     * @var SignatureProvider
     */
    protected $signatureProvider;

    /**
     * API access token
     * @var string
     */
    protected $token;

    /**
     * API base URL including version
     * @var string
     */
    protected $baseUrl;

    /**
     * API version
     * @var string
     */
    protected $version;

    /**
     * Return URL for card payment
     * @var string
     */
    protected $returnUrl;

    /**
     * Fail URL for card payment
     * @var string
     */
    protected $failUrl;

    /**
     * Guzzle Http Client
     * @var Client
     */
    protected $httpClient;

    /**
     * ApiClient constructor
     *
     * @param SignatureProvider $signatureProvider Signature provider
     * @param string $token API access token
     * @param string $baseUrl API base URL
     * @param string $version API version
     * @param string $returnUrl Success return URL for card payment
     * @param string $failUrl Fail return URL for card payment
     */
    public function __construct(SignatureProvider $signatureProvider, string $token, string $baseUrl, string $version, string $returnUrl, string $failUrl)
    {
        $this->signatureProvider = $signatureProvider;
        $this->token = $token;
        $this->baseUrl = $baseUrl;
        $this->version = $version;
        $this->returnUrl = $returnUrl;
        $this->failUrl = $failUrl;

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'allow_redirects' => false,
        ]);
    }

    /**
     * View the list of accounts by the parameters
     * By default returning data for the last 30 days
     *
     * @param \DateTime $from Payment date start
     * @param \DateTime $to Payment date end
     * @param string $accountNo Account number
     * @param int $status Account status for payment
     *
     * @return array List of invoices
     *
     * @throws ApiError
     */
    public function getListInvoices(\DateTime $from = null, \DateTime $to = null, string $accountNo = null, int $status = null): array
    {
        $params = [
            'token' => $this->token,
            'From' => $from ? $from->format('Ymd') : null,
            'To' => $to ? $to->format('Ymd') : null,
            'AccountNo' => $accountNo,
            'Status' => $status,
        ];

        $params = $this->addSignature('get-list-invoices', $params);

        $response = $this->httpClient->get('invoices', ['query' => $params]);

        return $this->parseResponse($response)['Items'];
    }

    /**
     * Invoicing
     *
     * @param string $accountNo Account number
     * @param float $amount Amount of bill for payment
     * @param int $currency Currency. 933 (BYN) by default.
     * @param array $data Optional invoice data
     *
     * @return int Invoice number
     *
     * @throws ApiError
     */
    public function addInvoice(string $accountNo, float $amount, int $currency = self::CURRENCY_BYN, array $data = []): int
    {
        $data = array_replace(self::EMPTY_INVOICE, $data);

        $params = [
            'token' => $this->token,
            'AccountNo' => $accountNo,
            'Amount' => str_replace('.', ',', $amount),
            'Currency' => $currency,
            'Expiration' => $data['Expiration'] instanceof \DateTime ? $data['Expiration']->format('Ymd') : $data['Expiration'],
            'Info' => $data['Info'],
            'Surname' => $data['Surname'],
            'FirstName' => $data['FirstName'],
            'Patronymic' => $data['Patronymic'],
            'City' => $data['City'],
            'Street' => $data['Street'],
            'House' => $data['House'],
            'Building' => $data['Building'],
            'Apartment' => $data['Apartment'],
            'IsNameEditable' => $data['IsNameEditable'] !== null ? (int)$data['IsNameEditable'] : null,
            'IsAddressEditable' => $data['IsAddressEditable'] !== null ? (int)$data['IsAddressEditable'] : null,
            'IsAmountEditable' => $data['IsAmountEditable'] !== null ? (int)$data['IsAmountEditable'] : null,
            'EmailNotification' => $data['EmailNotification'],
        ];

        $form = array_slice($params, 1); // 'token' is not needed for form
        $params = $this->addSignature('add-invoice', $params);
        $params = $this->filterParams($params);

        $response = $this->httpClient->post('invoices', ['query' => $params, 'form_params' => $form]);

        return $this->parseResponse($response)['InvoiceNo'];
    }

    /**
     * Invoicing by card
     *
     * @param string $accountNo Account number
     * @param float $amount Amount of bill for payment
     * @param string $info Purpose of payment
     * @param int $currency Currency. 933 (BYN) by default.
     * @param array $data Optional invoice data
     *
     * @return int Invoice number
     *
     * @throws ApiError
     */
    public function addInvoiceByCard(string $accountNo, float $amount, string $info, int $currency = self::CURRENCY_BYN, array $data = []): int
    {
        $data = array_replace(self::EMPTY_INVOICE_BY_CARD, $data);

        $params = [
            'token' => $this->token,
            'AccountNo' => $accountNo,
            'Amount' => str_replace('.', ',', $amount),
            'Currency' => $currency,
            'Info' => $info,
            'ReturnUrl' => $this->returnUrl,
            'FailUrl' => $this->failUrl,
            'Expiration' => $data['Expiration'] instanceof \DateTime ? $data['Expiration']->format('Ymd') : $data['Expiration'],
            'Language' => $data['Language'],
            'PageView' => $data['PageView'],
            'SessionTimeoutSecs' => $data['SessionTimeoutSecs'],
            'ExpirationDate' => $data['ExpirationDate'] instanceof \DateTime ? $data['ExpirationDate']->format('YmdHis') : $data['ExpirationDate'],
        ];

        $form = array_slice($params, 1); // 'token' is not needed for form
        $params = $this->addSignature('add-card-invoice', $params);
        $params = $this->filterParams($params);

        $response = $this->httpClient->post('cardinvoices', ['query' => $params, 'form_params' => $form]);

        return $this->parseResponse($response)['CardInvoiceNo'];
    }

    /**
     * Detail information about the invoice
     *
     * @param int $number Invoice number
     *
     * @return array Detail information
     *
     * @throws ApiError
     */
    public function getDetailsInvoice(int $number): array
    {
        $params = [
            'token' => $this->token,
            'Id' => $number,
        ];

        $params = $this->addSignature('get-details-invoice', $params);
        $params = $this->filterParams($params);

        $response = $this->httpClient->get('invoices/' . $number, ['query' => $params]);

        return $this->parseResponse($response);
    }

    /**
     * Invoice status
     *
     * @param int $number Invoice number
     *
     * @return int Status
     *
     * @throws ApiError
     */
    public function statusInvoice(int $number): int
    {
        $params = [
            'token' => $this->token,
            'Id' => $number,
        ];

        $params = $this->addSignature('status-invoice', $params);
        $params = $this->filterParams($params);

        $response = $this->httpClient->get('invoices/' . $number . '/status', ['query' => $params]);

        return $this->parseResponse($response)['Status'];
    }

    /**
     * Invoice status by card
     *
     * @param int $number Card invoice number
     * @param string $language Language code in ISO 639-1. By default 'ru'.
     *
     * @return int Status
     *
     * @throws ApiError
     */
    public function statusCardInvoice(int $number, $language = 'ru'): int
    {
        $params = [
            'token' => $this->token,
            'Id' => $number,
            'Language' => $language,
        ];

        $params = $this->addSignature('status-card-invoice', $params);
        $params = $this->filterParams($params, ['Language']);

        $response = $this->httpClient->get('cardinvoices/' . $number . '/status', ['query' => $params]);

        return $this->parseResponse($response)['CardInvoiceStatus'];
    }

    /**
     * Cancel invoice
     *
     * @param int $number Invoice number
     *
     * @throws ApiError
     */
    public function cancelInvoice(int $number)
    {
        $params = [
            'token' => $this->token,
            'Id' => $number,
        ];

        $params = $this->addSignature('cancel-invoice', $params);
        $params = $this->filterParams($params);

        $response = $this->httpClient->delete('invoices/' . $number, ['query' => $params]);

        $this->parseResponse($response);
    }

    /**
     * Cancel invoice by card
     *
     * @param int $number Card invoice number
     *
     * @throws ApiError
     */
    public function reverseCardInvoice(int $number)
    {
        $params = [
            'token' => $this->token,
            'CardInvoiceNo' => $number,
        ];

        $params = $this->addSignature('reverse-card-invoice', $params);
        $params = $this->filterParams($params);

        $response = $this->httpClient->post('cardinvoices/' . $number . '/reverse', ['query' => $params]);

        $this->parseResponse($response);
    }

    /**
     * List of payments
     * By default returning data for the last 30 days
     *
     * @param \DateTime $from Payment date start
     * @param \DateTime $to Payment date end
     * @param string $accountNo Account number
     *
     * @return array List of payments
     *
     * @throws ApiError
     */
    public function getListPayments(\DateTime $from = null, \DateTime $to = null, string $accountNo = null): array
    {
        $params = [
            'token' => $this->token,
            'From' => $from ? $from->format('Ymd') : null,
            'To' => $to ? $to->format('Ymd') : null,
            'AccountNo' => $accountNo,
        ];

        $params = $this->addSignature('get-list-payments', $params);

        $response = $this->httpClient->get('payments', ['query' => $params]);

        return $this->parseResponse($response)['Items'];
    }

    /**
     * Detail information about the payment
     *
     * @param int $number Payment number
     *
     * @return array Detail information
     *
     * @throws ApiError
     */
    public function getDetailsPayment(int $number): array
    {
        $params = [
            'token' => $this->token,
            'Id' => $number,
        ];

        $params = $this->addSignature('get-details-payment', $params);
        $params = $this->filterParams($params);

        $response = $this->httpClient->get('payments/' . $number, ['query' => $params]);

        return $this->parseResponse($response);
    }

    /**
     * Payment form URL
     *
     * @param int $number Card invoice number
     *
     * @return string Payment form URL
     *
     * @throws ApiError
     */
    public function getPaymentFormat(int $number): string
    {
        $params = [
            'token' => $this->token,
            'Id' => $number,
        ];

        $params = $this->addSignature('card-invoice-form', $params);
        $params = $this->filterParams($params);

        $response = $this->httpClient->get('cardinvoices/' . $number . '/payment', ['query' => $params]);

        return $this->parseResponse($response)['FormUrl'];
    }

    /**
     * Get notification from request
     *
     * @param Request $request
     *
     * @return mixed
     *
     * @throws ApiError
     */
    public function getNotification(Request $request)
    {
        $data = $request->request->get('Data');
        $signature = $request->request->get('Signature');

        $computedSignature = $this->signatureProvider->computeNotificationSignature($data);

        if (($computedSignature !== null) && ($computedSignature !== $signature)) {
            throw new ApiError('Signature mismatch');
        }

        return json_decode($data, true);
    }

    /**
     * Add signature to params
     *
     * @param string $method Method name
     * @param array $params Request params
     *
     * @return array
     */
    protected function addSignature(string $method, array $params)
    {
        $signature = $this->signatureProvider->computeSignature($method, $params);

        if ($signature !== null) {
            $params['signature'] = $signature;
        }

        return $params;
    }

    /**
     * Filter params for fields names
     *
     * @param array $params Request parameters
     * @param array $fields Additional fields names
     *
     * @return array
     */
    protected function filterParams(array $params, array $fields = [])
    {
        $result['token'] = $params['token'];

        if (!empty($params['signature'])) {
            $result['signature'] = $params['signature'];
        }

        foreach ($fields as $field) {
            if (isset($params[$field])) {
                $result[$field] = $params[$field];
            }
        }

        return $result;
    }

    /**
     * Parse response to array
     *
     * @param ResponseInterface $response Response
     *
     * @return array Parsed response
     *
     * @throws ApiError
     */
    protected function parseResponse(ResponseInterface $response): array
    {
        $response = json_decode($response->getBody(), true);

        if (!empty($response['Error'])) {
            throw new ApiError($response['Error']['Msg'], $response['Error']['MsgCode']);
        }

        if (!empty($response['ErrorMessage'])) {
            throw new ApiError($response['ErrorMessage'], $response['ErrorCode'] ?? null);
        }

        return $response;
    }
}
