import React, { useState } from 'react';
import { apiService } from '../services/api';

const Contact = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: ''
  });
  const [responseMessage, setResponseMessage] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    setResponseMessage('');

    try {
      const response = await apiService.sendContact(formData);
      setResponseMessage(response.data.message);
      setFormData({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: ''
      });
    } catch (error) {
      setResponseMessage(error.response?.data?.error || 'حدث خطأ في إرسال الرسالة');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div style={{ padding: '4rem 0' }}>
      <div className="container">
        <h1 style={{ textAlign: 'center', marginBottom: '3rem', fontSize: '2.5rem' }}>
          اتصل بنا
        </h1>
        
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(400px, 1fr))', gap: '3rem' }}>
          {/* Contact Form */}
          <div className="card">
            <h2 style={{ marginBottom: '2rem', color: '#2c3e50' }}>أرسل لنا رسالة</h2>
            
            <form onSubmit={handleSubmit}>
              <div className="form-group">
                <label>الاسم الكامل *</label>
                <input
                  type="text"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  required
                  placeholder="أدخل اسمك الكامل"
                />
              </div>
              
              <div className="form-group">
                <label>البريد الإلكتروني *</label>
                <input
                  type="email"
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  required
                  placeholder="أدخل بريدك الإلكتروني"
                />
              </div>
              
              <div className="form-group">
                <label>رقم الهاتف</label>
                <input
                  type="tel"
                  name="phone"
                  value={formData.phone}
                  onChange={handleChange}
                  placeholder="أدخل رقم هاتفك"
                />
              </div>
              
              <div className="form-group">
                <label>الموضوع</label>
                <input
                  type="text"
                  name="subject"
                  value={formData.subject}
                  onChange={handleChange}
                  placeholder="موضوع الرسالة"
                />
              </div>
              
              <div className="form-group">
                <label>الرسالة *</label>
                <textarea
                  name="message"
                  rows="5"
                  value={formData.message}
                  onChange={handleChange}
                  required
                  placeholder="اكتب رسالتك هنا..."
                ></textarea>
              </div>
              
              <button 
                type="submit" 
                className="btn btn-primary" 
                disabled={isLoading}
                style={{ width: '100%' }}
              >
                {isLoading ? 'جاري الإرسال...' : 'إرسال الرسالة'}
              </button>
            </form>
            
            {responseMessage && (
              <div style={{ 
                marginTop: '1rem', 
                padding: '10px', 
                backgroundColor: responseMessage.includes('نجاح') ? '#d4edda' : '#f8d7da',
                color: responseMessage.includes('نجاح') ? '#155724' : '#721c24',
                borderRadius: '5px',
                textAlign: 'center'
              }}>
                {responseMessage}
              </div>
            )}
          </div>

          {/* Contact Information */}
          <div className="card">
            <h2 style={{ marginBottom: '2rem', color: '#2c3e50' }}>معلومات الاتصال</h2>
            
            <div style={{ marginBottom: '2rem' }}>
              <h3 style={{ color: '#3498db', marginBottom: '1rem' }}>العنوان</h3>
              <p>الرياض، المملكة العربية السعودية</p>
            </div>
            
            <div style={{ marginBottom: '2rem' }}>
              <h3 style={{ color: '#3498db', marginBottom: '1rem' }}>الهاتف</h3>
              <p>+966 50 123 4567</p>
            </div>
            
            <div style={{ marginBottom: '2rem' }}>
              <h3 style={{ color: '#3498db', marginBottom: '1rem' }}>البريد الإلكتروني</h3>
              <p>info@darzaid.com</p>
            </div>
            
            <div style={{ marginBottom: '2rem' }}>
              <h3 style={{ color: '#3498db', marginBottom: '1rem' }}>ساعات العمل</h3>
              <p>السبت - الخميس: 8:00 ص - 5:00 م</p>
              <p>الجمعة: مغلق</p>
            </div>
            
            <div>
              <h3 style={{ color: '#3498db', marginBottom: '1rem' }}>تابعنا على</h3>
              <div style={{ display: 'flex', gap: '1rem', flexDirection: 'row-reverse' }}>
                <button className="btn btn-secondary">تويتر</button>
                <button className="btn btn-secondary">فيسبوك</button>
                <button className="btn btn-secondary">إنستغرام</button>
              </div>
            </div>
          </div>
        </div>
        
        {/* Map */}
        <div className="card" style={{ marginTop: '3rem', textAlign: 'center' }}>
          <h3 style={{ marginBottom: '2rem', color: '#2c3e50' }}>موقعنا</h3>
          <div style={{ height: '400px', width: '100%' }}>
            <iframe
              src="https://maps.google.com/maps?q=21.254264831543,40.4294357299805&hl=es;z=14&amp;output=embed"
              width="100%"
              height="100%"
              style={{ border: 0 }}
              allowFullScreen=""
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
              title="Google Maps - Taif"
            ></iframe>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Contact;
