import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { apiService } from '../services/api';

const PublishingPackages = () => {
  const [packages, setPackages] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchPackages();
  }, []);

  const fetchPackages = async () => {
    try {
      const response = await apiService.getPackages();
      setPackages(response.data.packages);
    } catch (error) {
      console.error('Error fetching packages:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div style={{ padding: '4rem 0', textAlign: 'center' }}>
        <div className="container">جاري تحميل الباقات...</div>
      </div>
    );
  }

  return (
    <div style={{ padding: '4rem 0' }}>
      <div className="container">
        <h1 style={{ textAlign: 'center', marginBottom: '2rem', fontSize: '2.5rem' }}>
          باقات الطباعة والنشر المتميزة
        </h1>
        
        <p style={{ textAlign: 'center', marginBottom: '4rem', fontSize: '1.1rem', color: '#7f8c8d' }}>
          اختر الباقة التي تناسب احتياجاتك من باقات النشر المتنوعة التي نوفرها
        </p>
        
        <div className="package-grid">
          {packages.map((pkg) => (
            <div key={pkg.id} className="package-card">
              <h3 style={{ 
                fontSize: '1.5rem', 
                marginBottom: '1rem',
                color: '#2c3e50',
                textAlign: 'center'
              }}>
                {pkg.name}
              </h3>
              
              <div className="package-price" style={{ textAlign: 'center' }}>
                {pkg.price} {pkg.currency}
              </div>
              
              <div style={{ marginBottom: '2rem' }}>
                <h4 style={{ color: '#27ae60', marginBottom: '1rem' }}>نسبة المؤلف:</h4>
                <p>{pkg.authorShare} بسعر {pkg.price} ر.س</p>
                <p>{pkg.freeCopies} نسخة مجانية</p>
              </div>
              
              <div style={{ marginBottom: '2rem' }}>
                <h4 style={{ color: '#3498db', marginBottom: '1rem' }}>الخدمات الأساسية:</h4>
                <ul className="package-features">
                  {pkg.services.slice(0, 4).map((service, index) => (
                    <li key={index}>{service}</li>
                  ))}
                </ul>
              </div>
              
              <div style={{ textAlign: 'center' }}>
                <Link 
                  to={`/package/${pkg.id}`} 
                  className="btn btn-primary"
                  style={{ width: '100%' }}
                >
                  عرض التفاصيل الكاملة
                </Link>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default PublishingPackages;
