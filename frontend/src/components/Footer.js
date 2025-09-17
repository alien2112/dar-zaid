import React from 'react';

const Footer = React.memo(() => {
  return (
    <footer className="footer">
      <div className="container">
        <div className="footer-content">
          <div className="footer-section">
            <h3>دار زيد للنشر والتوزيع</h3>
            <p>
              تأسست دار زيد للنشر والتوزيع عام ٢٠٢٢م برؤية ثقافية طموحة تهدف إلى تمكين المؤلفين 
              من مختلف الشرائح، وتقديم الدعم المهني لنشر مؤلفاتهم بأسلوب يليق بقيمتهم الفكرية والإبداعية.
            </p>
          </div>
          
          <div className="footer-section">
            <h3>روابط سريعة</h3>
            <ul style={{ listStyle: 'none' }}>
              <li style={{ marginBottom: '0.5rem' }}><a href="/" style={{ color: 'white', textDecoration: 'none' }}>الرئيسية</a></li>
              <li style={{ marginBottom: '0.5rem' }}><a href="/packages" style={{ color: 'white', textDecoration: 'none' }}>باقات النشر</a></li>
              <li style={{ marginBottom: '0.5rem' }}><a href="/bookstore" style={{ color: 'white', textDecoration: 'none' }}>متجر الكتب</a></li>
              <li style={{ marginBottom: '0.5rem' }}><a href="/blog" style={{ color: 'white', textDecoration: 'none' }}>المدونة</a></li>
              <li style={{ marginBottom: '0.5rem' }}><a href="/contact" style={{ color: 'white', textDecoration: 'none' }}>اتصل بنا</a></li>
            </ul>
          </div>
          
          <div className="footer-section">
            <h3>معلومات الاتصال</h3>
            <p>📧 info@darzaid.com</p>
            <p>📞 +966 50 123 4567</p>
            <p>📍 الطائف، المملكة العربية السعودية</p>
          </div>

          <div className="footer-section">
            <h3>تابعنا</h3>
            <div className="social-icons">
              <a href="#" className="social-icon">f</a>
              <a href="#" className="social-icon">t</a>
              <a href="#" className="social-icon">i</a>
            </div>
          </div>
        </div>
        
        <div className="footer-bottom">
          <p>&copy; 2024 دار زيد للنشر والتوزيع. جميع الحقوق محفوظة.</p>
        </div>
      </div>
    </footer>
  );
});

export default Footer;
