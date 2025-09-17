import React, { useState, useEffect } from 'react';
import { apiService } from '../services/api';
import { Link } from 'react-router-dom';

const ChosenBooks = () => {
  const [books, setBooks] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchChosenBooks = async () => {
      try {
        const res = await apiService.get('/books?chosen=true');
        setBooks(res.data.items);
      } catch (error) {
        console.error("Error fetching chosen books:", error);
      } finally {
        setLoading(false);
      }
    };

    fetchChosenBooks();
  }, []);

  if (loading) {
    return <div>Loading...</div>;
  }

  return (
    <div className="chosen-books-section">
      <h2>الكتب المختارة</h2>
      <div className="books-grid">
        {books.map(book => (
          <div key={book.id} className="book-card">
            <Link to={`/book/${book.id}`}>
              <img src={/^https?:\/\//i.test(book.image_url) ? book.image_url : (book.image_url?.startsWith('/backend/') ? `http://localhost:8000${book.image_url}` : (book.image_url || '/images/book-placeholder.jpg'))} alt={book.title} />
              <h3>{book.title}</h3>
              <p>{book.author}</p>
              <span>{book.price} ريال</span>
            </Link>
          </div>
        ))}
      </div>
    </div>
  );
};

export default ChosenBooks;
