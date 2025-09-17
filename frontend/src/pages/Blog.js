import React, { useState, useEffect } from 'react';
import { apiService } from '../services/api';

const Blog = () => {
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchPosts();
  }, []);

  const fetchPosts = async () => {
    try {
      const response = await apiService.getBlogPosts();
      setPosts(response.data.posts);
    } catch (error) {
      console.error('Error fetching blog posts:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div style={{ padding: '4rem 0', textAlign: 'center' }}>
        <div className="container">جاري تحميل المقالات...</div>
      </div>
    );
  }

  return (
    <div style={{ padding: '4rem 0' }}>
      <div className="container">
        <h1 style={{ textAlign: 'center', marginBottom: '3rem', fontSize: '2.5rem' }}>
          المدونة
        </h1>
        
        <div style={{ maxWidth: '800px', margin: '0 auto' }}>
          {posts.map((post) => (
            <article key={post.id} className="card" style={{ marginBottom: '2rem' }}>
              <img src={post.image || '/images/news-placeholder.jpg'} alt={post.title} style={{ width: '100%', height: '200px', objectFit: 'cover', marginBottom: '1.5rem' }} />
              
              <h2 style={{ marginBottom: '1rem', color: '#2c3e50' }}>
                {post.title}
              </h2>
              
              <div style={{ 
                display: 'flex', 
                justifyContent: 'space-between', 
                marginBottom: '1rem',
                color: '#7f8c8d',
                fontSize: '0.9rem'
              }}>
                <span>بواسطة: {post.author}</span>
                <span>{new Date(post.date).toLocaleDateString('ar-SA')}</span>
              </div>
              
              <p style={{ lineHeight: '1.6', marginBottom: '1rem' }}>
                {post.content}
              </p>
              
              <button className="btn btn-primary">
                قراءة المزيد
              </button>
            </article>
          ))}
        </div>
        
        {posts.length === 0 && (
          <div style={{ textAlign: 'center', padding: '2rem' }}>
            <p>لا توجد مقالات متاحة حالياً</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default Blog;
