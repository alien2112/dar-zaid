import React, { useState } from 'react';
import PaymentMethods from '../components/PaymentMethods';
import { paymentService } from '../services/paymentService';

const PaymentDemo = () => {
  const [selectedMethod, setSelectedMethod] = useState(null);
  const [amount, setAmount] = useState(100);
  const [currency, setCurrency] = useState('SAR');
  const [paymentResult, setPaymentResult] = useState(null);
  const [loading, setLoading] = useState(false);

  const demoOrderData = {
    items: [
      { id: 1, title: 'كتاب الفلسفة الإسلامية', price: 45, quantity: 1 },
      { id: 2, title: 'تاريخ الحضارة العربية', price: 55, quantity: 1 }
    ],
    customer_info: {
      name: 'أحمد محمد',
      email: 'ahmed@example.com',
      phone: '+966501234567'
    },
    shipping_address: {
      address: 'الرياض، النرجس',
      city: 'الرياض',
      region: 'riyadh',
      postal_code: '12345',
      country: 'Saudi Arabia'
    },
    currency: currency
  };

  const handleMethodSelect = (method) => {
    setSelectedMethod(method);
    setPaymentResult(null);
  };

  const handlePaymentInitiate = async (method) => {
    setLoading(true);
    setPaymentResult(null);

    try {
      // Simulate payment initialization
      await new Promise(resolve => setTimeout(resolve, 2000));

      // Mock different responses based on payment method
      let mockResponse;

      switch (method.id) {
        case 'stc_pay':
          mockResponse = {
            status: 'redirect',
            redirect_url: 'https://stcpay.com.sa/demo',
            transaction_id: `stc_${Date.now()}`,
            message: 'سيتم توجيهك إلى STC Pay'
          };
          break;

        case 'tamara':
          mockResponse = {
            status: 'redirect',
            redirect_url: 'https://tamara.co/checkout/demo',
            transaction_id: `tamara_${Date.now()}`,
            installments: 3,
            message: 'سيتم توجيهك إلى تمارا'
          };
          break;

        case 'tabby':
          mockResponse = {
            status: 'redirect',
            redirect_url: 'https://tabby.ai/checkout/demo',
            transaction_id: `tabby_${Date.now()}`,
            installments: 4,
            message: 'سيتم توجيهك إلى تابي'
          };
          break;

        case 'bank_transfer':
          mockResponse = {
            status: 'pending',
            transaction_id: `bank_${Date.now()}`,
            bank_details: {
              bank_name: 'البنك الأهلي السعودي',
              account_number: '1234567890',
              iban: 'SA0510000012345678901',
              account_holder: 'دار زيد للنشر والتوزيع',
              swift_code: 'NCBKSARIXX'
            },
            reference_number: `REF${Date.now()}`,
            amount: amount,
            currency: currency,
            message: 'يرجى إجراء التحويل باستخدام التفاصيل المقدمة'
          };
          break;

        case 'paypal':
          mockResponse = {
            status: 'redirect',
            redirect_url: 'https://www.paypal.com/checkout/demo',
            transaction_id: `paypal_${Date.now()}`,
            message: 'سيتم توجيهك إلى PayPal'
          };
          break;

        case 'sadad':
          mockResponse = {
            status: 'redirect',
            redirect_url: 'https://sadad.com.sa/demo',
            transaction_id: `sadad_${Date.now()}`,
            message: 'سيتم توجيهك إلى سداد'
          };
          break;

        case 'fawry':
          mockResponse = {
            status: 'redirect',
            redirect_url: 'https://fawry.com/demo',
            transaction_id: `fawry_${Date.now()}`,
            message: 'سيتم توجيهك إلى فوري'
          };
          break;

        case 'urpay':
          mockResponse = {
            status: 'redirect',
            redirect_url: 'https://urpay.com.sa/demo',
            transaction_id: `urpay_${Date.now()}`,
            message: 'سيتم توجيهك إلى أور باي'
          };
          break;

        case 'benefit':
          mockResponse = {
            status: 'redirect',
            redirect_url: 'https://benefit.com.sa/demo',
            transaction_id: `benefit_${Date.now()}`,
            message: 'سيتم توجيهك إلى بنفت'
          };
          break;

        case 'amex':
        case 'unionpay':
          mockResponse = {
            status: 'redirect',
            redirect_url: `/payment/card-form?method=${method.id}&demo=true`,
            transaction_id: `${method.id}_${Date.now()}`,
            message: `سيتم توجيهك إلى نموذج ${method.nameAr}`
          };
          break;

        default:
          mockResponse = {
            status: 'completed',
            transaction_id: `demo_${Date.now()}`,
            amount: amount,
            currency: currency,
            message: 'تم الدفع بنجاح (محاكاة)'
          };
      }

      setPaymentResult(mockResponse);

      // If it's a redirect, show redirect message
      if (mockResponse.status === 'redirect') {
        alert(`سيتم توجيهك إلى: ${mockResponse.redirect_url}\n(هذا عرض توضيحي فقط)`);
      }

    } catch (error) {
      setPaymentResult({
        status: 'failed',
        message: error.message
      });
    } finally {
      setLoading(false);
    }
  };

  const PaymentResultDisplay = () => {
    if (!paymentResult) return null;

    const getStatusColor = (status) => {
      switch (status) {
        case 'completed': return '#10b981';
        case 'pending': return '#f59e0b';
        case 'redirect': return '#3b82f6';
        case 'failed': return '#ef4444';
        default: return '#6b7280';
      }
    };

    const getStatusText = (status) => {
      switch (status) {
        case 'completed': return 'مكتمل';
        case 'pending': return 'في الانتظار';
        case 'redirect': return 'إعادة توجيه';
        case 'failed': return 'فشل';
        default: return status;
      }
    };

    return (
      <div className="payment-result" style={{
        background: 'white',
        padding: '2rem',
        borderRadius: '12px',
        marginTop: '2rem',
        border: `2px solid ${getStatusColor(paymentResult.status)}`
      }}>
        <h3>نتيجة المعاملة</h3>
        <div className="result-details" style={{ textAlign: 'right' }}>
          <div className="detail-row">
            <span>الحالة:</span>
            <span style={{
              color: getStatusColor(paymentResult.status),
              fontWeight: 'bold'
            }}>
              {getStatusText(paymentResult.status)}
            </span>
          </div>

          {paymentResult.transaction_id && (
            <div className="detail-row">
              <span>رقم المعاملة:</span>
              <span>{paymentResult.transaction_id}</span>
            </div>
          )}

          {paymentResult.amount && (
            <div className="detail-row">
              <span>المبلغ:</span>
              <span>{paymentService.formatAmount(paymentResult.amount, paymentResult.currency)}</span>
            </div>
          )}

          {paymentResult.installments && (
            <div className="detail-row">
              <span>عدد الأقساط:</span>
              <span>{paymentResult.installments} أقساط</span>
            </div>
          )}

          {paymentResult.bank_details && (
            <div className="bank-details" style={{
              background: '#f8f9fa',
              padding: '1rem',
              borderRadius: '8px',
              marginTop: '1rem'
            }}>
              <h4>تفاصيل التحويل البنكي</h4>
              <div className="detail-row">
                <span>اسم البنك:</span>
                <span>{paymentResult.bank_details.bank_name}</span>
              </div>
              <div className="detail-row">
                <span>رقم الحساب:</span>
                <span>{paymentResult.bank_details.account_number}</span>
              </div>
              <div className="detail-row">
                <span>IBAN:</span>
                <span>{paymentResult.bank_details.iban}</span>
              </div>
              <div className="detail-row">
                <span>رقم المرجع:</span>
                <span>{paymentResult.reference_number}</span>
              </div>
            </div>
          )}

          <div className="detail-row">
            <span>الرسالة:</span>
            <span>{paymentResult.message}</span>
          </div>
        </div>
      </div>
    );
  };

  return (
    <div className="payment-demo-page">
      <div className="container">
        <div className="payment-demo-header">
          <h1>عرض توضيحي لطرق الدفع</h1>
          <p>هذه صفحة تجريبية لعرض جميع طرق الدفع المتاحة في النظام</p>
        </div>

        <div className="demo-controls" style={{
          background: 'white',
          padding: '2rem',
          borderRadius: '12px',
          marginBottom: '2rem',
          boxShadow: '0 4px 12px rgba(0, 0, 0, 0.1)'
        }}>
          <h3>إعدادات العرض التوضيحي</h3>
          <div className="controls-grid" style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
            gap: '1rem',
            marginTop: '1rem'
          }}>
            <div className="form-group">
              <label htmlFor="amount">المبلغ (ريال)</label>
              <input
                type="number"
                id="amount"
                value={amount}
                onChange={(e) => setAmount(Number(e.target.value))}
                min="1"
                max="10000"
              />
            </div>
            <div className="form-group">
              <label htmlFor="currency">العملة</label>
              <select
                id="currency"
                value={currency}
                onChange={(e) => setCurrency(e.target.value)}
              >
                <option value="SAR">ريال سعودي (SAR)</option>
                <option value="USD">دولار أمريكي (USD)</option>
                <option value="EUR">يورو (EUR)</option>
              </select>
            </div>
          </div>
        </div>

        <div className="demo-content" style={{
          background: 'white',
          padding: '2rem',
          borderRadius: '12px',
          boxShadow: '0 4px 12px rgba(0, 0, 0, 0.1)'
        }}>
          <PaymentMethods
            amount={amount}
            currency={currency}
            selectedMethod={selectedMethod}
            onMethodSelect={handleMethodSelect}
            onPaymentInitiate={handlePaymentInitiate}
            disabled={loading}
          />

          <PaymentResultDisplay />
        </div>

        <div className="demo-info" style={{
          background: '#f0f4ff',
          padding: '2rem',
          borderRadius: '12px',
          marginTop: '2rem',
          border: '1px solid #3b82f6'
        }}>
          <h3>معلومات العرض التوضيحي</h3>
          <div className="info-grid" style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))',
            gap: '2rem',
            marginTop: '1rem'
          }}>
            <div>
              <h4>طرق الدفع المتاحة:</h4>
              <ul style={{ paddingRight: '1.5rem' }}>
                <li><strong>STC Pay:</strong> محفظة رقمية سعودية</li>
                <li><strong>تمارا (Tamara):</strong> ادفع الآن أو بالتقسيط</li>
                <li><strong>تابي (Tabby):</strong> ادفع على 4 أقساط</li>
                <li><strong>Google Pay:</strong> محفظة جوجل الرقمية</li>
                <li><strong>Apple Pay:</strong> محفظة آبل الرقمية</li>
                <li><strong>PayPal:</strong> محفظة باي بال العالمية</li>
                <li><strong>أور باي (UrPay):</strong> محفظة رقمية سعودية</li>
                <li><strong>بنفت (Benefit):</strong> محفظة رقمية سعودية</li>
                <li><strong>Visa/Mastercard:</strong> البطاقات الائتمانية</li>
                <li><strong>أمريكان إكسبريس:</strong> بطاقة ائتمانية عالمية</li>
                <li><strong>يونيون باي:</strong> بطاقة ائتمانية صينية</li>
                <li><strong>مدى:</strong> الشبكة السعودية للمدفوعات</li>
                <li><strong>سداد (Sadad):</strong> نظام الدفع الإلكتروني السعودي</li>
                <li><strong>فوري (Fawry):</strong> نظام دفع متعدد القنوات</li>
                <li><strong>التحويل البنكي:</strong> تحويل مباشر من البنك</li>
              </ul>
            </div>
            <div>
              <h4>ملاحظات مهمة:</h4>
              <ul style={{ paddingRight: '1.5rem' }}>
                <li>هذا عرض توضيحي فقط - لا يتم دفع أموال حقيقية</li>
                <li>كل طريقة دفع لها حدود دنيا وعليا مختلفة</li>
                <li>طرق الدفع المتاحة تعتمد على المبلغ والعملة</li>
                <li>تمارا وتابي متاحان للدفع بالتقسيط فقط</li>
                <li>التحويل البنكي يتطلب معالجة يدوية</li>
                <li>جميع الطرق تدعم العملة السعودية</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="demo-code" style={{
          background: '#1e293b',
          color: '#e2e8f0',
          padding: '2rem',
          borderRadius: '12px',
          marginTop: '2rem',
          fontFamily: 'monospace'
        }}>
          <h3 style={{ color: '#f1f5f9', marginBottom: '1rem' }}>مثال على الكود:</h3>
          <pre style={{ overflow: 'auto', fontSize: '0.9rem' }}>
{`// استخدام مكون طرق الدفع
import PaymentMethods from './components/PaymentMethods';
import { paymentService } from './services/paymentService';

const MyCheckout = () => {
  const [selectedMethod, setSelectedMethod] = useState(null);

  const handlePayment = async (method) => {
    const result = await paymentService.initializePayment(
      method.id,
      orderData
    );
    // معالجة النتيجة...
  };

  return (
    <PaymentMethods
      amount={100}
      currency="SAR"
      selectedMethod={selectedMethod}
      onMethodSelect={setSelectedMethod}
      onPaymentInitiate={handlePayment}
    />
  );
};`}
          </pre>
        </div>
      </div>
    </div>
  );
};

export default PaymentDemo;