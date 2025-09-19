import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
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
    <div style={{ padding: '2rem 0' }}>
      <div className="container">
        <h1 style={{ 
          textAlign: 'center', 
          marginBottom: '2rem', 
          fontSize: 'clamp(1.8rem, 4vw, 2.5rem)',
          color: '#1e3a8a',
          fontFamily: 'Amiri, serif',
          lineHeight: 1.3
        }}>
          المدونة
        </h1>
        
        <div style={{ 
          maxWidth: '800px', 
          margin: '0 auto',
          padding: '0 1rem'
        }}>
          {posts.map((post) => (
            <article key={post.id} className="card" style={{ 
              marginBottom: '2rem',
              padding: '1.5rem',
              borderRadius: '12px'
            }}>
              <img 
                src={post.image || '/images/news-placeholder.jpg'} 
                alt={post.title} 
                style={{ 
                  width: '100%', 
                  height: '200px', 
                  objectFit: 'cover', 
                  marginBottom: '1.5rem',
                  borderRadius: '8px'
                }} 
              />
              
              <h2 style={{ 
                marginBottom: '1rem', 
                color: '#1e3a8a',
                fontSize: 'clamp(1.2rem, 3vw, 1.5rem)',
                lineHeight: 1.3
              }}>
                {post.title}
              </h2>
              
              <div style={{ 
                display: 'flex', 
                flexDirection: 'column',
                gap: '0.5rem',
                marginBottom: '1rem',
                color: '#6b7280',
                fontSize: '0.9rem'
              }}>
                <span>بواسطة: {post.author}</span>
                <span>{new Date(post.date).toLocaleDateString('ar-SA')}</span>
              </div>
              
              <p style={{ 
                lineHeight: '1.6', 
                marginBottom: '1.5rem',
                color: '#4a5568',
                fontSize: '0.95rem'
              }}>
                {post.content}
              </p>
              
              <Link 
                to={`/blog/${post.id}`}
                className="btn btn-primary"
                style={{
                  minHeight: '44px',
                  padding: '12px 24px',
                  fontSize: '1rem',
                  width: '100%',
                  textDecoration: 'none',
                  display: 'block',
                  textAlign: 'center'
                }}
              >
                قراءة المزيد
              </Link>
            </article>
          ))}
        </div>
        
        {posts.length === 0 && (
          <div style={{ 
            textAlign: 'center', 
            padding: '3rem 1rem',
            color: '#6b7280'
          }}>
            <div style={{ fontSize: '3rem', marginBottom: '1rem' }}>📝</div>
            <p style={{ fontSize: '1.1rem' }}>لا توجد مقالات متاحة حالياً</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default Blog;
