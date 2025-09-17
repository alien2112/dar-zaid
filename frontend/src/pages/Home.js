import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet-async';
import { apiService } from '../services/api';
import { Link, useNavigate } from 'react-router-dom';
import ImageSlider from '../components/ImageSlider';
import Packages from '../components/Packages';
import BookOfTheWeek from '../components/BookOfTheWeek';
import ChosenBooks from '../components/ChosenBooks';
import DynamicCategoryWidget from '../components/DynamicCategoryWidget';

const Home = () => {
  const [dynamicCategories, setDynamicCategories] = useState([]);
  const [categories, setCategories] = useState([]);
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const categoriesRes = await apiService.getDynamicCategories();
        setDynamicCategories(categoriesRes.data.categories || []);
        const cats = await apiService.getCategories();
        const list = Array.isArray(cats.data) ? cats.data : (cats.data && Array.isArray(cats.data.categories) ? cats.data.categories : []);
        setCategories(list.map(c => c.name).filter(Boolean));
      } catch (error) {
        console.error('Error fetching dynamic categories:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  return (
    <div>
      <Helmet>
        <title>دار زيد للنشر والتوزيع - أفضل دار نشر في المملكة العربية السعودية</title>
        <meta name="description" content="دار زيد للنشر والتوزيع - اكتشف عالماً من المعرفة والإبداع. أفضل دار نشر في السعودية تقدم كتب عربية متنوعة وخدمات نشر احترافية للمؤلفين" />
        <meta name="keywords" content="دار نشر, كتب عربية, نشر كتب, السعودية, دار زيد, أدب عربي, كتب إسلامية, تاريخ, فلسفة" />
        <meta name="author" content="دار زيد للنشر والتوزيع" />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href="https://www.daralhadarah.net/" />

        {/* Open Graph / Facebook */}
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://www.daralhadarah.net/" />
        <meta property="og:title" content="دار زيد للنشر والتوزيع - أفضل دار نشر في المملكة العربية السعودية" />
        <meta property="og:description" content="اكتشف عالماً من المعرفة والإبداع مع دار زيد للنشر والتوزيع. نقدم أفضل الكتب العربية وخدمات النشر الاحترافية" />
        <meta property="og:image" content="https://www.daralhadarah.net/og-image.jpg" />
        <meta property="og:locale" content="ar_SA" />
        <meta property="og:site_name" content="دار زيد للنشر والتوزيع" />

        {/* Twitter */}
        <meta property="twitter:card" content="summary_large_image" />
        <meta property="twitter:url" content="https://www.daralhadarah.net/" />
        <meta property="twitter:title" content="دار زيد للنشر والتوزيع - أفضل دار نشر في المملكة العربية السعودية" />
        <meta property="twitter:description" content="اكتشف عالماً من المعرفة والإبداع مع دار زيد للنشر والتوزيع" />
        <meta property="twitter:image" content="https://www.daralhadarah.net/og-image.jpg" />

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
            "name": "دار زيد للنشر والتوزيع",
            "url": "https://www.daralhadarah.net",
            "logo": "https://www.daralhadarah.net/logo.png",
            "description": "دار زيد للنشر والتوزيع - أفضل دار نشر في المملكة العربية السعودية",
            "address": {
              "@type": "PostalAddress",
              "addressCountry": "SA",
              "addressLocality": "الرياض"
            },
            "contactPoint": {
              "@type": "ContactPoint",
              "telephone": "+966-XX-XXXXXXX",
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
        {/* Categories strip */}
        {categories.length > 0 && (
          <div style={{ margin: '1.5rem 0' }}>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.5rem', justifyContent: 'center' }}>
              {categories.map((cat) => (
                <Link
                  key={cat}
                  to={`/bookstore?category=${encodeURIComponent(cat)}`}
                  className="btn btn-secondary"
                  style={{ padding: '6px 12px' }}
                >
                  {cat}
                </Link>
              ))}
            </div>
          </div>
        )}
        <Packages />
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
