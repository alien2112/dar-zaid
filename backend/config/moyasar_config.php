<?php
// Moyasar Payment Gateway Configuration
class MoyasarConfig {
    // Live credentials
    const PUBLISHABLE_KEY = 'pk_live_FnXepyRM6tuTa2pKFhAotcNkb15NUvgoPX1G15nr';
    const SECRET_KEY = 'sk_live_oyWZTjdZHFEzGo';

    // API URLs
    const API_BASE_URL = 'https://api.moyasar.com/v1';
    const CALLBACK_URL = '/api/payments/callback/moyasar';

    // Supported payment methods
    const SUPPORTED_METHODS = [
        'creditcard',
        'stcpay',
        'applepay'
    ];

    // Currency
    const CURRENCY = 'SAR';

    public static function getHeaders() {
        return [
            'Authorization: Basic ' . base64_encode(self::SECRET_KEY . ':'),
            'Content-Type: application/json'
        ];
    }

    public static function createPaymentData($amount, $currency, $description, $callback_url, $metadata = []) {
        return [
            'amount' => intval($amount * 100), // Moyasar expects amount in halalas (cents)
            'currency' => $currency,
            'description' => $description,
            'callback_url' => $callback_url,
            'metadata' => $metadata
        ];
    }
}
?>