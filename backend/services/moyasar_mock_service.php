<?php
/**
 * Mock Moyasar Service for testing without real credentials
 */
class MoyasarMockService {

    public function createPayment($paymentData) {
        // Simulate API delay
        usleep(500000); // 0.5 seconds

        $mockPaymentId = 'pay_mock_' . time() . '_' . rand(1000, 9999);

        return [
            'status' => 'success',
            'payment_id' => $mockPaymentId,
            'redirect_url' => '/moyasar-payment.html?mock=1&payment_id=' . $mockPaymentId,
            'payment_status' => 'initiated',
            'provider' => 'moyasar_mock',
            'response_data' => [
                'id' => $mockPaymentId,
                'status' => 'initiated',
                'amount' => intval($paymentData['amount'] * 100),
                'currency' => $paymentData['currency'] ?? 'SAR',
                'description' => $paymentData['description'],
                'metadata' => [
                    'transaction_id' => $paymentData['transaction_id'],
                    'order_id' => $paymentData['order_id']
                ],
                'created_at' => date('Y-m-d\TH:i:s\Z'),
                'source' => [
                    'type' => $this->getSourceType($paymentData['payment_method']),
                    'message' => 'Mock payment created for testing'
                ]
            ]
        ];
    }

    public function getPayment($paymentId) {
        return [
            'id' => $paymentId,
            'status' => 'paid', // Mock successful payment
            'amount' => 10000, // 100.00 SAR
            'currency' => 'SAR',
            'description' => 'Mock payment',
            'metadata' => [
                'transaction_id' => 'mock_txn_' . time(),
                'order_id' => 'mock_order_' . time()
            ],
            'created_at' => date('Y-m-d\TH:i:s\Z'),
            'source' => [
                'type' => 'creditcard',
                'company' => 'visa',
                'last_four' => '4242',
                'message' => 'Mock payment completed'
            ]
        ];
    }

    public function refundPayment($paymentId, $amount = null) {
        return [
            'id' => 'rf_mock_' . time(),
            'payment_id' => $paymentId,
            'amount' => $amount ? intval($amount * 100) : null,
            'status' => 'succeeded',
            'created_at' => date('Y-m-d\TH:i:s\Z')
        ];
    }

    public function verifyWebhookSignature($payload, $signature) {
        // For mock mode, always return true
        return true;
    }

    public function processWebhook($webhookData) {
        return [
            'transaction_id' => $webhookData['metadata']['transaction_id'] ?? 'mock_txn',
            'provider_transaction_id' => $webhookData['id'],
            'status' => $this->mapMoyasarStatus($webhookData['status']),
            'amount' => $webhookData['amount'] / 100,
            'currency' => $webhookData['currency'],
            'provider_response' => $webhookData
        ];
    }

    private function getSourceType($paymentMethod) {
        switch ($paymentMethod) {
            case 'stc_pay':
                return 'stcpay';
            case 'apple_pay':
                return 'applepay';
            default:
                return 'creditcard';
        }
    }

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
}
?>