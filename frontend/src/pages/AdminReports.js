import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { apiService } from '../services/api';
import CustomLoader from '../components/CustomLoader';

const AdminReports = () => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [books, setBooks] = useState([]);
  const [orders, setOrders] = useState([]);
  const [reviews, setReviews] = useState([]);

  useEffect(() => {
    const load = async () => {
      try {
        const [booksRes, ordersRes] = await Promise.all([
          apiService.getBooks({ page: 1, limit: 500 }),
          apiService.get('/orders')
        ]);
        setBooks(booksRes.data.items || []);
        setOrders(ordersRes.data.orders || []);

        // Aggregate reviews by fetching for top 10 books only (to limit calls)
        const top = (booksRes.data.items || []).slice(0, 10);
        const reviewCalls = top.map(b => apiService.getReviews(b.id).then(r => ({ id: b.id, ...r.data })).catch(()=>({ id: b.id, reviews: [], average: 0, count: 0 })));
        const reviewData = await Promise.all(reviewCalls);
        setReviews(reviewData);
      } catch (e) {
        setError('فشل تحميل التقارير');
      } finally {
        setLoading(false);
      }
    };
    load();
  }, []);

  const stats = useMemo(() => {
    const totalBooks = books.length;
    const inStock = books.reduce((sum, b) => sum + (b.stock_quantity || 0), 0);
    const outOfStock = books.filter(b => (b.stock_quantity || 0) === 0).length;
    const lowStock = books.filter(b => (b.stock_quantity || 0) > 0 && (b.stock_quantity || 0) < 10).length;
    const inventoryValue = books.reduce((sum, b) => sum + ((b.price || 0) * (b.stock_quantity || 0)), 0);

    const totalOrders = orders.length;
    const totalRevenue = orders.reduce((sum, o) => sum + (parseFloat(o.total_amount || o.total || 0)), 0);
    const pendingOrders = orders.filter(o => o.status === 'pending').length;
    const paidOrders = orders.filter(o => o.status === 'paid' || o.status === 'completed').length;

    const topReviewed = [...reviews]
      .sort((a, b) => (b.count || 0) - (a.count || 0))
      .slice(0, 5);

    const topRated = [...reviews]
      .filter(r => (r.count || 0) >= 3)
      .sort((a, b) => (b.average || 0) - (a.average || 0))
      .slice(0, 5);

    return { totalBooks, inStock, outOfStock, lowStock, inventoryValue, totalOrders, totalRevenue, pendingOrders, paidOrders, topReviewed, topRated };
  }, [books, orders, reviews]);

  if (loading) {
    return <CustomLoader />;
  }

  if (error) {
    return <div style={{ color: '#ef4444', padding: '1rem' }}>{error}</div>;
  }

  return (
    <div className="admin-dashboard">
      <div className="container">
        <div className="dashboard-header" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <h1 style={{ margin: 0 }}>التقارير</h1>
          <div>
            <Link to="/admin" className="btn btn-secondary">لوحة الإدارة</Link>
          </div>
        </div>

        <div className="dashboard-stats">
          <div className="stat-card">
            <div className="stat-number">{stats.totalBooks}</div>
            <div className="stat-label">عدد الكتب</div>
          </div>
          <div className="stat-card" style={{ background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)' }}>
            <div className="stat-number">{stats.inStock}</div>
            <div className="stat-label">إجمالي القطع بالمخزون</div>
          </div>
          <div className="stat-card" style={{ background: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)' }}>
            <div className="stat-number">{stats.lowStock}</div>
            <div className="stat-label">مخزون منخفض (&lt; 10)</div>
          </div>
          <div className="stat-card" style={{ background: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)' }}>
            <div className="stat-number">{stats.outOfStock}</div>
            <div className="stat-label">نفد المخزون</div>
          </div>
          <div className="stat-card">
            <div className="stat-number">{stats.inventoryValue.toFixed(2)}</div>
            <div className="stat-label">قيمة المخزون (ريال)</div>
          </div>
        </div>

        <div className="dashboard-stats">
          <div className="stat-card">
            <div className="stat-number">{stats.totalOrders}</div>
            <div className="stat-label">عدد الطلبات</div>
          </div>
          <div className="stat-card" style={{ background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)' }}>
            <div className="stat-number">{stats.totalRevenue.toFixed(2)}</div>
            <div className="stat-label">إجمالي الإيرادات (ريال)</div>
          </div>
          <div className="stat-card">
            <div className="stat-number">{stats.paidOrders}</div>
            <div className="stat-label">طلبات مدفوعة/مكتملة</div>
          </div>
          <div className="stat-card">
            <div className="stat-number">{stats.pendingOrders}</div>
            <div className="stat-label">طلبات قيد المعالجة</div>
          </div>
        </div>

        <div className="card" style={{ marginTop: '1rem' }}>
          <h2>الأكثر تقييماً</h2>
          <div className="admin-table">
            <div className="table-header">
              <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr', gap: '1rem' }}>
                <div>الكتاب</div>
                <div>المتوسط</div>
                <div>عدد التقييمات</div>
              </div>
            </div>
            {stats.topReviewed.map(r => (
              <div key={r.id} className="table-row">
                <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr', gap: '1rem' }}>
                  <div>#{r.id}</div>
                  <div>{(r.average || 0).toFixed(1)}</div>
                  <div>{r.count || 0}</div>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="card" style={{ marginTop: '1rem' }}>
          <h2>الأعلى تقييماً (3+ تقييم)</h2>
          <div className="admin-table">
            <div className="table-header">
              <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr', gap: '1rem' }}>
                <div>الكتاب</div>
                <div>المتوسط</div>
                <div>عدد التقييمات</div>
              </div>
            </div>
            {stats.topRated.map(r => (
              <div key={r.id} className="table-row">
                <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr', gap: '1rem' }}>
                  <div>#{r.id}</div>
                  <div>{(r.average || 0).toFixed(1)}</div>
                  <div>{r.count || 0}</div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

export default AdminReports;