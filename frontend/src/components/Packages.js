import React, { useState, useEffect } from 'react';
import { apiService } from '../services/api';
import { Link } from 'react-router-dom';

const Packages = ({ hidePrices = false }) => {
  const [packages, setPackages] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchPackages = async () => {
      try {
        const res = await apiService.get('/packages');
        if (res.data && res.data.packages && Array.isArray(res.data.packages)) {
          setPackages(res.data.packages);
        } else {
          setPackages([]);
        }
      } catch (error) {
        console.error("Error fetching packages:", error);
        setPackages([]);
      } finally {
        setLoading(false);
      }
    };

    fetchPackages();
  }, []);

  if (loading) {
    return <div>Loading...</div>;
  }

  return (
    <div className={`packages-section ${hidePrices ? 'packages-modern' : ''}`}>
      <h2>باقات النشر</h2>
      <div className="package-grid">
        {packages && Array.isArray(packages) && packages.length > 0 ? (
          packages.map(pkg => (
            <div key={pkg.id} className={`package-card ${hidePrices ? 'package-card-modern' : ''}`}>
              <div className="package-header">
                <h3>{pkg.name}</h3>
                {!hidePrices && <p className="package-price">{pkg.price} {pkg.currency}</p>}
              </div>
              <div className="package-content">
                <p className="package-description">
                  {pkg.description || 'اكتشف المزيد من التفاصيل حول هذه الباقة'}
                </p>
                <Link to={`/package/${pkg.id}`} className="btn btn-primary package-btn">
                  {hidePrices ? 'اكتشف الباقة' : 'التفاصيل'}
                </Link>
              </div>
            </div>
          ))
        ) : (
          <div className="no-packages">
            <p>لا توجد باقات متاحة حالياً</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default Packages;
