-- Add publisher field to books table
ALTER TABLE books ADD COLUMN publisher VARCHAR(255) DEFAULT NULL;

-- Create index for better performance
CREATE INDEX idx_books_publisher ON books(publisher);

-- Update existing books with sample publisher data (optional)
-- UPDATE books SET publisher = 'دار النشر العربية' WHERE publisher IS NULL;
