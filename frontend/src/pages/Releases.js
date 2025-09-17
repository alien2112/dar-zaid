import React, { useEffect, useState } from 'react';
import { apiService } from '../services/api';

const Releases = () => {
  const [news, setNews] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const load = async () => {
      try {
        const res = await apiService.get('/news');
        setNews(res.data.news || []);
      } catch {
        setNews([]);
      } finally {
        setLoading(false);
      }
    };
    load();
  }, []);

  return (
    <div style={{ padding: '4rem 0' }}>
      <div className="container">
        <h1 style={{ textAlign: 'center', marginBottom: '2rem', fontSize: '2.5rem' }}>
          الإصدارات والأخبار
        </h1>
        
        <p style={{ textAlign: 'center', marginBottom: '4rem', fontSize: '1.1rem', color: '#7f8c8d' }}>
          تابع آخر إصداراتنا والأخبار المتعلقة بدار زيد للنشر والتوزيع
        </p>

        {/* News Section */}
        <section style={{ marginBottom: '4rem' }}>
          <div style={{ 
            backgroundColor: '#3498db', 
            color: 'white', 
            padding: '1rem',
            marginBottom: '2rem',
            borderRadius: '10px'
          }}>
            <h2 style={{ textAlign: 'center', fontSize: '1.8rem' }}>شريط الأخبار</h2>
          </div>
          
          <div style={{ maxWidth: '800px', margin: '0 auto' }}>
            {loading ? (
              <div className="loading"><div className="spinner"></div></div>
            ) : news.map((item) => (
              <div key={item.id} className="card" style={{ marginBottom: '2rem' }}>
                <div style={{ 
                  display: 'flex', 
                  gap: '1.5rem', 
                  alignItems: 'flex-start',
                  flexDirection: 'row-reverse' // RTL layout
                }}>
                  <div style={{ 
                    minWidth: '150px',
                    height: '100px', 
                    backgroundColor: '#f0f0f0',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    color: '#666',
                    borderRadius: '5px'
                  }}>
                    <img src={item.image || '/images/news-placeholder.jpg'} alt={item.title} style={{ width: '100%', height: '100%', objectFit: 'cover', borderRadius: '5px' }} />
                  </div>
                  
                  <div style={{ flex: 1 }}>
                    <h3 style={{ 
                      marginBottom: '0.5rem', 
                      color: '#2c3e50',
                      fontSize: '1.3rem'
                    }}>
                      {item.title}
                    </h3>
                    
                    <p style={{ 
                      color: '#7f8c8d', 
                      fontSize: '0.9rem',
                      marginBottom: '1rem'
                    }}>
                      {new Date(item.date).toLocaleDateString('ar-SA')}
                    </p>
                    
                    <p style={{ lineHeight: '1.6' }}>
                      {item.content}
                    </p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </section>

        {/* Latest Releases */}
        <section>
          <h2 style={{ 
            textAlign: 'center', 
            marginBottom: '3rem', 
            fontSize: '2rem',
            color: '#2c3e50'
          }}>
            أحدث الإصدارات
          </h2>
          
          <div className="package-grid">
            {news.filter(n => n.type === 'release').slice(0, 3).map(rel => (
              <div key={rel.id} className="card" style={{ textAlign: 'center' }}>
                <div style={{ height: '200px', marginBottom: '1rem' }}>
                  <img src={rel.image || '/images/news-placeholder.jpg'} alt={rel.title} style={{ width: '100%', height: '100%', objectFit: 'cover', borderRadius: '8px' }} />
                </div>
                <h3 style={{ marginBottom: '0.5rem' }}>{rel.title}</h3>
                <p style={{ color: '#7f8c8d', marginBottom: '1rem' }}>{new Date(rel.date).toLocaleDateString('ar-SA')}</p>
                <button className="btn btn-primary">تصفح الكتاب</button>
              </div>
            ))}
          </div>
        </section>
      </div>
    </div>
  );
};

export default Releases;
