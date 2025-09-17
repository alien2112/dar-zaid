import React, { createContext, useState, useContext, useEffect } from 'react';

const CartContext = createContext();

export const useCart = () => useContext(CartContext);

export const CartProvider = ({ children }) => {
  const [cartItems, setCartItems] = useState([]);
  const [isCartOpen, setIsCartOpen] = useState(false);

  // Hydrate cart from localStorage on first mount
  useEffect(() => {
    try {
      const saved = localStorage.getItem('dz_cart');
      if (saved) {
        const parsed = JSON.parse(saved);
        if (Array.isArray(parsed)) {
          setCartItems(parsed);
        }
      }
    } catch {}
  }, []);

  // Persist cart to localStorage whenever it changes
  useEffect(() => {
    try {
      localStorage.setItem('dz_cart', JSON.stringify(cartItems));
    } catch {}
  }, [cartItems]);

  const openCart = () => setIsCartOpen(true);
  const closeCart = () => setIsCartOpen(false);

  const addToCart = (book) => {
    const itemToAdd = { ...book, type: book.type || 'book' };
    setCartItems(prevItems => {
      const itemInCart = prevItems.find(item => item.id === itemToAdd.id && (item.type || 'book') === (itemToAdd.type || 'book'));
      if (itemInCart) {
        return prevItems.map(item =>
          (item.id === itemToAdd.id && (item.type || 'book') === (itemToAdd.type || 'book')) ? { ...item, quantity: item.quantity + 1 } : item
        );
      } else {
        return [...prevItems, { ...itemToAdd, quantity: 1 }];
      }
    });
    openCart();
  };

  const addPackageToCart = (pkg) => {
    const itemToAdd = {
      id: pkg.id,
      type: 'package',
      title: pkg.name,
      name: pkg.name,
      price: pkg.price,
      currency: pkg.currency || 'SAR'
    };
    setCartItems(prevItems => {
      const itemInCart = prevItems.find(item => item.id === itemToAdd.id && (item.type || 'book') === 'package');
      if (itemInCart) {
        return prevItems.map(item => (item.id === itemToAdd.id && (item.type || 'book') === 'package') ? { ...item, quantity: item.quantity + 1 } : item);
      }
      return [...prevItems, { ...itemToAdd, quantity: 1 }];
    });
    openCart();
  };

  const removeFromCart = (bookId) => {
    setCartItems(prevItems => prevItems.filter(item => item.id !== bookId));
  };

  const updateQuantity = (bookId, quantity) => {
    if (quantity <= 0) {
      removeFromCart(bookId);
    } else {
      setCartItems(prevItems =>
        prevItems.map(item =>
          item.id === bookId ? { ...item, quantity } : item
        )
      );
    }
  };

  const clearCart = () => {
    setCartItems([]);
    try { localStorage.removeItem('dz_cart'); } catch {}
  };

  const getCartTotal = () => {
    return cartItems.reduce((total, item) => total + item.price * item.quantity, 0);
  };

  return (
    <CartContext.Provider
      value={{
        cartItems,
        // Backward compatibility: some pages use `cart`
        cart: cartItems,
        addToCart,
        removeFromCart,
        updateQuantity,
        clearCart,
        getCartTotal,
        isCartOpen,
        openCart,
        closeCart,
        addPackageToCart,
      }}
    >
      {children}
    </CartContext.Provider>
  );
};
