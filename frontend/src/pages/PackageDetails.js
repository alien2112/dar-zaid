import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { apiService } from '../services/api';
import { useCart } from '../contexts/CartContext';

const PackageDetails = () => {
  const { id } = useParams();
  const [packageData, setPackageData] = useState(null);
  const [loading, setLoading] = useState(true);
  const { addPackageToCart } = useCart();

  useEffect(() => {
    fetchPackageDetails();
  }, [id]);

  const fetchPackageDetails = async () => {
    try {
      const response = await apiService.getPackages();
      const pkg = response.data.packages.find(p => p.id === parseInt(id));
      setPackageData(pkg);
    } catch (error) {
      console.error('Error fetching package details:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div style={{ padding: '4rem 0', textAlign: 'center' }}>
        <div className="container">جاري تحميل تفاصيل الباقة...</div>
      </div>
    );
  }

  if (!packageData) {
    return (
      <div style={{ padding: '4rem 0', textAlign: 'center' }}>
        <div className="container">
          <h1>الباقة غير موجودة</h1>
          <Link to="/packages" className="btn btn-primary">العودة للباقات</Link>
        </div>
      </div>
    );
  }

  return (
    <div style={{ padding: '4rem 0' }}>
      <div className="container">
        {/* Header */}
        <div style={{ textAlign: 'center', marginBottom: '3rem' }}>
          <h1 style={{ fontSize: '2.5rem', marginBottom: '1rem', color: '#2c3e50' }}>
            {packageData.name}
          </h1>
          <div style={{ fontSize: '3rem', color: '#e74c3c', fontWeight: 'bold' }}>
            {packageData.price} {packageData.currency}
          </div>
          <div style={{ marginTop: '1rem' }}>
            <button
              className="btn btn-primary"
              onClick={() => addPackageToCart(packageData)}
            >
              إضافة الباقة إلى السلة
            </button>
          </div>
        </div>

        <div style={{ maxWidth: '1000px', margin: '0 auto' }}>
          {/* Author Benefits */}
          <div className="card" style={{ marginBottom: '2rem' }}>
            <h2 style={{ color: '#27ae60', marginBottom: '1.5rem' }}>نسبة المؤلف</h2>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '1rem' }}>
              <div style={{ textAlign: 'center', padding: '1rem', backgroundColor: '#f8f9fa', borderRadius: '10px' }}>
                <div style={{ fontSize: '2rem', color: '#27ae60', fontWeight: 'bold' }}>
                  {packageData.authorShare}
                </div>
                <p>نسبة المؤلف من المبيعات</p>
              </div>
              <div style={{ textAlign: 'center', padding: '1rem', backgroundColor: '#f8f9fa', borderRadius: '10px' }}>
                <div style={{ fontSize: '2rem', color: '#3498db', fontWeight: 'bold' }}>
                  {packageData.freeCopies}
                </div>
                <p>نسخة مجانية</p>
              </div>
            </div>
          </div>

          {/* Specifications */}
          <div className="card" style={{ marginBottom: '2rem' }}>
            <h2 style={{ color: '#3498db', marginBottom: '1.5rem' }}>مواصفات الطباعة</h2>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '1rem' }}>
              {Object.entries(packageData.specifications).map(([key, value]) => (
                <div key={key} style={{ 
                  padding: '1rem', 
                  backgroundColor: '#f8f9fa', 
                  borderRadius: '10px',
                  borderLeft: '4px solid #3498db'
                }}>
                  <h4 style={{ marginBottom: '0.5rem', color: '#2c3e50' }}>
                    {key === 'printing' && 'نوع الطباعة'}
                    {key === 'size' && 'مقاس الكتاب'}
                    {key === 'paperType' && 'نوع الورق'}
                    {key === 'coverType' && 'نوع الغلاف'}
                    {key === 'maxPages' && 'عدد الصفحات (حد أقصى)'}
                  </h4>
                  <p>{value} {key === 'maxPages' && 'صفحة'}</p>
                </div>
              ))}
            </div>
          </div>

          {/* Services */}
          <div className="card" style={{ marginBottom: '2rem' }}>
            <h2 style={{ color: '#8e44ad', marginBottom: '1.5rem' }}>الخدمات المقدمة</h2>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: '1rem' }}>
              {packageData.services.map((service, index) => (
                <div key={index} style={{ 
                  display: 'flex',
                  alignItems: 'center',
                  padding: '1rem',
                  backgroundColor: '#f8f9fa',
                  borderRadius: '10px'
                }}>
                  <span style={{ 
                    color: '#27ae60', 
                    fontSize: '1.5rem',
                    marginLeft: '1rem'
                  }}>
                    ✓
                  </span>
                  <span>{service}</span>
                </div>
              ))}
            </div>
          </div>

          {/* Additional Services */}
          <div className="card" style={{ marginBottom: '2rem' }}>
            <h2 style={{ color: '#e67e22', marginBottom: '1.5rem' }}>الخدمات الإضافية</h2>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: '1rem' }}>
              {packageData.additionalServices.map((service, index) => (
                <div key={index} style={{ 
                  display: 'flex',
                  alignItems: 'center',
                  padding: '1rem',
                  backgroundColor: '#fff3cd',
                  borderRadius: '10px',
                  border: '1px solid #ffc107'
                }}>
                  <span style={{ 
                    color: '#e67e22', 
                    fontSize: '1.5rem',
                    marginLeft: '1rem'
                  }}>
                    ⭐
                  </span>
                  <span>{service}</span>
                </div>
              ))}
            </div>
          </div>

          {/* Additional Offers */}
          <div className="card" style={{ marginBottom: '2rem' }}>
            <h2 style={{ color: '#e74c3c', marginBottom: '1.5rem' }}>عروض إضافية</h2>
            <div style={{ 
              padding: '2rem',
              backgroundColor: '#ffebee',
              borderRadius: '10px',
              border: '2px solid #e74c3c',
              textAlign: 'center'
            }}>
              <p style={{ fontSize: '1.1rem', lineHeight: '1.6' }}>
                {packageData.additionalOffers}
              </p>
            </div>
          </div>

          {/* CTA Section */}
          <div style={{ textAlign: 'center', marginTop: '3rem' }}>
            <div className="card" style={{ backgroundColor: '#2c3e50', color: 'white' }}>
              <h2 style={{ marginBottom: '1rem' }}>هل أنت مستعد لنشر كتابك؟</h2>
              <p style={{ marginBottom: '2rem', fontSize: '1.1rem' }}>
                ابدأ رحلتك في عالم النشر مع دار زيد واستفد من خبرتنا وخدماتنا المتميزة
              </p>
              <div style={{ display: 'flex', gap: '1rem', justifyContent: 'center', flexWrap: 'wrap' }}>
                <Link to="/contact" className="btn btn-primary">
                  تواصل معنا الآن
                </Link>
                <Link to="/packages" className="btn btn-secondary">
                  مقارنة الباقات
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PackageDetails;
