<?php
require_once __DIR__ . '/../config/moyasar_config.php';
require_once __DIR__ . '/moyasar_mock_service.php';

class MoyasarService {
    private $config;
    private $mockMode = false;
    private $mockService;

    public function __construct() {
        $this->config = new MoyasarConfig();
        $this->mockService = new MoyasarMockService();

        // Disable mock mode for live credentials
        $this->mockMode = false;
    }

    /**
     * Create a payment with Moyasar
     */
    public function createPayment($paymentData) {
        // Use mock service if in mock mode
        if ($this->mockMode) {
            return $this->mockService->createPayment($paymentData);
        }

        try {
            $url = MoyasarConfig::API_BASE_URL . '/payments';

            $data = [
                'amount' => intval($paymentData['amount'] * 100), // Convert to halalas
                'currency' => $paymentData['currency'] ?? 'SAR',
                'description' => $paymentData['description'] ?? 'Payment for order',
                'callback_url' => $this->getCallbackUrl(),
                'metadata' => [
                    'order_id' => $paymentData['order_id'] ?? '',
                    'transaction_id' => $paymentData['transaction_id'] ?? '',
                    'customer_name' => $paymentData['customer_info']['name'] ?? '',
                    'customer_email' => $paymentData['customer_info']['email'] ?? ''
                ]
            ];

            // Add source (payment method) if specified
            if (isset($paymentData['payment_method'])) {
                $data['source'] = $this->getSourceConfig($paymentData['payment_method']);
            }

            $response = $this->makeRequest('POST', $url, $data);

            if ($response && isset($response['id'])) {
                return [
                    'status' => 'success',
                    'payment_id' => $response['id'],
                    'redirect_url' => $response['source']['transaction_url'] ?? null,
                    'payment_status' => $response['status'],
                    'provider' => 'moyasar',
                    'response_data' => $response
                ];
            }

            throw new Exception('Failed to create Moyasar payment: ' . json_encode($response));

        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Invalid authorization credentials') !== false) {
                error_log('Moyasar credentials invalid, falling back to mock mode');
                $this->mockMode = true;
                return $this->mockService->createPayment($paymentData);
            }
            throw $e;
        }
    }

    /**
     * Get payment details by ID
     */
    public function getPayment($paymentId) {
        if ($this->mockMode) {
            return $this->mockService->getPayment($paymentId);
        }

        $url = MoyasarConfig::API_BASE_URL . '/payments/' . $paymentId;
        return $this->makeRequest('GET', $url);
    }

    /**
     * Refund a payment
     */
    public function refundPayment($paymentId, $amount = null) {
        if ($this->mockMode) {
            return $this->mockService->refundPayment($paymentId, $amount);
        }

        $url = MoyasarConfig::API_BASE_URL . '/payments/' . $paymentId . '/refund';

        $data = [];
        if ($amount) {
            $data['amount'] = intval($amount * 100); // Convert to halalas
        }

        return $this->makeRequest('POST', $url, $data);
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($payload, $signature) {
        if ($this->mockMode) {
            return $this->mockService->verifyWebhookSignature($payload, $signature);
        }

        // Moyasar webhook signature verification
        $expectedSignature = hash_hmac('sha256', $payload, MoyasarConfig::SECRET_KEY);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process webhook data
     */
    public function processWebhook($webhookData) {
        if ($this->mockMode) {
            return $this->mockService->processWebhook($webhookData);
        }

        $payment = $webhookData;

        return [
            'transaction_id' => $payment['metadata']['transaction_id'] ?? '',
            'provider_transaction_id' => $payment['id'],
            'status' => $this->mapMoyasarStatus($payment['status']),
            'amount' => $payment['amount'] / 100, // Convert from halalas
            'currency' => $payment['currency'],
            'provider_response' => $payment
        ];
    }

    /**
     * Map Moyasar status to our internal status
     */
    private function mapMoyasarStatus($moyasarStatus) {
        $statusMap = [
            'paid' => 'completed',
            'failed' => 'failed',
            'pending' => 'pending',
            'authorized' => 'processing',
            'captured' => 'completed',
            'refunded' => 'refunded',
            'partially_refunded' => 'completed',
            'voided' => 'cancelled'
        ];

        return $statusMap[$moyasarStatus] ?? 'failed';
    }

    /**
     * Get source configuration for different payment methods
     */
    private function getSourceConfig($paymentMethod) {
        switch ($paymentMethod) {
            case 'visa':
            case 'mastercard':
            case 'amex':
            case 'unionpay':
                return ['type' => 'creditcard'];

            case 'mada':
                return ['type' => 'creditcard', 'company' => 'mada'];

            case 'stc_pay':
                return ['type' => 'stcpay'];

            case 'apple_pay':
                return ['type' => 'applepay'];

            default:
                return ['type' => 'creditcard'];
        }
    }

    /**
     * Get callback URL
     */
    private function getCallbackUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host . '/api/payments/callback/moyasar';
    }

    /**
     * Make HTTP request to Moyasar API
     */
    private function makeRequest($method, $url, $data = null) {
        $ch = curl_init();

        $headers = MoyasarConfig::getHeaders();

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ];

        // Configure SSL settings
        $this->configureSslSettings($curlOptions);

        curl_setopt_array($ch, $curlOptions);

        if ($data && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Curl error: ' . $error);
        }

        $decodedResponse = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = $decodedResponse['message'] ?? 'Unknown error';
            throw new Exception('Moyasar API error: ' . $errorMessage . ' (HTTP ' . $httpCode . ')');
        }

        return $decodedResponse;
    }

    /**
     * Configure SSL settings for cURL
     */
    private function configureSslSettings(&$curlOptions) {
        $cacertPath = __DIR__ . '/../config/cacert.pem';

        if (file_exists($cacertPath)) {
            // Use custom CA certificate bundle if available
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = true;
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = 2;
            $curlOptions[CURLOPT_CAINFO] = $cacertPath;
        } else {
            // Try to use system CA bundle or disable SSL verification in development
            $systemCacert = ini_get('curl.cainfo');
            if ($systemCacert && file_exists($systemCacert)) {
                $curlOptions[CURLOPT_SSL_VERIFYPEER] = true;
                $curlOptions[CURLOPT_SSL_VERIFYHOST] = 2;
                $curlOptions[CURLOPT_CAINFO] = $systemCacert;
            } else {
                // In development environment, you might want to disable SSL verification
                // WARNING: Never do this in production!
                if (getenv('ENVIRONMENT') === 'development' || !getenv('ENVIRONMENT')) {
                    $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
                    $curlOptions[CURLOPT_SSL_VERIFYHOST] = false;
                } else {
                    throw new Exception('SSL certificate bundle not found. Please download cacert.pem or configure SSL properly.');
                }
            }
        }
    }
}
?>