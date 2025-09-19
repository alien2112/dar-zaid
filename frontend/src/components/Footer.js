import React, { useEffect, useState } from 'react';
import { apiService } from '../services/api';

const Footer = React.memo(() => {
  const [links, setLinks] = useState(null);

  useEffect(() => {
    let mounted = true;
    (async () => {
      try {
        const res = await apiService.getSettings();
        const settings = (res.data && res.data.settings) || {};
        const social = Array.isArray(settings.social_links) ? settings.social_links : [];
        if (!mounted) return;
        const activeSorted = social
          .filter((l) => l && l.url && l.is_active)
          .sort((a, b) => (a.display_order ?? 0) - (b.display_order ?? 0));
        setLinks(activeSorted);
      } catch {
        setLinks([]);
      }
    })();
    return () => { mounted = false; };
  }, []);

  const fallback = [
    { label: 'X', url: 'https://x.com/darzaid22' },
    { label: '📷', url: 'https://instagram.com/dar.zaid.2022', title: 'انستقرام' },
    { label: '💬', url: 'https://wa.me/966561123119', title: 'واتساب' },
    { label: '♪', url: 'https://www.tiktok.com/@dar.zaid.2022', title: 'تيك توك' },
  ];
  const items = links && links.length > 0 ? links.map(l => ({ label: l.label || l.platform || 'Link', url: l.url, title: l.platform || l.label })) : fallback;

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
              {(items || []).map((it, idx) => (
                <a key={idx} href={it.url} target="_blank" rel="noopener noreferrer" className="social-icon" title={it.title || it.label}>
                  {it.label}
                </a>
              ))}
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
