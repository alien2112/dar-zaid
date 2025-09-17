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
        setPackages(res.data.packages);
      } catch (error) {
        console.error("Error fetching packages:", error);
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
        {packages.map(pkg => (
          <div key={pkg.id} className="package-card">
            <h3>{pkg.name}</h3>
            <p className="package-price">{pkg.price} {pkg.currency}</p>
            <Link to={`/package/${pkg.id}`} className="btn btn-primary">
              التفاصيل
            </Link>
          </div>
        ))}
      </div>
    </div>
  );
};

export default Packages;
