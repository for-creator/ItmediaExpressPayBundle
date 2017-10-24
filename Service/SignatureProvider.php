<?php

namespace Itmedia\ExpressPayBundle\Service;

class SignatureProvider
{
    /**
     * Fields map to calculate a signature
     */
    const MAPPING = [
        'add-invoice' => [
            'token',
            'accountno',
            'amount',
            'currency',
            'expiration',
            'info',
            'surname',
            'firstname',
            'patronymic',
            'city',
            'street',
            'house',
            'building',
            'apartment',
            'isnameeditable',
            'isaddresseditable',
            'isamounteditable',
            'emailnotification',
        ],
        'get-details-invoice' => [
            'token',
            'id',
        ],
        'cancel-invoice' => [
            'token',
            'id',
        ],
        'status-invoice' => [
            'token',
            'id',
        ],
        'get-list-invoices' => [
            'token',
            'from',
            'to',
            'accountno',
            'status',
        ],
        'get-list-payments' => [
            'token',
            'from',
            'to',
            'accountno',
        ],
        'get-details-payment' => [
            'token',
            'id',
        ],
        'add-card-invoice' => [
            'token',
            'accountno',
            'expiration',
            'amount',
            'currency',
            'info',
            'returnurl',
            'failurl',
            'language',
            'pageview',
            'sessiontimeoutsecs',
            'expirationdate'
        ],
        'status-card-invoice' => [
            'token',
            'id',
            'language',
        ],
        'reverse-card-invoice' => [
            'token',
            'cardinvoiceno',
        ],
        'card-invoice-form' => [
            'token',
            'id',
        ],
    ];

    /**
     * Use signature for API
     * @var bool
     */
    protected $apiSignature;

    /**
     * Secret word for API
     * @var string
     */
    protected $apiSecret;

    /**
     * Use signature for notifications
     * @var bool
     */
    protected $notificationSignature;

    /**
     * Secret word for notifications
     * @var string
     */
    protected $notificationSecret;

    /**
     * SignatureProvider constructor
     *
     * @param bool $apiSignature Use API signature
     * @param string|null $apiSecret API secret word
     * @param bool $notificationSignature Use notifications signature
     * @param string|null $notificationSecret Notification secret word
     */
    public function __construct(bool $apiSignature, string $apiSecret = null, bool $notificationSignature, string $notificationSecret = null)
    {
        $this->apiSignature = $apiSignature;
        $this->apiSecret = $apiSecret;
        $this->notificationSignature = $notificationSignature;
        $this->notificationSecret = $notificationSecret;
    }

    /**
     * Computing signature for API. Null if signature disabled.
     *
     * @param string $method API method
     * @param array $params Request params
     *
     * @return null|string
     */
    public function computeSignature(string $method, array $params)
    {
        if (!$this->apiSignature) {
            return null;
        }

        $normalizedParams = array_change_key_case($params, CASE_LOWER);
        $apiMethod = self::MAPPING[$method];
        $result = '';

        /** @var array $apiMethod */
        foreach ($apiMethod as $item) {
            $result .= $normalizedParams[$item];
        }

        return strtoupper(hash_hmac('sha1', $result, $this->apiSecret));
    }

    /**
     * Computing signature for notification. Null if signature disabled.
     *
     * @param string $json Notification
     *
     * @return null|string
     */
    public function computeNotificationSignature(string $json)
    {
        if (!$this->notificationSignature) {
            return null;
        }

        return strtoupper(hash_hmac('sha1', $json, $this->notificationSecret));
    }
}
