import React, { lazy, Suspense, useRef } from 'react';
import { BrowserRouter as Router, Routes, Route, useLocation } from 'react-router-dom';
import { HelmetProvider } from 'react-helmet-async';
import { AuthProvider } from './contexts/AuthContext';
import { CartProvider } from './contexts/CartContext';
import Navbar from './components/Navbar';
import Footer from './components/Footer';
import SocialSidebar from './components/SocialSidebar';
import ProtectedRoute from './components/ProtectedRoute';
import SideCart from './components/SideCart';
import './styles/dynamic-widgets.css';
import MovingBar from './components/MovingBar';
import CustomLoader from './components/CustomLoader';
import './index.css';
import './styles/animations.css';
import { TransitionGroup, CSSTransition } from 'react-transition-group';

// Lazy load all pages for better performance
const Home = lazy(() => import('./pages/Home'));
const About = lazy(() => import('./pages/About'));
const BookStore = lazy(() => import('./pages/BookStore'));
const BookDetails = lazy(() => import('./pages/BookDetails'));
const PublishingPackages = lazy(() => import('./pages/PublishingPackages'));
const Releases = lazy(() => import('./pages/Releases'));
const Blog = lazy(() => import('./pages/Blog'));
const BlogPostDetail = lazy(() => import('./pages/BlogPostDetail'));
const Contact = lazy(() => import('./pages/Contact'));
const Login = lazy(() => import('./pages/Login'));
const Signup = lazy(() => import('./pages/Signup'));
const AdminDashboard = lazy(() => import('./pages/AdminDashboard'));
const AdminSettings = lazy(() => import('./pages/AdminSettings'));
const AdminReports = lazy(() => import('./pages/AdminReports'));
const AdminFilterManagement = lazy(() => import('./pages/AdminFilterManagement'));
const AdminCategories = lazy(() => import('./pages/AdminCategories'));
const PackageDetails = lazy(() => import('./pages/PackageDetails'));
const Cart = lazy(() => import('./pages/Cart'));

function App() {
  const location = useLocation();
  const nodeRef = useRef(null);
  return (
    <HelmetProvider>
      <AuthProvider>
        <CartProvider>
          <div className="App">
            <MovingBar />
            <Navbar />
            <SocialSidebar />
            <SideCart />
            <Suspense fallback={<CustomLoader />}>
              <Routes location={location}>
              <Route path="/" element={<Home />} />
              <Route path="/about" element={<About />} />
              <Route path="/bookstore" element={<BookStore />} />
              <Route path="/book/:id" element={<BookDetails />} />
              <Route path="/packages" element={<PublishingPackages />} />
              <Route path="/releases" element={<Releases />} />
              <Route path="/blog" element={<Blog />} />
              <Route path="/blog/:id" element={<BlogPostDetail />} />
              <Route path="/contact" element={<Contact />} />
              <Route path="/login" element={<Login />} />
              <Route path="/signup" element={<Signup />} />
              <Route path="/package/:id" element={<PackageDetails />} />
              <Route path="/cart" element={<Cart />} />
              <Route
                path="/admin"
                element={
                  <ProtectedRoute requireAdmin={true}>
                    <AdminDashboard />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/admin/settings"
                element={
                  <ProtectedRoute requireAdmin={true}>
                    <AdminSettings />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/admin/reports"
                element={
                  <ProtectedRoute requireAdmin={true}>
                    <AdminReports />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/admin/filters"
                element={
                  <ProtectedRoute requireAdmin={true}>
                    <AdminFilterManagement />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/admin/categories"
                element={
                  <ProtectedRoute requireAdmin={true}>
                    <AdminCategories />
                  </ProtectedRoute>
                }
              />
              </Routes>
            </Suspense>
            <Footer />
          </div>
        </CartProvider>
      </AuthProvider>
    </HelmetProvider>
  );
}

const Root = () => <Router><App /></Router>;

export default Root;


