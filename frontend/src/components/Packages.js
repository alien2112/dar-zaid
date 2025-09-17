import React, { useState, useEffect } from 'react';
import { apiService } from '../services/api';
import { Link } from 'react-router-dom';

const Packages = () => {
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
    <div className="packages-section">
      <h2>باقات النشر</h2>
      <div className="package-grid">
        {packages && Array.isArray(packages) && packages.length > 0 ? (
          packages.map(pkg => (
            <div key={pkg.id} className="package-card">
              <h3>{pkg.name}</h3>
              <p className="package-price">{pkg.price} {pkg.currency}</p>
              <Link to={`/package/${pkg.id}`} className="btn btn-primary">
                التفاصيل
              </Link>
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
