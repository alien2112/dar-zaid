import React, { useState, useEffect } from 'react';
import { apiService } from '../services/api';
import { FaEnvelope, FaMapMarkerAlt, FaPhone, FaClock, FaTwitter, FaFacebook, FaInstagram, FaMap } from 'react-icons/fa';
import { BsBuilding } from 'react-icons/bs';

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
  const [isVisible, setIsVisible] = useState(false);
  const [focusedField, setFocusedField] = useState('');
  const [typingText, setTypingText] = useState('');
  const [currentTextIndex, setCurrentTextIndex] = useState(0);
  const [animatedStats, setAnimatedStats] = useState({
    support: 0,
    responseTime: 0,
    satisfaction: 0
  });
  const [showTimeProgression, setShowTimeProgression] = useState(false);

  const welcomeTexts = [
    'مرحباً بك في دار زيد',
    'نحن هنا لمساعدتك',
    'تواصل معنا في أي وقت',
    'نرحب بآرائك واقتراحاتك'
  ];

  useEffect(() => {
    setIsVisible(true);
  }, []);

  // Animate statistics on component mount
  useEffect(() => {
    if (!isVisible) return;

    const animateStats = () => {
      // Animate support (24/7) - count to 24
      const supportInterval = setInterval(() => {
        setAnimatedStats(prev => ({
          ...prev,
          support: Math.min(prev.support + 1, 24)
        }));
      }, 50);

      // Animate satisfaction (100%) - count to 100
      const satisfactionInterval = setInterval(() => {
        setAnimatedStats(prev => ({
          ...prev,
          satisfaction: Math.min(prev.satisfaction + 2, 100)
        }));
      }, 30);

      // Start time progression after a delay
      setTimeout(() => {
        setShowTimeProgression(true);
        setAnimatedStats(prev => ({ ...prev, responseTime: 1 }));
      }, 1000);

      // Clean up intervals
      setTimeout(() => {
        clearInterval(supportInterval);
        clearInterval(satisfactionInterval);
      }, 3000);

      return () => {
        clearInterval(supportInterval);
        clearInterval(satisfactionInterval);
      };
    };

    const timer = setTimeout(animateStats, 500);
    return () => clearTimeout(timer);
  }, [isVisible]);

  // Time progression effect (1 min to 1:59 hour then < 2h)
  useEffect(() => {
    if (!showTimeProgression) return;

    let timeValue = 1; // Start at 1 minute
    let isMinutes = true;
    
    const timeInterval = setInterval(() => {
      if (isMinutes) {
        timeValue += 1;
        if (timeValue >= 60) {
          isMinutes = false;
          timeValue = 1; // Reset to 1 hour
        }
      } else {
        timeValue += 1;
        if (timeValue >= 2) {
          // Show < 2h
          setAnimatedStats(prev => ({ ...prev, responseTime: -1 }));
          clearInterval(timeInterval);
          return;
        }
      }
      
      setAnimatedStats(prev => ({ ...prev, responseTime: timeValue }));
    }, 200);

    return () => clearInterval(timeInterval);
  }, [showTimeProgression]);

  useEffect(() => {
    const interval = setInterval(() => {
      setCurrentTextIndex((prevIndex) => (prevIndex + 1) % welcomeTexts.length);
    }, 3000);

    return () => clearInterval(interval);
  }, []);

  useEffect(() => {
    let timeout;
    const currentText = welcomeTexts[currentTextIndex];
    let charIndex = 0;
    
    const typeText = () => {
      if (charIndex <= currentText.length) {
        setTypingText(currentText.slice(0, charIndex));
        charIndex++;
        timeout = setTimeout(typeText, 100);
      }
    };
    
    typeText();
    
    return () => clearTimeout(timeout);
  }, [currentTextIndex]);

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleFocus = (fieldName) => {
    setFocusedField(fieldName);
  };

  const handleBlur = () => {
    setFocusedField('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    setResponseMessage('');

    try {
      // Try backend proxy first, fallback to direct Formspree if proxy fails
      try {
        const response = await fetch('/api/formspree_proxy', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(formData),
        });

        const result = await response.json();
        
        if (result.success) {
          setResponseMessage(result.message);
        } else {
          throw new Error(result.error || 'حدث خطأ في إرسال الرسالة');
        }
      } catch (proxyError) {
        console.log('Proxy failed, trying direct Formspree:', proxyError);
        
        // Fallback to direct Formspree call
        const formspreeFormId = process.env.REACT_APP_FORMSPREE_FORM_ID || 'xqadrpjy';
        const endpoint = `https://formspree.io/f/${formspreeFormId}`;

        const payload = {
          name: formData.name,
          email: formData.email,
          phone: formData.phone,
          subject: formData.subject || 'رسالة جديدة من نموذج الاتصال',
          message: formData.message,
          _subject: formData.subject || 'رسالة جديدة من نموذج الاتصال',
          _replyto: formData.email,
        };

        // Convert payload to URL-encoded format for Formspree
        const formDataEncoded = new URLSearchParams();
        formDataEncoded.append('name', payload.name);
        formDataEncoded.append('email', payload.email);
        formDataEncoded.append('phone', payload.phone);
        formDataEncoded.append('subject', payload.subject);
        formDataEncoded.append('message', payload.message);
        formDataEncoded.append('_subject', payload._subject);
        formDataEncoded.append('_replyto', payload._replyto);

        const res = await fetch(endpoint, {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
            'Accept': 'application/json'
          },
          body: formDataEncoded,
          mode: 'no-cors', // Use no-cors to avoid CORS issues
        });

        // With no-cors, we can't read the response, so assume success
        setResponseMessage('تم إرسال رسالتك بنجاح. شكراً لتواصلك معنا!');
      }
      setFormData({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: ''
      });
    } catch (error) {
      setResponseMessage(error.response?.data?.error || error.message || 'حدث خطأ في إرسال الرسالة');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="contact-page">
      {/* Animated Hero Section */}
      <div className="contact-hero">
        <div className="floating-elements">
          <div className="floating-circle circle-1"></div>
          <div className="floating-circle circle-2"></div>
          <div className="floating-circle circle-3"></div>
          <div className="floating-square square-1"></div>
          <div className="floating-square square-2"></div>
        </div>
        
        <div className="container">
          <div className={`hero-content ${isVisible ? 'fade-in-up' : ''}`}>
            <h1 className="hero-title">
              <span className="title-main">اتصل بنا</span>
              <span className="title-sub">نحن هنا لمساعدتك</span>
            </h1>
            
            <div className="typing-container">
              <span className="typing-text">{typingText}</span>
              <span className="typing-cursor">|</span>
            </div>
            
            <div className="hero-stats">
              <div className="stat-item tech-support-special">
                <div className="stat-number">
                  {animatedStats.support}/7
                </div>
                <div className="stat-label">دعم فني</div>
                <div className="tech-support-badge">مميز</div>
              </div>
              <div className="stat-item">
                <div className="stat-number">
                  {animatedStats.responseTime === -1 ? '< 2h' : 
                   animatedStats.responseTime < 60 ? `${animatedStats.responseTime} دقيقة` : 
                   `${animatedStats.responseTime}:59 ساعة`}
                </div>
                <div className="stat-label">وقت الاستجابة</div>
              </div>
              <div className="stat-item">
                <div className="stat-number">{animatedStats.satisfaction}%</div>
                <div className="stat-label">رضا العملاء</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="container">
        <div className={`contact-content ${isVisible ? 'fade-in-up delay-1' : ''}`}>
          <div className="contact-grid">
            {/* Enhanced Contact Form */}
            <div className="contact-form-card">
              <div className="card-header">
                <div className="card-icon"><FaEnvelope className="card-icon-svg" /></div>
                <h2>أرسل لنا رسالة</h2>
                <p>سنرد عليك في أقرب وقت ممكن</p>
              </div>
              
              <form onSubmit={handleSubmit} className="animated-form">
                <div className="form-group floating-label">
                  <input
                    type="text"
                    name="name"
                    value={formData.name}
                    onChange={handleChange}
                    onFocus={() => handleFocus('name')}
                    onBlur={handleBlur}
                    required
                    className={`form-input ${focusedField === 'name' || formData.name ? 'focused' : ''}`}
                  />
                  <label className={`form-label ${focusedField === 'name' || formData.name ? 'focused' : ''}`}>
                    الاسم الكامل *
                  </label>
                  <div className="input-border"></div>
                </div>
                
                <div className="form-group floating-label">
                  <input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    onFocus={() => handleFocus('email')}
                    onBlur={handleBlur}
                    required
                    className={`form-input ${focusedField === 'email' || formData.email ? 'focused' : ''}`}
                  />
                  <label className={`form-label ${focusedField === 'email' || formData.email ? 'focused' : ''}`}>
                    البريد الإلكتروني *
                  </label>
                  <div className="input-border"></div>
                </div>
                
                <div className="form-group floating-label">
                  <input
                    type="tel"
                    name="phone"
                    value={formData.phone}
                    onChange={handleChange}
                    onFocus={() => handleFocus('phone')}
                    onBlur={handleBlur}
                    className={`form-input ${focusedField === 'phone' || formData.phone ? 'focused' : ''}`}
                  />
                  <label className={`form-label ${focusedField === 'phone' || formData.phone ? 'focused' : ''}`}>
                    رقم الهاتف
                  </label>
                  <div className="input-border"></div>
                </div>
                
                <div className="form-group floating-label">
                  <input
                    type="text"
                    name="subject"
                    value={formData.subject}
                    onChange={handleChange}
                    onFocus={() => handleFocus('subject')}
                    onBlur={handleBlur}
                    className={`form-input ${focusedField === 'subject' || formData.subject ? 'focused' : ''}`}
                  />
                  <label className={`form-label ${focusedField === 'subject' || formData.subject ? 'focused' : ''}`}>
                    الموضوع
                  </label>
                  <div className="input-border"></div>
                </div>
                
                <div className="form-group floating-label">
                  <textarea
                    name="message"
                    rows="5"
                    value={formData.message}
                    onChange={handleChange}
                    onFocus={() => handleFocus('message')}
                    onBlur={handleBlur}
                    required
                    className={`form-input textarea ${focusedField === 'message' || formData.message ? 'focused' : ''}`}
                  ></textarea>
                  <label className={`form-label ${focusedField === 'message' || formData.message ? 'focused' : ''}`}>
                    الرسالة *
                  </label>
                  <div className="input-border"></div>
                </div>
                
                <button 
                  type="submit" 
                  className={`submit-btn ${isLoading ? 'loading' : ''}`}
                  disabled={isLoading}
                >
                  <span className="btn-text">
                    {isLoading ? 'جاري الإرسال...' : 'إرسال الرسالة'}
                  </span>
                  <div className="btn-loader"></div>
                </button>
              </form>
              
              {responseMessage && (
                <div className={`response-message ${responseMessage.includes('نجاح') ? 'success' : 'error'}`}>
                  <div className="response-icon">
                    {responseMessage.includes('نجاح') ? '✓' : '✗'}
                  </div>
                  {responseMessage}
                </div>
              )}
            </div>

            {/* Enhanced Contact Information */}
            <div className="contact-info-card">
              <div className="card-header">
                <div className="card-icon"><FaMapMarkerAlt className="card-icon-svg" /></div>
                <h2>معلومات الاتصال</h2>
                <p>تواصل معنا عبر القنوات التالية</p>
              </div>
              
              <div className="info-items">
                <div className="info-item">
                  <div className="info-icon"><BsBuilding className="info-icon-svg" /></div>
                  <div className="info-content">
                    <h3>العنوان</h3>
                    <p>الطائف، المملكة العربية السعودية</p>
                  </div>
                </div>
                
                <div className="info-item">
                  <div className="info-icon"><FaPhone className="info-icon-svg" /></div>
                  <div className="info-content">
                    <h3>الهاتف</h3>
                    <p>+966 50 123 4567</p>
                  </div>
                </div>
                
                <div className="info-item">
                  <div className="info-icon"><FaEnvelope className="info-icon-svg" /></div>
                  <div className="info-content">
                    <h3>البريد الإلكتروني</h3>
                    <p>info@darzaid.com</p>
                  </div>
                </div>
                
                <div className="info-item">
                  <div className="info-icon"><FaClock className="info-icon-svg" /></div>
                  <div className="info-content">
                    <h3>ساعات العمل</h3>
                    <p>السبت - الخميس: 8:00 ص - 5:00 م</p>
                    <p>الجمعة: مغلق</p>
                  </div>
                </div>
              </div>
              
              <div className="social-section">
                <h3>تابعنا على</h3>
                <div className="social-buttons">
                  <button className="social-btn twitter">
                    <span className="social-icon"><FaTwitter /></span>
                    <span>تويتر</span>
                  </button>
                  <button className="social-btn facebook">
                    <span className="social-icon"><FaFacebook /></span>
                    <span>فيسبوك</span>
                  </button>
                  <button className="social-btn instagram">
                    <span className="social-icon"><FaInstagram /></span>
                    <span>إنستغرام</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          {/* Enhanced Map Section */}
          <div className={`map-section ${isVisible ? 'fade-in-up delay-2' : ''}`}>
            <div className="map-card">
              <div className="map-header">
                <div className="map-icon"><FaMap className="map-icon-svg" /></div>
                <h3>موقعنا على الخريطة</h3>
                <p>زيارة مكتبنا في الرياض</p>
              </div>
              
              <div className="map-container">
                <div className="map-loading">
                  <div className="loading-spinner"></div>
                  <p>جاري تحميل الخريطة...</p>
                </div>
                <iframe
                  src="https://maps.google.com/maps?q=21.254264831543,40.4294357299805&hl=es;z=14&amp;output=embed"
                  width="100%"
                  height="100%"
                  style={{ border: 0 }}
                  allowFullScreen=""
                  loading="lazy"
                  referrerPolicy="no-referrer-when-downgrade"
                  title="Google Maps - Taif"
                  onLoad={() => {
                    const loading = document.querySelector('.map-loading');
                    if (loading) loading.style.display = 'none';
                  }}
                ></iframe>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Contact;
