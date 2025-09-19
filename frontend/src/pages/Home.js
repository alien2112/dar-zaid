import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet-async';
import { apiService } from '../services/api';
import { Link, useNavigate } from 'react-router-dom';
import ImageSlider from '../components/ImageSlider';
import Packages from '../components/Packages';
import BookOfTheWeek from '../components/BookOfTheWeek';
import ChosenBooks from '../components/ChosenBooks';
import DynamicCategoryWidget from '../components/DynamicCategoryWidget';
import '../styles/home-categories.css';

const Home = () => {
  const [dynamicCategories, setDynamicCategories] = useState([]);
  const [categories, setCategories] = useState([]);
  const [settings, setSettings] = useState({
    site_name: 'دار زيد للنشر والتوزيع',
    site_description: 'اكتشف عالماً من المعرفة والإبداع',
    contact_email: 'info@darzaid.com',
    contact_phone: '+966123456789',
    site_url: 'https://www.daralhadarah.net'
  });
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        // Load settings first
        const settingsRes = await apiService.getSettings();
        if (settingsRes.data && settingsRes.data.settings) {
          setSettings(settingsRes.data.settings);
        }
        
        const categoriesRes = await apiService.getDynamicCategories();
        setDynamicCategories(categoriesRes.data.categories || []);
        const cats = await apiService.getCategories();
        const list = Array.isArray(cats.data) ? cats.data : (cats.data && Array.isArray(cats.data.categories) ? cats.data.categories : []);
        setCategories(list.map(c => c.name).filter(Boolean));
      } catch (error) {
        console.error('Error fetching data:', error);
        // Keep default settings if API fails
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  return (
    <div>
      <Helmet>
        <title>{settings?.site_name || 'دار زيد للنشر والتوزيع'} - أفضل دار نشر في المملكة العربية السعودية</title>
        <meta name="description" content={`${settings?.site_name || 'دار زيد للنشر والتوزيع'} - ${settings?.site_description || 'اكتشف عالماً من المعرفة والإبداع'}. أفضل دار نشر في السعودية تقدم كتب عربية متنوعة وخدمات نشر احترافية للمؤلفين`} />
        <meta name="keywords" content="دار نشر, كتب عربية, نشر كتب, السعودية, دار زيد, أدب عربي, كتب إسلامية, تاريخ, فلسفة" />
        <meta name="author" content={settings?.site_name || 'دار زيد للنشر والتوزيع'} />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href={settings?.site_url || 'https://www.daralhadarah.net'} />

        {/* Open Graph / Facebook */}
        <meta property="og:type" content="website" />
        <meta property="og:url" content={settings?.site_url || 'https://www.daralhadarah.net'} />
        <meta property="og:title" content={`${settings?.site_name || 'دار زيد للنشر والتوزيع'} - أفضل دار نشر في المملكة العربية السعودية`} />
        <meta property="og:description" content={`${settings?.site_description || 'اكتشف عالماً من المعرفة والإبداع'} مع ${settings?.site_name || 'دار زيد للنشر والتوزيع'}. نقدم أفضل الكتب العربية وخدمات النشر الاحترافية`} />
        <meta property="og:image" content={`${settings?.site_url || 'https://www.daralhadarah.net'}/og-image.jpg`} />
        <meta property="og:locale" content="ar_SA" />
        <meta property="og:site_name" content={settings?.site_name || 'دار زيد للنشر والتوزيع'} />

        {/* Twitter */}
        <meta property="twitter:card" content="summary_large_image" />
        <meta property="twitter:url" content={settings?.site_url || 'https://www.daralhadarah.net'} />
        <meta property="twitter:title" content={`${settings?.site_name || 'دار زيد للنشر والتوزيع'} - أفضل دار نشر في المملكة العربية السعودية`} />
        <meta property="twitter:description" content={`${settings?.site_description || 'اكتشف عالماً من المعرفة والإبداع'} مع ${settings?.site_name || 'دار زيد للنشر والتوزيع'}`} />
        <meta property="twitter:image" content={`${settings?.site_url || 'https://www.daralhadarah.net'}/og-image.jpg`} />

        {/* Additional SEO tags */}
        <meta name="google-site-verification" content="your-verification-code" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta httpEquiv="Content-Language" content="ar" />
        <meta name="theme-color" content="#1e3a8a" />

        {/* JSON-LD Structured Data */}
        <script type="application/ld+json">
          {JSON.stringify({
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": settings?.site_name || "دار زيد للنشر والتوزيع",
            "url": settings?.site_url || "https://www.daralhadarah.net",
            "logo": `${settings?.site_url || "https://www.daralhadarah.net"}/logo.png`,
            "description": `${settings?.site_name || "دار زيد للنشر والتوزيع"} - أفضل دار نشر في المملكة العربية السعودية`,
            "address": {
              "@type": "PostalAddress",
              "addressCountry": "SA",
              "addressLocality": "الرياض"
            },
            "contactPoint": {
              "@type": "ContactPoint",
              "telephone": settings?.contact_phone || "+966123456789",
              "email": settings?.contact_email || "info@darzaid.com",
              "contactType": "customer service"
            },
            "sameAs": [
              "https://www.facebook.com/daralhadarah",
              "https://www.twitter.com/daralhadarah",
              "https://www.instagram.com/daralhadarah"
            ]
          })}
        </script>
      </Helmet>

      <ImageSlider />

      <div className="container">
        {/* Modern Categories Section */}
        {categories.length > 0 && (
          <div className="home-categories-strip">
            <div className="home-categories-header" style={{
              textAlign: 'center',
              marginBottom: '2rem',
              position: 'relative',
              zIndex: 1
            }}>
              <h2 style={{
                margin: 0,
                fontFamily: 'Amiri, serif',
                fontWeight: 700,
                fontSize: window.innerWidth <= 480 ? '1.5rem' :
                          window.innerWidth <= 768 ? '1.8rem' : '2.2rem',
                marginBottom: '0.5rem'
              }}>
                تصفح حسب التصنيفات
              </h2>
              <p style={{
                margin: '0.5rem 0 0',
                color: '#64748b',
                fontWeight: 500,
                fontSize: window.innerWidth <= 480 ? '0.9rem' : '1.1rem',
                maxWidth: '600px',
                margin: '0 auto'
              }}>
                اكتشف مجموعة متنوعة من الكتب في كافة المجالات واختر ما يناسب اهتماماتك
              </p>
            </div>
            <div className="home-categories-row">
              {categories.map((cat) => (
                <Link
                  key={cat}
                  to={`/bookstore?category=${encodeURIComponent(cat)}`}
                  className="category-chip"
                  style={{ textDecoration: 'none' }}
                >
                  {cat}
                </Link>
              ))}
            </div>
          </div>
        )}
        <Packages hidePrices={true} />
        <BookOfTheWeek />
        {loading ? (
          <div className="loading">
            <div className="spinner"></div>
          </div>
        ) : (
          <>
            {dynamicCategories.map(category => (
              <DynamicCategoryWidget key={category.id} category={category} />
            ))}
          </>
        )}
        <ChosenBooks />
      </div>

    </div>
  );
};

export default Home;
