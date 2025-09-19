import React, { useState, useEffect, useRef } from 'react';
import { Link } from 'react-router-dom';
import { newsService } from '../services/newsService';
import { categoryService } from '../services/categoryService';
import { apiService } from '../services/api';
import ImageUpload from '../components/ImageUpload';
import ImageGallery from '../components/ImageGallery';
import CustomLoader from '../components/CustomLoader';
import SearchableDropdown from '../components/SearchableDropdown';

const AdminSettings = () => {
  const [categories, setCategories] = useState([]);
  const [movingBarText, setMovingBarText] = useState('');
  const [newCategory, setNewCategory] = useState('');
  const [editingCategory, setEditingCategory] = useState(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('categories');
  const [dynamicCategories, setDynamicCategories] = useState([]);
  const [newDynamicCategory, setNewDynamicCategory] = useState({
    name: '',
    type: 'top_sellers',
    description: '',
    max_items: 4,
    widget_style: 'grid',
    show_on_homepage: true,
    filter_criteria: {}
  });
  const [editingDynamicCategory, setEditingDynamicCategory] = useState(null);
  const [packages, setPackages] = useState([]);
  const [newPackage, setNewPackage] = useState({
    name: '',
    price: '',
    currency: '',
    authorShare: '',
    freeCopies: 0,
    description: '',
    specifications: [],
    services: [],
    additionalServices: [],
    additionalOffers: '',
    isActive: true,
    displayOrder: 0
  });
  const [editingPackage, setEditingPackage] = useState(null);
  const [blogPosts, setBlogPosts] = useState([]);
  const [newBlogPost, setNewBlogPost] = useState({
    title: '',
    content: '',
    author: '',
    image: '',
    status: 'published'
  });
  const [editingBlogPost, setEditingBlogPost] = useState(null);
  const [sliderImages, setSliderImages] = useState([]);
  const [newSliderImage, setNewSliderImage] = useState({
    title: '',
    subtitle: '',
    image_url: '',
    link_url: '',
    button_text: '',
    display_order: 0,
    is_active: true
  });
  const [editingSliderImage, setEditingSliderImage] = useState(null);
  const [teamPhotos, setTeamPhotos] = useState([]);
  const [newTeamPhoto, setNewTeamPhoto] = useState({
    title: '',
    description: '',
    image_url: '',
    display_order: 0,
    is_active: true
  });
  const [editingTeamPhoto, setEditingTeamPhoto] = useState(null);
  const [bookOfWeek, setBookOfWeek] = useState(null);
  const [availableBooks, setAvailableBooks] = useState([]);
  const [selectedBookId, setSelectedBookId] = useState('');
  const [booksLoading, setBooksLoading] = useState(false);
  const [booksSearchTerm, setBooksSearchTerm] = useState('');
  const [news, setNews] = useState([]);
  const [newNews, setNewNews] = useState({ title: '', content: '', type: 'news', date: '', image: '', featured: false, status: 'published' });
  const [editingNews, setEditingNews] = useState(null);
  const newNewsFileRef = useRef(null);
  const editNewsFileRef = useRef(null);
  const newSliderFileRef = useRef(null);
  const editSliderFileRef = useRef(null);
  const newTeamPhotoFileRef = useRef(null);
  const editTeamPhotoFileRef = useRef(null);
  const newBlogPostFileRef = useRef(null);
  const editBlogPostFileRef = useRef(null);

  // Social links state (stored in backend settings as JSON)
  const [socialLinks, setSocialLinks] = useState([]);
  const [newSocialLink, setNewSocialLink] = useState({ platform: '', label: '', url: '', is_active: true, display_order: 0 });
  const [editingSocialIndex, setEditingSocialIndex] = useState(null);

  const uploadImageAndSetUrl = async (file, onUrl, uploadType = 'general', entityType = null, entityId = null, entityTitle = null) => {
    if (!file) return;
    const form = new FormData();
    form.append('image', file);
    if (uploadType) form.append('upload_type', uploadType);
    if (entityType) form.append('entity_type', entityType);
    if (entityId) form.append('entity_id', entityId);
    if (entityTitle) form.append('entity_title', entityTitle);
    try {
      const res = await apiService.uploadImage(form);
      const data = res.data || {};
      onUrl(data.url);
    } catch (err) {
      alert('فشل رفع الصورة');
    }
  };

  

  // No mock categories; load from API

  useEffect(() => {
    // Load categories and news from API
    const loadCategories = async () => {
      try {
        const res = await categoryService.getCategories();
        const list = Array.isArray(res.data) ? res.data : (res.data && Array.isArray(res.data.categories) ? res.data.categories : []);
        setCategories(list);
      } catch {}
    };
    loadCategories();

    const loadDynamicCategories = async () => {
      try {
        const res = await apiService.getDynamicCategories();
        setDynamicCategories(res.data.categories || []);
      } catch {}
    };
    loadDynamicCategories();

    const loadPackages = async () => {
      try {
        const res = await apiService.getPackages(true); // admin=true to get all packages
        setPackages(res.data.packages || []);
      } catch {}
    };
    loadPackages();

    const loadBlogPosts = async () => {
      try {
        const res = await apiService.getBlogPosts(true); // admin=true to get all posts including drafts
        setBlogPosts(res.data.posts || []);
      } catch {}
    };
    loadBlogPosts();

    const loadSliderImages = async () => {
      try {
        const res = await apiService.getSliderImages(true); // admin=true to get all images including inactive
        setSliderImages(res.data.sliders || []);
      } catch {}
    };
    loadSliderImages();

    const loadTeamPhotos = async () => {
      try {
        const res = await apiService.getTeamPhotos(true); // admin=true to get all photos including inactive
        setTeamPhotos(res.data.team_photos || []);
      } catch {}
    };
    loadTeamPhotos();

    const loadBookOfWeek = async () => {
      try {
        const res = await apiService.getBookOfWeek();
        setBookOfWeek(res.data.book_of_week);
      } catch {}
    };
    loadBookOfWeek();

    const loadAvailableBooks = async (searchTerm = '') => {
      try {
        setBooksLoading(true);
        const params = { limit: 100 };
        if (searchTerm) {
          params.search = searchTerm;
        }
        const res = await apiService.getBooks(params);
        setAvailableBooks(res.data.items || []);
      } catch (error) {
        console.error('Error loading books:', error);
        setAvailableBooks([]);
      } finally {
        setBooksLoading(false);
      }
    };
    loadAvailableBooks();

    const loadMovingBarText = async () => {
      try {
        const res = await apiService.getMovingBarText();
        setMovingBarText(res.data.text || '');
      } catch {
        setMovingBarText('');
      }
    };
    loadMovingBarText();

    const loadNews = async () => {
      try {
        const res = await newsService.list(false);
        setNews(res.data.news || []);
      } catch {}
      setLoading(false);
    };
    loadNews();
    const loadSettings = async () => {
      try {
        const res = await apiService.getSettings();
        const settings = (res.data && res.data.settings) || {};
        const links = Array.isArray(settings.social_links) ? settings.social_links : [];
        setSocialLinks(links);
      } catch {}
    };
    loadSettings();
  }, []);

  const handleAddCategory = async (e) => {
    e.preventDefault();
    if (!newCategory.trim()) return;

    try {
      const res = await categoryService.addCategory({ name: newCategory.trim() });
      setCategories([...(categories || []), res.data]);
      setNewCategory('');
    } catch {
      alert('فشل إضافة التصنيف');
    }
  };

  const handleEditCategory = (category) => {
    setEditingCategory({ ...category });
  };

  const handleUpdateCategory = async (e) => {
    e.preventDefault();
    if (!editingCategory.name.trim()) return;
    try {
      const res = await categoryService.updateCategory(editingCategory.id, { name: editingCategory.name.trim() });
      setCategories(categories.map(cat => cat.id === editingCategory.id ? res.data : cat));
      setEditingCategory(null);
    } catch {
      alert('فشل تحديث التصنيف');
    }
  };

  const handleDeleteCategory = async (categoryId) => {
    if (!window.confirm('هل أنت متأكد من حذف هذا التصنيف؟ سيؤثر هذا على جميع الكتب المرتبطة به.')) return;
    try {
      await categoryService.deleteCategory(categoryId);
      setCategories(categories.filter(cat => cat.id !== categoryId));
    } catch {
      alert('فشل حذف التصنيف');
    }
  };

  const handleUpdateMovingBar = async (e) => {
    e.preventDefault();
    if (!movingBarText.trim()) return;

    try {
      await apiService.updateMovingBarText(movingBarText.trim());
      alert('تم تحديث نص الشريط المتحرك بنجاح! قم بإعادة تحميل الصفحة لرؤية التغييرات.');
    } catch (error) {
      console.error('Error updating moving bar text:', error);
      alert('فشل في تحديث نص الشريط المتحرك');
    }
  };

  // Dynamic category handlers
  const handleAddDynamicCategory = async (e) => {
    e.preventDefault();
    if (!newDynamicCategory.name.trim()) return;

    try {
      const res = await apiService.addDynamicCategory(newDynamicCategory);
      setDynamicCategories([...(dynamicCategories || []), { id: res.data.id, ...newDynamicCategory }]);
      setNewDynamicCategory({
        name: '',
        type: 'top_sellers',
        description: '',
        max_items: 4,
        widget_style: 'grid',
        show_on_homepage: true,
        filter_criteria: {}
      });
    } catch {
      alert('فشل إضافة التصنيف الديناميكي');
    }
  };

  const handleEditDynamicCategory = (category) => {
    setEditingDynamicCategory({ ...category });
  };

  const handleUpdateDynamicCategory = async (e) => {
    e.preventDefault();
    if (!editingDynamicCategory.name.trim()) return;
    try {
      await apiService.updateDynamicCategory(editingDynamicCategory.id, editingDynamicCategory);
      setDynamicCategories(dynamicCategories.map(cat =>
        cat.id === editingDynamicCategory.id ? editingDynamicCategory : cat
      ));
      setEditingDynamicCategory(null);
    } catch {
      alert('فشل تحديث التصنيف الديناميكي');
    }
  };

  const handleDeleteDynamicCategory = async (categoryId) => {
    if (!window.confirm('هل أنت متأكد من حذف هذا التصنيف الديناميكي؟')) return;
    try {
      await apiService.deleteDynamicCategory(categoryId);
      setDynamicCategories(dynamicCategories.filter(cat => cat.id !== categoryId));
    } catch {
      alert('فشل حذف التصنيف الديناميكي');
    }
  };

  // Package handlers
  const handleAddPackage = async (e) => {
    e.preventDefault();
    if (!newPackage.name.trim() || !newPackage.price) return;

    try {
      const res = await apiService.addPackage(newPackage);
      setPackages([...(packages || []), { id: res.data.id, ...newPackage }]);
      setNewPackage({
        name: '',
        price: '',
        currency: 'ريال',
        authorShare: '70%',
        freeCopies: 20,
        description: '',
        specifications: [],
        services: [],
        additionalServices: [],
        additionalOffers: '',
        isActive: true,
        displayOrder: 0
      });
    } catch {
      alert('فشل إضافة الباقة');
    }
  };

  const handleEditPackage = (pkg) => {
    setEditingPackage({ ...pkg });
  };

  const handleUpdatePackage = async (e) => {
    e.preventDefault();
    if (!editingPackage.name.trim() || !editingPackage.price) return;
    try {
      await apiService.updatePackage(editingPackage.id, editingPackage);
      setPackages(packages.map(pkg =>
        pkg.id === editingPackage.id ? editingPackage : pkg
      ));
      setEditingPackage(null);
    } catch {
      alert('فشل تحديث الباقة');
    }
  };

  const handleDeletePackage = async (packageId) => {
    if (!window.confirm('هل أنت متأكد من حذف هذه الباقة؟')) return;
    try {
      await apiService.deletePackage(packageId);
      setPackages(packages.filter(pkg => pkg.id !== packageId));
    } catch {
      alert('فشل حذف الباقة');
    }
  };

  // Blog handlers
  const handleAddBlogPost = async (e) => {
    e.preventDefault();
    if (!newBlogPost.title.trim() || !newBlogPost.content.trim()) return;

    try {
      const res = await apiService.addBlogPost(newBlogPost);
      setBlogPosts([{ id: res.data.id, ...newBlogPost, date: new Date().toISOString() }, ...(blogPosts || [])]);
      setNewBlogPost({
        title: '',
        content: '',
        author: 'إدارة الموقع',
        image: '',
        status: 'published'
      });
    } catch {
      alert('فشل إضافة المقال');
    }
  };

  const handleEditBlogPost = (post) => {
    setEditingBlogPost({ ...post });
  };

  const handleUpdateBlogPost = async (e) => {
    e.preventDefault();
    if (!editingBlogPost.title.trim() || !editingBlogPost.content.trim()) return;
    try {
      await apiService.updateBlogPost(editingBlogPost.id, editingBlogPost);
      setBlogPosts(blogPosts.map(post =>
        post.id === editingBlogPost.id ? editingBlogPost : post
      ));
      setEditingBlogPost(null);
    } catch {
      alert('فشل تحديث المقال');
    }
  };

  const handleDeleteBlogPost = async (postId) => {
    if (!window.confirm('هل أنت متأكد من حذف هذا المقال؟')) return;
    try {
      await apiService.deleteBlogPost(postId);
      setBlogPosts(blogPosts.filter(post => post.id !== postId));
    } catch {
      alert('فشل حذف المقال');
    }
  };

  // Book of the Week handlers
  const handleSetBookOfWeek = async (e) => {
    e.preventDefault();
    if (!selectedBookId) return;

    try {
      await apiService.setBookOfWeek({ book_id: parseInt(selectedBookId) });
      // Reload book of the week data
      const res = await apiService.getBookOfWeek();
      setBookOfWeek(res.data.book_of_week);
      setSelectedBookId('');
      alert('تم تحديد كتاب الأسبوع بنجاح!');
    } catch {
      alert('فشل في تحديد كتاب الأسبوع');
    }
  };

  const handleRemoveBookOfWeek = async () => {
    if (!window.confirm('هل أنت متأكد من إزالة كتاب الأسبوع الحالي؟')) return;

    try {
      await apiService.removeBookOfWeek();
      setBookOfWeek(null);
      alert('تم إزالة كتاب الأسبوع بنجاح!');
    } catch {
      alert('فشل في إزالة كتاب الأسبوع');
    }
  };

  const handleBooksSearch = async (searchTerm) => {
    setBooksSearchTerm(searchTerm);
    await loadAvailableBooks(searchTerm);
  };

  // Slider handlers
  const handleAddSliderImage = async (e) => {
    e.preventDefault();
    console.log('Form submitted with slider data:', newSliderImage);

    // Validate title
    if (!newSliderImage.title.trim()) {
      alert('يرجى إدخال عنوان الشريحة');
      return;
    }

    // Validate image URL with detailed logging
    console.log('Checking image URL:', newSliderImage.image_url);
    if (!newSliderImage.image_url || !newSliderImage.image_url.trim()) {
      console.log('Image URL validation failed - no URL provided');
      alert('يرجى إدخال رابط صورة أو رفع صورة ثم الضغط على زر "رفع الصورة"');
      return;
    }

    try {
      const res = await apiService.addSliderImage(newSliderImage);
      setSliderImages([{ id: res.data.id, ...newSliderImage }, ...(sliderImages || [])]);
      setNewSliderImage({
        title: '',
        subtitle: '',
        image_url: '',
        link_url: '',
        button_text: '',
        display_order: 0,
        is_active: true
      });
    } catch {
      alert('فشل إضافة صورة السلايدر');
    }
  };

  const handleEditSliderImage = (slider) => {
    setEditingSliderImage({ ...slider });
  };

  const handleUpdateSliderImage = async (e) => {
    e.preventDefault();
    if (!editingSliderImage.title.trim()) {
      alert('يرجى إدخال عنوان الشريحة');
      return;
    }
    if (!editingSliderImage.image_url || !editingSliderImage.image_url.trim()) {
      alert('يرجى إدخال رابط صورة أو رفع صورة');
      return;
    }
    try {
      await apiService.updateSliderImage(editingSliderImage.id, editingSliderImage);
      setSliderImages(sliderImages.map(slider =>
        slider.id === editingSliderImage.id ? editingSliderImage : slider
      ));
      setEditingSliderImage(null);
    } catch {
      alert('فشل تحديث صورة السلايدر');
    }
  };

  const handleDeleteSliderImage = async (sliderId) => {
    if (!window.confirm('هل أنت متأكد من حذف هذه الصورة؟')) return;
    try {
      await apiService.deleteSliderImage(sliderId);
      setSliderImages(sliderImages.filter(slider => slider.id !== sliderId));
    } catch {
      alert('فشل حذف صورة السلايدر');
    }
  };

  // Team photos handlers
  const handleAddTeamPhoto = async (e) => {
    e.preventDefault();
    console.log('Form submitted with team photo data:', newTeamPhoto);

    // Validate title
    if (!newTeamPhoto.title.trim()) {
      alert('يرجى إدخال عنوان الصورة');
      return;
    }

    // Validate image URL
    console.log('Checking image URL:', newTeamPhoto.image_url);
    if (!newTeamPhoto.image_url || !newTeamPhoto.image_url.trim()) {
      console.log('Image URL validation failed - no URL provided');
      alert('يرجى إدخال رابط صورة أو رفع صورة ثم الضغط على زر "رفع الصورة"');
      return;
    }

    try {
      const res = await apiService.addTeamPhoto(newTeamPhoto);
      setTeamPhotos([{ id: res.data.id, ...newTeamPhoto }, ...(teamPhotos || [])]);
      setNewTeamPhoto({
        title: '',
        description: '',
        image_url: '',
        display_order: 0,
        is_active: true
      });
      alert('تم إضافة صورة الفريق بنجاح');
    } catch {
      alert('فشل إضافة صورة الفريق');
    }
  };

  const handleEditTeamPhoto = (photo) => {
    setEditingTeamPhoto({ ...photo });
  };

  const handleUpdateTeamPhoto = async (e) => {
    e.preventDefault();
    if (!editingTeamPhoto.title.trim()) {
      alert('يرجى إدخال عنوان الصورة');
      return;
    }
    if (!editingTeamPhoto.image_url || !editingTeamPhoto.image_url.trim()) {
      alert('يرجى إدخال رابط صورة أو رفع صورة ثم الضغط على زر "رفع الصورة"');
      return;
    }
    try {
      await apiService.updateTeamPhoto(editingTeamPhoto.id, editingTeamPhoto);
      setTeamPhotos(teamPhotos.map(photo =>
        photo.id === editingTeamPhoto.id ? editingTeamPhoto : photo
      ));
      setEditingTeamPhoto(null);
      alert('تم تحديث صورة الفريق بنجاح');
    } catch {
      alert('فشل تحديث صورة الفريق');
    }
  };

  const handleDeleteTeamPhoto = async (photoId) => {
    if (!window.confirm('هل أنت متأكد من حذف هذه الصورة؟')) return;
    try {
      await apiService.deleteTeamPhoto(photoId);
      setTeamPhotos(teamPhotos.filter(photo => photo.id !== photoId));
    } catch {
      alert('فشل حذف صورة الفريق');
    }
  };

  // Social links handlers
  const handleAddSocialLink = (e) => {
    e.preventDefault();
    if (!newSocialLink.url.trim()) return;
    const next = [...socialLinks, { ...newSocialLink, display_order: Number(newSocialLink.display_order) || 0 }];
    setSocialLinks(next);
    setNewSocialLink({ platform: '', label: '', url: '', is_active: true, display_order: 0 });
  };

  const handleEditSocialLink = (index) => {
    setEditingSocialIndex(index);
    const item = socialLinks[index];
    setNewSocialLink({ ...item });
  };

  const handleUpdateSocialLink = (e) => {
    e.preventDefault();
    if (editingSocialIndex === null) return;
    const next = [...socialLinks];
    next[editingSocialIndex] = { ...newSocialLink, display_order: Number(newSocialLink.display_order) || 0 };
    setSocialLinks(next);
    setEditingSocialIndex(null);
    setNewSocialLink({ platform: '', label: '', url: '', is_active: true, display_order: 0 });
  };

  const handleDeleteSocialLink = (index) => {
    if (!window.confirm('حذف هذا الرابط الاجتماعي؟')) return;
    const next = socialLinks.filter((_, i) => i !== index);
    setSocialLinks(next);
  };

  const moveSocial = (index, dir) => {
    const next = [...socialLinks];
    const swapWith = index + dir;
    if (swapWith < 0 || swapWith >= next.length) return;
    [next[index], next[swapWith]] = [next[swapWith], next[index]];
    setSocialLinks(next);
  };

  const handleSaveSocialLinks = async () => {
    try {
      const normalized = socialLinks.map((l, i) => ({ ...l, display_order: Number(l.display_order ?? i) }));
      await apiService.updateSettings({ social_links: normalized });
      alert('تم حفظ روابط التواصل بنجاح');
    } catch {
      alert('فشل حفظ روابط التواصل');
    }
  };

  if (loading) {
    return <CustomLoader />;
  }

  return (
    <div className="admin-dashboard">
      <div className="container">
        <h1 className="dashboard-header">الإعدادات</h1>


        {/* Tabs */}
        <div style={{ marginBottom: '1rem', display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
          <button className={`btn ${activeTab==='categories'?'btn-primary':'btn-secondary'}`} onClick={() => setActiveTab('categories')}>التصنيفات</button>
          <button className={`btn ${activeTab==='dynamic'?'btn-primary':'btn-secondary'}`} onClick={() => setActiveTab('dynamic')}>التصنيفات الديناميكية</button>
          <button className={`btn ${activeTab==='packages'?'btn-primary':'btn-secondary'}`} onClick={() => setActiveTab('packages')}>الباقات</button>
          <button className={`btn ${activeTab==='blog'?'btn-primary':'btn-secondary'}`} onClick={() => setActiveTab('blog')}>المدونة</button>
          <button className={`btn ${activeTab==='slider'?'btn-primary':'btn-secondary'}`} onClick={() => setActiveTab('slider')}>صور السلايدر</button>
          <button className={`btn ${activeTab==='team-photos'?'btn-primary':'btn-secondary'}`} onClick={() => setActiveTab('team-photos')}>صور الفريق</button>
          <button className={`btn ${activeTab==='book-of-week'?'btn-primary':'btn-secondary'}`} onClick={() => setActiveTab('book-of-week')}>كتاب الأسبوع</button>
          <button className={`btn ${activeTab==='news'?'btn-primary':'btn-secondary'}`} onClick={() => setActiveTab('news')}>الإصدارات/الأخبار</button>
          <button className={`btn ${activeTab==='images'?'btn-primary':'btn-secondary'}`} onClick={() => setActiveTab('images')}>معرض الصور</button>
          <button className={`btn ${activeTab==='social'?'btn-primary':'btn-secondary'}`} onClick={() => setActiveTab('social')}>روابط التواصل</button>
        </div>

        <div className="card" style={{ marginBottom: '2rem' }}>
          <h2>الشريط المتحرك</h2>
          <form onSubmit={handleUpdateMovingBar} className="modern-form" style={{ padding: 0, boxShadow: 'none' }}>
            <div className="form-group">
              <label htmlFor="movingBarText">نص الشريط المتحرك</label>
              <input
                type="text"
                id="movingBarText"
                value={movingBarText}
                onChange={(e) => setMovingBarText(e.target.value)}
                placeholder="النص الذي يظهر في الشريط المتحرك"
              />
            </div>
            <button type="submit" className="btn btn-primary">
              حفظ التغييرات
            </button>
          </form>
        </div>

        {activeTab === 'categories' && (
        <div className="card">
          <h2>تصنيفات الكتب</h2>
          <div className="modern-form" style={{ padding: 0, boxShadow: 'none' }}>
            <form onSubmit={handleAddCategory}>
              <div className="form-grid">
                <div className="form-group">
                  <label htmlFor="newCategory">إضافة تصنيف جديد</label>
                  <input
                    type="text"
                    id="newCategory"
                    value={newCategory}
                    onChange={(e) => setNewCategory(e.target.value)}
                    placeholder="اسم التصنيف الجديد"
                  />
                </div>
                <div className="form-group">
                  <button type="submit" className="btn btn-primary" style={{ width: '100%' }}>
                    إضافة
                  </button>
                </div>
              </div>
            </form>
          </div>

          <div className="admin-table">
            <div className="table-header">
              <div>اسم التصنيف</div>
              <div>إجراءات</div>
            </div>
            {categories.map(category => (
              <div className="table-row" key={category.id}>
                <div>{category.name}</div>
                <div className="table-actions">
                  <button className="btn btn-small btn-secondary" onClick={() => handleEditCategory(category)}>
                    تعديل
                  </button>
                  <button className="btn btn-small btn-delete" onClick={() => handleDeleteCategory(category.id)}>
                    حذف
                  </button>
                </div>
              </div>
            ))}
          </div>

          {editingCategory && (
            <div className="modal-overlay" onClick={() => setEditingCategory(null)}>
              <div className="modal-content" onClick={(e) => e.stopPropagation()}>
                <h3>تعديل التصنيف</h3>
                <form onSubmit={handleUpdateCategory} className="modern-form" style={{ padding: 0, boxShadow: 'none' }}>
                  <div className="form-group">
                    <label htmlFor="editCategoryName">اسم التصنيف</label>
                    <input
                      type="text"
                      id="editCategoryName"
                      value={editingCategory.name}
                      onChange={(e) => setEditingCategory({ ...editingCategory, name: e.target.value })}
                      placeholder="اسم التصنيف"
                    />
                  </div>
                  <div className="form-actions">
                    <button type="submit" className="btn btn-primary">
                      حفظ التغييرات
                    </button>
                    <button type="button" className="btn btn-secondary" onClick={() => setEditingCategory(null)}>
                      إلغاء
                    </button>
                  </div>
                </form>
              </div>
            </div>
          )}
        </div>
        )}
        {activeTab === 'social' && (
        <div className="card">
          <h2>روابط التواصل الاجتماعي</h2>
          <div className="modern-form" style={{ padding: 0, boxShadow: 'none' }}>
            <form onSubmit={editingSocialIndex === null ? handleAddSocialLink : handleUpdateSocialLink}>
              <div className="form-grid">
                <div className="form-group">
                  <label>المنصة</label>
                  <input type="text" value={newSocialLink.platform} onChange={(e) => setNewSocialLink({ ...newSocialLink, platform: e.target.value })} placeholder="مثال: X, Instagram, TikTok, YouTube" />
                </div>
                <div className="form-group">
                  <label>الاسم الظاهر</label>
                  <input type="text" value={newSocialLink.label} onChange={(e) => setNewSocialLink({ ...newSocialLink, label: e.target.value })} placeholder="النص على الزر" />
                </div>
                <div className="form-group" style={{ gridColumn: '1 / -1' }}>
                  <label>الرابط</label>
                  <input type="url" value={newSocialLink.url} onChange={(e) => setNewSocialLink({ ...newSocialLink, url: e.target.value })} placeholder="https://..." required />
                </div>
                <div className="form-group">
                  <label>ترتيب العرض</label>
                  <input type="number" min="0" value={newSocialLink.display_order} onChange={(e) => setNewSocialLink({ ...newSocialLink, display_order: parseInt(e.target.value || '0') })} />
                </div>
                <div className="form-group">
                  <label>
                    <input type="checkbox" checked={!!newSocialLink.is_active} onChange={(e) => setNewSocialLink({ ...newSocialLink, is_active: e.target.checked })} /> {' '}نشط
                  </label>
                </div>
              </div>
              <div className="form-actions">
                <button type="submit" className="btn btn-primary">{editingSocialIndex === null ? 'إضافة رابط' : 'تحديث الرابط'}</button>
                {editingSocialIndex !== null && (
                  <button type="button" className="btn btn-secondary" onClick={() => { setEditingSocialIndex(null); setNewSocialLink({ platform: '', label: '', url: '', is_active: true, display_order: 0 }); }}>إلغاء</button>
                )}
                <button type="button" className="btn" onClick={handleSaveSocialLinks}>حفظ جميع الروابط</button>
              </div>
            </form>
          </div>
          <div className="admin-table" style={{ marginTop: '1rem' }}>
            <div className="table-header">
              <div>المنصة</div>
              <div>الاسم</div>
              <div>الرابط</div>
              <div>الترتيب</div>
              <div>الحالة</div>
              <div>إجراءات</div>
            </div>
            {(socialLinks || []).map((link, index) => (
              <div className="table-row" key={`${link.platform}-${index}`}>
                <div>{link.platform || '-'}</div>
                <div>{link.label || '-'}</div>
                <div style={{ direction: 'ltr' }}>{link.url}</div>
                <div>{link.display_order ?? index}</div>
                <div>{link.is_active ? 'نشط' : 'غير نشط'}</div>
                <div className="table-actions" style={{ display: 'flex', gap: '0.25rem' }}>
                  <button className="btn btn-small" onClick={() => moveSocial(index, -1)}>▲</button>
                  <button className="btn btn-small" onClick={() => moveSocial(index, 1)}>▼</button>
                  <button className="btn btn-small btn-secondary" onClick={() => handleEditSocialLink(index)}>تعديل</button>
                  <button className="btn btn-small btn-delete" onClick={() => handleDeleteSocialLink(index)}>حذف</button>
                </div>
              </div>
            ))}
          </div>
        </div>
        )}

        {activeTab === 'dynamic' && (
        <div className="card">
          <h2>التصنيفات الديناميكية</h2>
          <div className="modern-form" style={{ padding: 0, boxShadow: 'none' }}>
            <form onSubmit={handleAddDynamicCategory}>
              <div className="form-grid">
                <div className="form-group">
                  <label>اسم التصنيف</label>
                  <input
                    type="text"
                    value={newDynamicCategory.name}
                    onChange={(e) => setNewDynamicCategory({ ...newDynamicCategory, name: e.target.value })}
                    placeholder="مثال: الأكثر مبيعاً"
                    required
                  />
                </div>
                <div className="form-group">
                  <label>نوع التصنيف</label>
                  <select
                    value={newDynamicCategory.type}
                    onChange={(e) => setNewDynamicCategory({ ...newDynamicCategory, type: e.target.value })}
                  >
                    <option value="top_sellers">الأكثر مبيعاً</option>
                    <option value="recent_releases">أحدث الإصدارات</option>
                    <option value="discounted">عروض وخصومات</option>
                    <option value="featured">مختارات المحررين</option>
                    <option value="category_based">حسب التصنيف</option>
                    <option value="author_collection">مجموعة مؤلف</option>
                    <option value="custom">مخصص</option>
                  </select>
                </div>
                <div className="form-group">
                  <label>عدد العناصر المعروضة</label>
                  <input
                    type="number"
                    min="1"
                    max="20"
                    value={newDynamicCategory.max_items}
                    onChange={(e) => setNewDynamicCategory({ ...newDynamicCategory, max_items: parseInt(e.target.value) })}
                  />
                </div>
                <div className="form-group">
                  <label>نمط العرض</label>
                  <select
                    value={newDynamicCategory.widget_style}
                    onChange={(e) => setNewDynamicCategory({ ...newDynamicCategory, widget_style: e.target.value })}
                  >
                    <option value="grid">شبكة</option>
                    <option value="carousel">دوار</option>
                    <option value="list">قائمة</option>
                    <option value="banner">بانر</option>
                  </select>
                </div>
                <div className="form-group" style={{ gridColumn: '1 / -1' }}>
                  <label>الوصف (اختياري)</label>
                  <textarea
                    rows="2"
                    value={newDynamicCategory.description}
                    onChange={(e) => setNewDynamicCategory({ ...newDynamicCategory, description: e.target.value })}
                    placeholder="وصف التصنيف"
                  />
                </div>
                <div className="form-group">
                  <label>
                    <input
                      type="checkbox"
                      checked={newDynamicCategory.show_on_homepage}
                      onChange={(e) => setNewDynamicCategory({ ...newDynamicCategory, show_on_homepage: e.target.checked })}
                    />
                    {' '}عرض في الصفحة الرئيسية
                  </label>
                </div>
              </div>
              <button type="submit" className="btn btn-primary">إضافة التصنيف الديناميكي</button>
            </form>
          </div>

          <div className="admin-table" style={{ marginTop: '1rem' }}>
            <div className="table-header">
              <div>اسم التصنيف</div>
              <div>النوع</div>
              <div>عدد العناصر</div>
              <div>نمط العرض</div>
              <div>نشط</div>
              <div>إجراءات</div>
            </div>
            {dynamicCategories.map(category => (
              <div className="table-row" key={category.id}>
                <div>{category.name}</div>
                <div>
                  {{
                    'top_sellers': 'الأكثر مبيعاً',
                    'recent_releases': 'أحدث الإصدارات',
                    'discounted': 'عروض وخصومات',
                    'featured': 'مختارات المحررين',
                    'category_based': 'حسب التصنيف',
                    'author_collection': 'مجموعة مؤلف',
                    'custom': 'مخصص'
                  }[category.type]}
                </div>
                <div>{category.max_items}</div>
                <div>
                  {{
                    'grid': 'شبكة',
                    'carousel': 'دوار',
                    'list': 'قائمة',
                    'banner': 'بانر'
                  }[category.widget_style]}
                </div>
                <div>{category.is_active ? 'نعم' : 'لا'}</div>
                <div className="table-actions">
                  <button className="btn btn-small btn-secondary" onClick={() => handleEditDynamicCategory(category)}>
                    تعديل
                  </button>
                  <button className="btn btn-small btn-delete" onClick={() => handleDeleteDynamicCategory(category.id)}>
                    حذف
                  </button>
                </div>
              </div>
            ))}
          </div>

          {editingDynamicCategory && (
            <div className="modal-overlay" onClick={() => setEditingDynamicCategory(null)}>
              <div className="modal-content" onClick={(e) => e.stopPropagation()}>
                <h3>تعديل التصنيف الديناميكي</h3>
                <form onSubmit={handleUpdateDynamicCategory} className="modern-form" style={{ padding: 0, boxShadow: 'none' }}>
                  <div className="form-grid">
                    <div className="form-group">
                      <label>اسم التصنيف</label>
                      <input
                        type="text"
                        value={editingDynamicCategory.name}
                        onChange={(e) => setEditingDynamicCategory({ ...editingDynamicCategory, name: e.target.value })}
                        required
                      />
                    </div>
                    <div className="form-group">
                      <label>نوع التصنيف</label>
                      <select
                        value={editingDynamicCategory.type}
                        onChange={(e) => setEditingDynamicCategory({ ...editingDynamicCategory, type: e.target.value })}
                      >
                        <option value="top_sellers">الأكثر مبيعاً</option>
                        <option value="recent_releases">أحدث الإصدارات</option>
                        <option value="discounted">عروض وخصومات</option>
                        <option value="featured">مختارات المحررين</option>
                        <option value="category_based">حسب التصنيف</option>
                        <option value="author_collection">مجموعة مؤلف</option>
                        <option value="custom">مخصص</option>
                      </select>
                    </div>
                    <div className="form-group">
                      <label>عدد العناصر المعروضة</label>
                      <input
                        type="number"
                        min="1"
                        max="20"
                        value={editingDynamicCategory.max_items || 4}
                        onChange={(e) => setEditingDynamicCategory({ ...editingDynamicCategory, max_items: parseInt(e.target.value) })}
                      />
                    </div>
                    <div className="form-group">
                      <label>نمط العرض</label>
                      <select
                        value={editingDynamicCategory.widget_style || 'grid'}
                        onChange={(e) => setEditingDynamicCategory({ ...editingDynamicCategory, widget_style: e.target.value })}
                      >
                        <option value="grid">شبكة</option>
                        <option value="carousel">دوار</option>
                        <option value="list">قائمة</option>
                        <option value="banner">بانر</option>
                      </select>
                    </div>
                    <div className="form-group" style={{ gridColumn: '1 / -1' }}>
                      <label>الوصف (اختياري)</label>
                      <textarea
                        rows="2"
                        value={editingDynamicCategory.description || ''}
                        onChange={(e) => setEditingDynamicCategory({ ...editingDynamicCategory, description: e.target.value })}
                      />
                    </div>
                    <div className="form-group">
                      <label>
                        <input
                          type="checkbox"
                          checked={editingDynamicCategory.show_on_homepage !== false}
                          onChange={(e) => setEditingDynamicCategory({ ...editingDynamicCategory, show_on_homepage: e.target.checked })}
                        />
                        {' '}عرض في الصفحة الرئيسية
                      </label>
                    </div>
                    <div className="form-group">
                      <label>
                        <input
                          type="checkbox"
                          checked={editingDynamicCategory.is_active !== false}
                          onChange={(e) => setEditingDynamicCategory({ ...editingDynamicCategory, is_active: e.target.checked })}
                        />
                        {' '}نشط
                      </label>
                    </div>
                  </div>
                  <div className="form-actions">
                    <button type="submit" className="btn btn-primary">
                      حفظ التغييرات
                    </button>
                    <button type="button" className="btn btn-secondary" onClick={() => setEditingDynamicCategory(null)}>
                      إلغاء
                    </button>
                  </div>
                </form>
              </div>
            </div>
          )}
        </div>
        )}

        {activeTab === 'packages' && (
        <div className="card">
          <h2>إدارة الباقات</h2>
          <div className="modern-form" style={{ padding: 0, boxShadow: 'none' }}>
            <form onSubmit={handleAddPackage}>
              <div className="form-grid">
                <div className="form-group">
                  <label>اسم الباقة</label>
                  <input
                    type="text"
                    value={newPackage.name}
                    onChange={(e) => setNewPackage({ ...newPackage, name: e.target.value })}
                    placeholder="مثال: باقة البرونز"
                    required
                  />
                </div>
                <div className="form-group">
                  <label>السعر</label>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    value={newPackage.price}
                    onChange={(e) => setNewPackage({ ...newPackage, price: e.target.value })}
                    placeholder="500.00"
                    required
                  />
                </div>
                <div className="form-group">
                  <label>العملة</label>
                  <select
                    value={newPackage.currency}
                    onChange={(e) => setNewPackage({ ...newPackage, currency: e.target.value })}
                  >
                    <option value="ريال">ريال سعودي</option>
                    <option value="دولار">دولار أمريكي</option>
                    <option value="يورو">يورو</option>
                  </select>
                </div>
                <div className="form-group">
                  <label>نسبة المؤلف</label>
                  <input
                    type="text"
                    value={newPackage.authorShare}
                    onChange={(e) => setNewPackage({ ...newPackage, authorShare: e.target.value })}
                    placeholder="70%"
                  />
                </div>
                <div className="form-group">
                  <label>النسخ المجانية</label>
                  <input
                    type="number"
                    min="0"
                    value={newPackage.freeCopies}
                    onChange={(e) => setNewPackage({ ...newPackage, freeCopies: parseInt(e.target.value) })}
                  />
                </div>
                <div className="form-group">
                  <label>ترتيب العرض</label>
                  <input
                    type="number"
                    min="0"
                    value={newPackage.displayOrder}
                    onChange={(e) => setNewPackage({ ...newPackage, displayOrder: parseInt(e.target.value) })}
                  />
                </div>
                <div className="form-group" style={{ gridColumn: '1 / -1' }}>
                  <label>الوصف</label>
                  <textarea
                    rows="3"
                    value={newPackage.description}
                    onChange={(e) => setNewPackage({ ...newPackage, description: e.target.value })}
                    placeholder="وصف الباقة"
                  />
                </div>
                <div className="form-group" style={{ gridColumn: '1 / -1' }}>
                  <label>العروض الإضافية</label>
                  <textarea
                    rows="2"
                    value={newPackage.additionalOffers}
                    onChange={(e) => setNewPackage({ ...newPackage, additionalOffers: e.target.value })}
                    placeholder="عروض وميزات إضافية"
                  />
                </div>
                <div className="form-group">
                  <label>
                    <input
                      type="checkbox"
                      checked={newPackage.isActive}
                      onChange={(e) => setNewPackage({ ...newPackage, isActive: e.target.checked })}
                    />
                    {' '}نشط
                  </label>
                </div>
              </div>
              <button type="submit" className="btn btn-primary">إضافة الباقة</button>
            </form>
          </div>

          <div className="admin-table" style={{ marginTop: '1rem' }}>
            <div className="table-header">
              <div>اسم الباقة</div>
              <div>السعر</div>
              <div>نسبة المؤلف</div>
              <div>النسخ المجانية</div>
              <div>نشط</div>
              <div>إجراءات</div>
            </div>
            {packages.map(pkg => (
              <div className="table-row" key={pkg.id}>
                <div>{pkg.name}</div>
                <div>{pkg.price} {pkg.currency}</div>
                <div>{pkg.authorShare}</div>
                <div>{pkg.freeCopies}</div>
                <div>{pkg.isActive ? 'نعم' : 'لا'}</div>
                <div className="table-actions">
                  <button className="btn btn-small btn-secondary" onClick={() => handleEditPackage(pkg)}>
                    تعديل
                  </button>
                  <button className="btn btn-small btn-delete" onClick={() => handleDeletePackage(pkg.id)}>
                    حذف
                  </button>
                </div>
              </div>
            ))}
          </div>

          {editingPackage && (
            <div className="modal-overlay" onClick={() => setEditingPackage(null)}>
              <div className="modal-content" onClick={(e) => e.stopPropagation()}>
                <h3>تعديل الباقة</h3>
                <form onSubmit={handleUpdatePackage} className="modern-form" style={{ padding: 0, boxShadow: 'none' }}>
                  <div className="form-grid">
                    <div className="form-group">
                      <label>اسم الباقة</label>
                      <input
                        type="text"
                        value={editingPackage.name}
                        onChange={(e) => setEditingPackage({ ...editingPackage, name: e.target.value })}
                        required
                      />
                    </div>
                    <div className="form-group">
                      <label>السعر</label>
                      <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={editingPackage.price}
                        onChange={(e) => setEditingPackage({ ...editingPackage, price: e.target.value })}
                        required
                      />
                    </div>
                    <div className="form-group">
                      <label>العملة</label>
                      <select
                        value={editingPackage.currency}
                        onChange={(e) => setEditingPackage({ ...editingPackage, currency: e.target.value })}
                      >
                        <option value="ريال">ريال سعودي</option>
                        <option value="دولار">دولار أمريكي</option>
                        <option value="يورو">يورو</option>
                      </select>
                    </div>
                    <div className="form-group">
                      <label>نسبة المؤلف</label>
                      <input
                        type="text"
                        value={editingPackage.authorShare}
                        onChange={(e) => setEditingPackage({ ...editingPackage, authorShare: e.target.value })}
                      />
                    </div>
                    <div className="form-group">
                      <label>النسخ المجانية</label>
                      <input
                        type="number"
                        min="0"
                        value={editingPackage.freeCopies}
                        onChange={(e) => setEditingPackage({ ...editingPackage, freeCopies: parseInt(e.target.value) })}
                      />
                    </div>
                    <div className="form-group">
                      <label>ترتيب العرض</label>
                      <input
                        type="number"
                        min="0"
                        value={editingPackage.displayOrder || 0}
                        onChange={(e) => setEditingPackage({ ...editingPackage, displayOrder: parseInt(e.target.value) })}
                      />
                    </div>
                    <div className="form-group" style={{ gridColumn: '1 / -1' }}>
                      <label>الوصف</label>
                      <textarea
                        rows="3"
                        value={editingPackage.description || ''}
                        onChange={(e) => setEditingPackage({ ...editingPackage, description: e.target.value })}
                      />
                    </div>
                    <div className="form-group" style={{ gridColumn: '1 / -1' }}>
                      <label>العروض الإضافية</label>
                      <textarea
                        rows="2"
                        value={editingPackage.additionalOffers || ''}
                        onChange={(e) => setEditingPackage({ ...editingPackage, additionalOffers: e.target.value })}
                      />
                    </div>
                    <div className="form-group">
                      <label>
                        <input
                          type="checkbox"
                          checked={editingPackage.isActive !== false}
                          onChange={(e) => setEditingPackage({ ...editingPackage, isActive: e.target.checked })}
                        />
                        {' '}نشط
                      </label>
                    </div>
                  </div>
                  <div className="form-actions">
                    <button type="submit" className="btn btn-primary">
                      حفظ التغييرات
                    </button>
                    <button type="button" className="btn btn-secondary" onClick={() => setEditingPackage(null)}>
                      إلغاء
                    </button>
                  </div>
                </form>
              </div>
            </div>
          )}
        </div>
        )}

        {activeTab === 'blog' && (
        <div className="card">
          <h2>إدارة المدونة</h2>
          <div className="modern-form" style={{ padding: 0, boxShadow: 'none' }}>
            <form onSubmit={handleAddBlogPost}>
              <div className="form-grid">
                <div className="form-group">
                  <label>عنوان المقال</label>
                  <input
                    type="text"
                    value={newBlogPost.title}
                    onChange={(e) => setNewBlogPost({ ...newBlogPost, title: e.target.value })}
                    placeholder="مثال: نصائح للكتابة الإبداعية"
                    required
                  />
                </div>
                <div className="form-group">
                  <label>المؤلف</label>
                  <input
                    type="text"
                    value={newBlogPost.author}
                    onChange={(e) => setNewBlogPost({ ...newBlogPost, author: e.target.value })}
                    placeholder="اسم المؤلف"
                  />
                </div>
                <div className="form-group">
                  <label>صورة المقال</label>
                  <ImageUpload
                    uploadType="blog_image"
                    entityType="blog"
                    entityTitle={newBlogPost.title}
                    onImageSelect={(file) => {
                      if (file && file.url) {
                        setNewBlogPost({ ...newBlogPost, image: file.url });
                      }
                    }}
                    onImageUpload={(result) => {
                      if (result && result.url) {
                        setNewBlogPost({ ...newBlogPost, image: result.url });
                      }
                    }}
                    onError={(error) => {
                      console.error('Upload failed:', error);
                      alert('فشل رفع الصورة: ' + error);
                    }}
                    placeholder="اختر صورة المقال أو اسحبها هنا"
                  />
                </div>
                <div className="form-group">
                  <label>حالة المقال</label>
                  <select
                    value={newBlogPost.status}
                    onChange={(e) => setNewBlogPost({ ...newBlogPost, status: e.target.value })}
                  >
                    <option value="published">منشور</option>
                    <option value="draft">مسودة</option>
                  </select>
                </div>
              </div>
              <div className="form-group">
                <label>محتوى المقال</label>
                <textarea
                  rows="6"
                  value={newBlogPost.content}
                  onChange={(e) => setNewBlogPost({ ...newBlogPost, content: e.target.value })}
                  placeholder="محتوى المقال..."
                  required
                />
              </div>
              <button type="submit" className="btn btn-primary">إضافة مقال</button>
            </form>
          </div>

          <div className="admin-table">
            <div className="table-header">
              <div>العنوان</div>
              <div>المؤلف</div>
              <div>التاريخ</div>
              <div>الحالة</div>
              <div>الإجراءات</div>
            </div>
            {(blogPosts || []).map((post) => (
              <div key={post.id} className="table-row">
                <div>{post.title}</div>
                <div>{post.author}</div>
                <div>{new Date(post.date || post.createdAt).toLocaleDateString('ar-SA')}</div>
                <div>
                  <span className={`status ${post.status === 'published' ? 'status-published' : 'status-draft'}`}>
                    {post.status === 'published' ? 'منشور' : 'مسودة'}
                  </span>
                </div>
                <div>
                  <button className="btn btn-small btn-secondary" onClick={() => handleEditBlogPost(post)}>تعديل</button>
                  <button className="btn btn-small btn-delete" onClick={() => handleDeleteBlogPost(post.id)}>حذف</button>
                </div>
              </div>
            ))}
          </div>

          {editingBlogPost && (
            <div className="modal">
              <div className="modal-content">
                <h3>تعديل المقال</h3>
                <form onSubmit={handleUpdateBlogPost}>
                  <div className="form-grid">
                    <div className="form-group">
                      <label>عنوان المقال</label>
                      <input
                        type="text"
                        value={editingBlogPost.title}
                        onChange={(e) => setEditingBlogPost({ ...editingBlogPost, title: e.target.value })}
                        required
                      />
                    </div>
                    <div className="form-group">
                      <label>المؤلف</label>
                      <input
                        type="text"
                        value={editingBlogPost.author}
                        onChange={(e) => setEditingBlogPost({ ...editingBlogPost, author: e.target.value })}
                      />
                    </div>
                    <div className="form-group">
                      <label>صورة المقال</label>
                      <ImageUpload
                        uploadType="blog_image"
                        entityType="blog"
                        entityId={editingBlogPost.id}
                        onImageSelect={(file) => {
                          if (file && file.url) {
                            setEditingBlogPost({ ...editingBlogPost, image: file.url });
                          }
                        }}
                        onImageUpload={(result) => {
                          if (result && result.url) {
                            setEditingBlogPost({ ...editingBlogPost, image: result.url });
                          }
                        }}
                        onError={(error) => {
                          console.error('Upload failed:', error);
                          alert('فشل رفع الصورة: ' + error);
                        }}
                        placeholder="اختر صورة المقال أو اسحبها هنا"
                      />
                    </div>
                    <div className="form-group">
                      <label>حالة المقال</label>
                      <select
                        value={editingBlogPost.status}
                        onChange={(e) => setEditingBlogPost({ ...editingBlogPost, status: e.target.value })}
                      >
                        <option value="published">منشور</option>
                        <option value="draft">مسودة</option>
                      </select>
                    </div>
                  </div>
                  <div className="form-group">
                    <label>محتوى المقال</label>
                    <textarea
                      rows="6"
                      value={editingBlogPost.content}
                      onChange={(e) => setEditingBlogPost({ ...editingBlogPost, content: e.target.value })}
                      required
                    />
                  </div>
                  <div className="modal-actions">
                    <button type="submit" className="btn btn-primary">حفظ التغييرات</button>
                    <button type="button" className="btn btn-secondary" onClick={() => setEditingBlogPost(null)}>إلغاء</button>
                  </div>
                </form>
              </div>
            </div>
          )}
        </div>
        )}

        {activeTab === 'slider' && (
        <div className="card">
          <h2>إدارة صور السلايدر</h2>
          <div className="modern-form" style={{ padding: 0, boxShadow: 'none' }}>
            <form onSubmit={handleAddSliderImage}>
              <div className="form-grid">
                <div className="form-group">
                  <label>العنوان</label>
                  <input
                    type="text"
                    value={newSliderImage.title}
                    onChange={(e) => setNewSliderImage({ ...newSliderImage, title: e.target.value })}
                    placeholder="عنوان الصورة"
                    required
                  />
                </div>
                <div className="form-group">
                  <label>العنوان الفرعي</label>
                  <input
                    type="text"
                    value={newSliderImage.subtitle}
                    onChange={(e) => setNewSliderImage({ ...newSliderImage, subtitle: e.target.value })}
                    placeholder="العنوان الفرعي (اختياري)"
                  />
                </div>
                <div className="form-group">
                  <label>صورة الشريحة</label>
                  {newSliderImage.image_url && (
                    <div style={{
                      marginBottom: '0.5rem',
                      padding: '0.5rem',
                      background: '#f0f9ff',
                      border: '1px solid #3b82f6',
                      borderRadius: '4px',
                      fontSize: '12px'
                    }}>
                      <strong>الصورة المحددة:</strong> {newSliderImage.image_url}
                    </div>
                  )}
                  <ImageUpload
                    uploadType="slider_image"
                    entityType="slider"
                    entityTitle={newSliderImage.title}
                    onImageSelect={(file) => {
                      console.log('ImageUpload onImageSelect called with:', file);
                      if (file && file.url) {
                        console.log('Setting image_url to:', file.url);
                        setNewSliderImage(prev => ({ ...prev, image_url: file.url }));
                      } else if (file === null) {
                        console.log('Image selection cleared');
                        setNewSliderImage(prev => ({ ...prev, image_url: '' }));
                      }
                    }}
                    onImageUpload={(result) => {
                      console.log('ImageUpload onImageUpload called with:', result);
                      if (result && result.url) {
                        console.log('Setting image_url to:', result.url);
                        setNewSliderImage(prev => ({ ...prev, image_url: result.url }));
                      }
                    }}
                    onError={(error) => {
                      console.error('Upload failed:', error);
                      alert('فشل رفع الصورة: ' + error);
                    }}
                    placeholder="اختر صورة الشريحة أو اسحبها هنا"
                  />
                </div>
                <div className="form-group">
                  <label>رابط الانتقال</label>
                  <input
                    type="url"
                    value={newSliderImage.link_url}
                    onChange={(e) => setNewSliderImage({ ...newSliderImage, link_url: e.target.value })}
                    placeholder="https://example.com (اختياري)"
                  />
                </div>
                <div className="form-group">
                  <label>نص الزر</label>
                  <input
                    type="text"
                    value={newSliderImage.button_text}
                    onChange={(e) => setNewSliderImage({ ...newSliderImage, button_text: e.target.value })}
                    placeholder="اقرأ المزيد (اختياري)"
                  />
                </div>
                <div className="form-group">
                  <label>ترتيب العرض</label>
                  <input
                    type="number"
                    min="0"
                    value={newSliderImage.display_order}
                    onChange={(e) => setNewSliderImage({ ...newSliderImage, display_order: parseInt(e.target.value) })}
                  />
                </div>
                <div className="form-group">
                  <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                    <input
                      type="checkbox"
                      checked={newSliderImage.is_active}
                      onChange={(e) => setNewSliderImage({ ...newSliderImage, is_active: e.target.checked })}
                    />
                    نشط
                  </label>
                </div>
              </div>
              <button type="submit" className="btn btn-primary">إضافة صورة</button>
            </form>
          </div>

          <div className="admin-table">
            <div className="table-header">
              <div>العنوان</div>
              <div>العنوان الفرعي</div>
              <div>الصورة</div>
              <div>الترتيب</div>
              <div>الحالة</div>
              <div>الإجراءات</div>
            </div>
            {(sliderImages || []).map((slider) => (
              <div key={slider.id} className="table-row">
                <div>{slider.title}</div>
                <div>{slider.subtitle || '-'}</div>
                <div>
                  <img
                    src={slider.image_url}
                    alt={slider.title}
                    style={{ width: '60px', height: '40px', objectFit: 'cover', borderRadius: '4px' }}
                    onError={(e) => { e.target.src = '/images/placeholder.jpg'; }}
                  />
                </div>
                <div>{slider.display_order}</div>
                <div>
                  <span className={`status ${slider.is_active ? 'status-published' : 'status-draft'}`}>
                    {slider.is_active ? 'نشط' : 'غير نشط'}
                  </span>
                </div>
                <div>
                  <button className="btn btn-small btn-secondary" onClick={() => handleEditSliderImage(slider)}>تعديل</button>
                  <button className="btn btn-small btn-delete" onClick={() => handleDeleteSliderImage(slider.id)}>حذف</button>
                </div>
              </div>
            ))}
          </div>

          {editingSliderImage && (
            <div className="modal">
              <div className="modal-content">
                <h3>تعديل صورة السلايدر</h3>
                <form onSubmit={handleUpdateSliderImage}>
                  <div className="form-grid">
                    <div className="form-group">
                      <label>العنوان</label>
                      <input
                        type="text"
                        value={editingSliderImage.title}
                        onChange={(e) => setEditingSliderImage({ ...editingSliderImage, title: e.target.value })}
                        required
                      />
                    </div>
                    <div className="form-group">
                      <label>العنوان الفرعي</label>
                      <input
                        type="text"
                        value={editingSliderImage.subtitle || ''}
                        onChange={(e) => setEditingSliderImage({ ...editingSliderImage, subtitle: e.target.value })}
                      />
                    </div>
                    <div className="form-group">
                      <label>صورة الشريحة</label>
                      <ImageUpload
                        uploadType="slider_image"
                        entityType="slider"
                        entityId={editingSliderImage.id}
                        onImageSelect={(file) => {
                          if (file && file.url) {
                            setEditingSliderImage({ ...editingSliderImage, image_url: file.url });
                          }
                        }}
                        onImageUpload={(result) => {
                          if (result && result.url) {
                            setEditingSliderImage({ ...editingSliderImage, image_url: result.url });
                          }
                        }}
                        onError={(error) => {
                          console.error('Upload failed:', error);
                          alert('فشل رفع الصورة: ' + error);
                        }}
                        placeholder="اختر صورة الشريحة أو اسحبها هنا"
                      />
                    </div>
                    <div className="form-group">
                      <label>رابط الانتقال</label>
                      <input
                        type="url"
                        value={editingSliderImage.link_url || ''}
                        onChange={(e) => setEditingSliderImage({ ...editingSliderImage, link_url: e.target.value })}
                      />
                    </div>
                    <div className="form-group">
                      <label>نص الزر</label>
                      <input
                        type="text"
                        value={editingSliderImage.button_text || ''}
                        onChange={(e) => setEditingSliderImage({ ...editingSliderImage, button_text: e.target.value })}
                      />
                    </div>
                    <div className="form-group">
                      <label>ترتيب العرض</label>
                      <input
                        type="number"
                        min="0"
                        value={editingSliderImage.display_order || 0}
                        onChange={(e) => setEditingSliderImage({ ...editingSliderImage, display_order: parseInt(e.target.value) })}
                      />
                    </div>
                    <div className="form-group">
                      <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                        <input
                          type="checkbox"
                          checked={editingSliderImage.is_active !== false}
                          onChange={(e) => setEditingSliderImage({ ...editingSliderImage, is_active: e.target.checked })}
                        />
                        نشط
                      </label>
                    </div>
                  </div>
                  <div className="modal-actions">
                    <button type="submit" className="btn btn-primary">حفظ التغييرات</button>
                    <button type="button" className="btn btn-secondary" onClick={() => setEditingSliderImage(null)}>إلغاء</button>
                  </div>
                </form>
              </div>
            </div>
          )}
        </div>
        )}

        {activeTab === 'team-photos' && (
        <div className="card">
          <h2>إدارة صور الفريق</h2>
          <div className="modern-form" style={{ padding: 0, boxShadow: 'none' }}>
            <form onSubmit={handleAddTeamPhoto}>
              <div className="form-grid">
                <div className="form-group">
                  <label>العنوان</label>
                  <input
                    type="text"
                    value={newTeamPhoto.title}
                    onChange={(e) => setNewTeamPhoto({ ...newTeamPhoto, title: e.target.value })}
                    placeholder="عنوان الصورة"
                    required
                  />
                </div>
                <div className="form-group">
                  <label>الوصف</label>
                  <textarea
                    value={newTeamPhoto.description}
                    onChange={(e) => setNewTeamPhoto({ ...newTeamPhoto, description: e.target.value })}
                    placeholder="وصف الصورة (اختياري)"
                    rows="3"
                  />
                </div>
                <div className="form-group">
                  <label>رابط الصورة</label>
                  <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                    <input
                      type="text"
                      value={newTeamPhoto.image_url}
                      onChange={(e) => setNewTeamPhoto({ ...newTeamPhoto, image_url: e.target.value })}
                      placeholder="رابط الصورة"
                      style={{ flex: 1 }}
                    />
                    {newTeamPhoto.image_url && (
                      <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                        <img
                          src={newTeamPhoto.image_url}
                          alt="Preview"
                          style={{ width: '50px', height: '50px', objectFit: 'cover', borderRadius: '4px' }}
                        />
                        <strong>الصورة المحددة:</strong> {newTeamPhoto.image_url}
                      </div>
                    )}
                  </div>
                  <ImageUpload
                    ref={newTeamPhotoFileRef}
                    uploadType="team_photo"
                    entityType="team_photo"
                    entityTitle={newTeamPhoto.title}
                    onUpload={(file) => {
                      uploadImageAndSetUrl(file, (url) => {
                        setNewTeamPhoto(prev => ({ ...prev, image_url: url }));
                      }, 'team_photo', 'team_photo', null, newTeamPhoto.title);
                    }}
                    onUploadComplete={(result) => {
                      setNewTeamPhoto(prev => ({ ...prev, image_url: result.url }));
                    }}
                  />
                </div>
                <div className="form-group">
                  <label>ترتيب العرض</label>
                  <input
                    type="number"
                    value={newTeamPhoto.display_order}
                    onChange={(e) => setNewTeamPhoto({ ...newTeamPhoto, display_order: parseInt(e.target.value) })}
                    min="0"
                  />
                </div>
                <div className="form-group">
                  <label>
                    <input
                      type="checkbox"
                      checked={newTeamPhoto.is_active}
                      onChange={(e) => setNewTeamPhoto({ ...newTeamPhoto, is_active: e.target.checked })}
                    />
                    نشط
                  </label>
                </div>
              </div>
              <button type="submit" className="btn btn-primary">إضافة صورة الفريق</button>
            </form>
          </div>

          <div className="table-container" style={{ marginTop: '2rem' }}>
            <h3>صور الفريق الحالية</h3>
            <div className="table-header">
              <div>العنوان</div>
              <div>الوصف</div>
              <div>الصورة</div>
              <div>الترتيب</div>
              <div>الحالة</div>
              <div>الإجراءات</div>
            </div>
            {(teamPhotos || []).map((photo) => (
              <div key={photo.id} className="table-row">
                <div>{photo.title}</div>
                <div>{photo.description || '-'}</div>
                <div>
                  <img
                    src={photo.image_url}
                    alt={photo.title}
                    style={{ width: '50px', height: '50px', objectFit: 'cover', borderRadius: '4px' }}
                  />
                </div>
                <div>{photo.display_order}</div>
                <div>
                  <span className={`status ${photo.is_active ? 'status-published' : 'status-draft'}`}>
                    {photo.is_active ? 'نشط' : 'غير نشط'}
                  </span>
                </div>
                <div>
                  <button className="btn btn-small btn-secondary" onClick={() => handleEditTeamPhoto(photo)}>تعديل</button>
                  <button className="btn btn-small btn-delete" onClick={() => handleDeleteTeamPhoto(photo.id)}>حذف</button>
                </div>
              </div>
            ))}
          </div>

          {editingTeamPhoto && (
            <div className="modal">
              <div className="modal-content">
                <h3>تعديل صورة الفريق</h3>
                <form onSubmit={handleUpdateTeamPhoto}>
                  <div className="form-grid">
                    <div className="form-group">
                      <label>العنوان</label>
                      <input
                        type="text"
                        value={editingTeamPhoto.title}
                        onChange={(e) => setEditingTeamPhoto({ ...editingTeamPhoto, title: e.target.value })}
                        required
                      />
                    </div>
                    <div className="form-group">
                      <label>الوصف</label>
                      <textarea
                        value={editingTeamPhoto.description || ''}
                        onChange={(e) => setEditingTeamPhoto({ ...editingTeamPhoto, description: e.target.value })}
                        rows="3"
                      />
                    </div>
                    <div className="form-group">
                      <label>رابط الصورة</label>
                      <ImageUpload
                        ref={editTeamPhotoFileRef}
                        uploadType="team_photo"
                        entityType="team_photo"
                        entityId={editingTeamPhoto.id}
                        onUpload={(file) => {
                          uploadImageAndSetUrl(file, (url) => {
                            setEditingTeamPhoto({ ...editingTeamPhoto, image_url: url });
                          }, 'team_photo', 'team_photo', editingTeamPhoto.id);
                        }}
                        onUploadComplete={(result) => {
                          setEditingTeamPhoto({ ...editingTeamPhoto, image_url: result.url });
                        }}
                      />
                      <input
                        type="text"
                        value={editingTeamPhoto.image_url || ''}
                        onChange={(e) => setEditingTeamPhoto({ ...editingTeamPhoto, image_url: e.target.value })}
                        placeholder="رابط الصورة"
                      />
                    </div>
                    <div className="form-group">
                      <label>ترتيب العرض</label>
                      <input
                        type="number"
                        value={editingTeamPhoto.display_order || 0}
                        onChange={(e) => setEditingTeamPhoto({ ...editingTeamPhoto, display_order: parseInt(e.target.value) })}
                        min="0"
                      />
                    </div>
                    <div className="form-group">
                      <label>
                        <input
                          type="checkbox"
                          checked={editingTeamPhoto.is_active !== false}
                          onChange={(e) => setEditingTeamPhoto({ ...editingTeamPhoto, is_active: e.target.checked })}
                        />
                        نشط
                      </label>
                    </div>
                  </div>
                  <div style={{ display: 'flex', gap: '1rem', marginTop: '1rem' }}>
                    <button type="submit" className="btn btn-primary">حفظ التغييرات</button>
                    <button type="button" className="btn btn-secondary" onClick={() => setEditingTeamPhoto(null)}>إلغاء</button>
                  </div>
                </form>
              </div>
            </div>
          )}
        </div>
        )}

        {activeTab === 'book-of-week' && (
        <div className="card">
          <h2>إدارة كتاب الأسبوع</h2>

          {bookOfWeek ? (
            <div className="book-of-week-current" style={{ marginBottom: '2rem' }}>
              <h3 style={{ marginBottom: '1rem', color: '#1f2937' }}>كتاب الأسبوع الحالي</h3>
              <div className="current-book-display" style={{
                display: 'flex',
                gap: '1.5rem',
                padding: '1.5rem',
                border: '2px solid #3b82f6',
                borderRadius: '12px',
                backgroundColor: '#f8fafc',
                alignItems: 'flex-start',
                minHeight: '200px',
                flexWrap: 'wrap'
              }}>
                <div className="book-image" style={{ flexShrink: 0 }}>
                  <img
                    src={bookOfWeek.image_url || '/images/book-placeholder.jpg'}
                    alt={bookOfWeek.title}
                    style={{ 
                      width: '120px', 
                      height: '160px', 
                      objectFit: 'cover', 
                      borderRadius: '8px',
                      boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)'
                    }}
                    onError={(e) => { e.target.src = '/images/book-placeholder.jpg'; }}
                  />
                </div>
                <div className="book-details" style={{
                  flex: 1,
                  minWidth: 0,
                  overflow: 'hidden'
                }}>
                  <h4 style={{
                    margin: '0 0 1.25rem 0',
                    fontSize: '1.4rem',
                    fontWeight: '700',
                    color: '#1f2937',
                    lineHeight: '1.6',
                    wordWrap: 'break-word',
                    hyphens: 'auto',
                    whiteSpace: 'normal',
                    overflow: 'hidden',
                    textOverflow: 'ellipsis'
                  }}>
                    {bookOfWeek.title}
                  </h4>
                  <div style={{
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '0.75rem',
                    marginBottom: '1.5rem'
                  }}>
                    <p style={{
                      margin: '0 0 0.5rem 0',
                      fontSize: '1rem',
                      color: '#4b5563',
                      lineHeight: '1.6',
                      padding: '0.5rem 0',
                      wordWrap: 'break-word',
                      whiteSpace: 'normal'
                    }}>
                      <strong style={{ color: '#374151', fontWeight: '600', marginLeft: '0.5rem' }}>المؤلف:</strong>
                      <span style={{ marginRight: '0.5rem' }}>{bookOfWeek.author}</span>
                    </p>
                    <p style={{
                      margin: '0 0 0.5rem 0',
                      fontSize: '1rem',
                      color: '#4b5563',
                      lineHeight: '1.6',
                      padding: '0.5rem 0',
                      wordWrap: 'break-word',
                      whiteSpace: 'normal'
                    }}>
                      <strong style={{ color: '#374151', fontWeight: '600', marginLeft: '0.5rem' }}>السعر:</strong>
                      <span style={{ marginRight: '0.5rem' }}>{bookOfWeek.price} ريال</span>
                    </p>
                    <p style={{
                      margin: '0 0 0.5rem 0',
                      fontSize: '1rem',
                      color: '#4b5563',
                      lineHeight: '1.6',
                      padding: '0.5rem 0',
                      wordWrap: 'break-word',
                      whiteSpace: 'normal'
                    }}>
                      <strong style={{ color: '#374151', fontWeight: '600', marginLeft: '0.5rem' }}>التصنيف:</strong>
                      <span style={{ marginRight: '0.5rem' }}>{bookOfWeek.category_name || 'غير محدد'}</span>
                    </p>
                    <p style={{
                      margin: '0 0 0.5rem 0',
                      fontSize: '1rem',
                      color: '#4b5563',
                      lineHeight: '1.6',
                      padding: '0.5rem 0',
                      wordWrap: 'break-word',
                      whiteSpace: 'normal'
                    }}>
                      <strong style={{ color: '#374151', fontWeight: '600', marginLeft: '0.5rem' }}>تاريخ البداية:</strong>
                      <span style={{ marginRight: '0.5rem' }}>{bookOfWeek.start_date || 'غير محدد'}</span>
                    </p>
                    <p style={{
                      margin: '0 0 0.5rem 0',
                      fontSize: '1rem',
                      color: '#4b5563',
                      lineHeight: '1.6',
                      padding: '0.5rem 0',
                      wordWrap: 'break-word',
                      whiteSpace: 'normal'
                    }}>
                      <strong style={{ color: '#374151', fontWeight: '600', marginLeft: '0.5rem' }}>تاريخ النهاية:</strong>
                      <span style={{ marginRight: '0.5rem' }}>{bookOfWeek.end_date || 'مفتوح'}</span>
                    </p>
                  </div>
                  <button
                    className="btn btn-delete"
                    onClick={handleRemoveBookOfWeek}
                    style={{ 
                      padding: '0.75rem 1.5rem',
                      fontSize: '0.9rem',
                      fontWeight: '500'
                    }}
                  >
                    إزالة كتاب الأسبوع
                  </button>
                </div>
              </div>
            </div>
          ) : (
            <div className="no-book-of-week" style={{
              padding: '2rem',
              textAlign: 'center',
              backgroundColor: '#f9fafb',
              border: '2px dashed #d1d5db',
              borderRadius: '8px',
              marginBottom: '2rem'
            }}>
              <p style={{ margin: 0, color: '#6b7280', fontSize: '1.1rem' }}>
                لا يوجد كتاب أسبوع محدد حالياً
              </p>
            </div>
          )}

          <div className="set-book-of-week" style={{
            padding: '1.5rem',
            backgroundColor: '#ffffff',
            border: '1px solid #e5e7eb',
            borderRadius: '8px',
            marginBottom: '2rem'
          }}>
            <h3 style={{ 
              marginBottom: '1.5rem', 
              color: '#1f2937',
              fontSize: '1.25rem',
              fontWeight: '600'
            }}>
              {bookOfWeek ? 'تغيير كتاب الأسبوع' : 'تحديد كتاب الأسبوع'}
            </h3>
            <form onSubmit={handleSetBookOfWeek} className="modern-form" style={{ padding: 0, boxShadow: 'none' }}>
              <div className="form-group" style={{ marginBottom: '1.5rem' }}>
                <label htmlFor="book-select" style={{ 
                  display: 'block',
                  marginBottom: '0.75rem',
                  fontSize: '1rem',
                  fontWeight: '500',
                  color: '#374151'
                }}>
                  اختر كتاباً
                </label>
                <SearchableDropdown
                  options={availableBooks}
                  value={selectedBookId}
                  onChange={setSelectedBookId}
                  placeholder="-- اختر كتاباً --"
                  searchPlaceholder="ابحث عن كتاب..."
                  displayKey="title"
                  valueKey="id"
                  secondaryKey="author"
                  priceKey="price"
                  imageKey="image_url"
                  loading={booksLoading}
                  onSearch={handleBooksSearch}
                  searchDelay={500}
                  className="book-selection-dropdown"
                  style={{ 
                    width: '100%',
                    maxWidth: '500px'
                  }}
                />
              </div>
              <div style={{ display: 'flex', gap: '1rem', alignItems: 'center' }}>
                <button 
                  type="submit" 
                  className="btn btn-primary" 
                  disabled={!selectedBookId}
                  style={{
                    padding: '0.75rem 2rem',
                    fontSize: '1rem',
                    fontWeight: '500',
                    opacity: selectedBookId ? 1 : 0.6,
                    cursor: selectedBookId ? 'pointer' : 'not-allowed'
                  }}
                >
                  {bookOfWeek ? 'تغيير كتاب الأسبوع' : 'تحديد كتاب الأسبوع'}
                </button>
                {selectedBookId && (
                  <span style={{ 
                    color: '#059669', 
                    fontSize: '0.9rem',
                    fontWeight: '500'
                  }}>
                    ✓ تم اختيار كتاب
                  </span>
                )}
              </div>
            </form>
          </div>

          <div className="book-of-week-info" style={{ marginTop: '2rem', padding: '1rem', backgroundColor: '#f8f9fa', borderRadius: '8px' }}>
            <h4>معلومات</h4>
            <ul style={{ margin: 0, paddingLeft: '1.5rem' }}>
              <li>يمكن تحديد كتاب واحد فقط كـ "كتاب الأسبوع"</li>
              <li>عند تحديد كتاب جديد، سيتم إلغاء الكتاب السابق تلقائياً</li>
              <li>سيظهر كتاب الأسبوع في الصفحة الرئيسية بتصميم مميز</li>
              <li>يمكن إزالة كتاب الأسبوع في أي وقت</li>
            </ul>
          </div>
        </div>
        )}

        {activeTab === 'news' && (
        <div className="card">
          <h2>إدارة الإصدارات/الأخبار</h2>

          <form className="modern-form" style={{ padding: 0, boxShadow: 'none' }} onSubmit={async (e) => {
            e.preventDefault();
            try {
              const payload = { ...newNews };
              const res = await newsService.create(payload);
              setNews([{ id: res.data.id, ...payload }, ...news]);
              setNewNews({ title: '', content: '', type: 'news', date: '', image: '', featured: false, status: 'published' });
            } catch {
              alert('فشل إضافة الخبر/الإصدار');
            }
          }}>
            <div className="form-grid">
              <div className="form-group">
                <label>العنوان</label>
                <input type="text" value={newNews.title} onChange={(e)=>setNewNews({ ...newNews, title: e.target.value })} required />
              </div>
              <div className="form-group">
                <label>النوع</label>
                <select value={newNews.type} onChange={(e)=>setNewNews({ ...newNews, type: e.target.value })}>
                  <option value="news">خبر</option>
                  <option value="release">إصدار</option>
                </select>
              </div>
              <div className="form-group">
                <label>التاريخ</label>
                <input type="date" value={newNews.date} onChange={(e)=>setNewNews({ ...newNews, date: e.target.value })} />
              </div>
              <div className="form-group" style={{ gridColumn: '1 / -1' }}>
                <label>المحتوى</label>
                <textarea rows="3" value={newNews.content} onChange={(e)=>setNewNews({ ...newNews, content: e.target.value })}></textarea>
              </div>
              <div className="form-group">
                <label>صورة الخبر</label>
                <ImageUpload
                  uploadType="news_image"
                  entityType="news"
                  entityTitle={newNews.title}
                  onImageSelect={(file) => {
                    if (file && file.url) {
                      setNewNews({ ...newNews, image: file.url });
                    }
                  }}
                  onImageUpload={(result) => {
                    if (result && result.url) {
                      setNewNews({ ...newNews, image: result.url });
                    }
                  }}
                  onError={(error) => {
                    console.error('Upload failed:', error);
                    alert('فشل رفع الصورة: ' + error);
                  }}
                  placeholder="اختر صورة الخبر أو اسحبها هنا"
                />
              </div>
              <div className="form-group">
                <label>
                  <input type="checkbox" checked={newNews.featured} onChange={(e)=>setNewNews({ ...newNews, featured: e.target.checked })} />
                  {' '}مميز
                </label>
              </div>
              <div className="form-group">
                <label>الحالة</label>
                <select value={newNews.status} onChange={(e)=>setNewNews({ ...newNews, status: e.target.value })}>
                  <option value="published">منشور</option>
                  <option value="draft">مسودة</option>
                </select>
              </div>
            </div>
            <button type="submit" className="btn btn-primary">إضافة</button>
          </form>

          <div className="admin-table" style={{ marginTop: '1rem' }}>
            <div className="table-header">
              <div>العنوان</div>
              <div>النوع</div>
              <div>التاريخ</div>
              <div>الحالة</div>
              <div>إجراءات</div>
            </div>
            {news.map(item => (
              <div className="table-row" key={item.id}>
                <div>{item.title}</div>
                <div>{item.type === 'release' ? 'إصدار' : 'خبر'}</div>
                <div>{item.date ? new Date(item.date).toLocaleDateString('ar-SA') : '-'}</div>
                <div>{item.status === 'draft' ? 'مسودة' : 'منشور'}</div>
                <div className="table-actions">
                  <button className="btn btn-small btn-secondary" onClick={() => setEditingNews(item)}>تعديل</button>
                  <button className="btn btn-small btn-delete" onClick={async () => {
                    if (!window.confirm('تأكيد الحذف؟')) return;
                    try { await newsService.remove(item.id); setNews(news.filter(n => n.id !== item.id)); } catch { alert('فشل الحذف'); }
                  }}>حذف</button>
                </div>
              </div>
            ))}
          </div>

          {editingNews && (
            <div className="modal-overlay" onClick={() => setEditingNews(null)}>
              <div className="modal-content" onClick={(e) => e.stopPropagation()}>
                <h3>تعديل خبر/إصدار</h3>
                <form className="modern-form" style={{ padding: 0, boxShadow: 'none' }} onSubmit={async (e)=>{
                  e.preventDefault();
                  try {
                    await newsService.update(editingNews.id, editingNews);
                    setNews(news.map(n => n.id === editingNews.id ? editingNews : n));
                    setEditingNews(null);
                  } catch { alert('فشل التحديث'); }
                }}>
                  <div className="form-grid">
                    <div className="form-group">
                      <label>العنوان</label>
                      <input type="text" value={editingNews.title} onChange={(e)=>setEditingNews({ ...editingNews, title: e.target.value })} />
                    </div>
                    <div className="form-group">
                      <label>النوع</label>
                      <select value={editingNews.type} onChange={(e)=>setEditingNews({ ...editingNews, type: e.target.value })}>
                        <option value="news">خبر</option>
                        <option value="release">إصدار</option>
                      </select>
                    </div>
                    <div className="form-group">
                      <label>التاريخ</label>
                      <input type="date" value={editingNews.date || ''} onChange={(e)=>setEditingNews({ ...editingNews, date: e.target.value })} />
                    </div>
                    <div className="form-group" style={{ gridColumn: '1 / -1' }}>
                      <label>المحتوى</label>
                      <textarea rows="3" value={editingNews.content || ''} onChange={(e)=>setEditingNews({ ...editingNews, content: e.target.value })}></textarea>
                    </div>
                    <div className="form-group">
                      <label>صورة الخبر</label>
                      <ImageUpload
                        uploadType="blog_image"
                        entityType="news"
                        entityId={editingNews.id}
                        onImageSelect={(file) => {
                          if (file && file.url) {
                            setEditingNews({ ...editingNews, image: file.url });
                          }
                        }}
                        onImageUpload={(result) => {
                          if (result && result.url) {
                            setEditingNews({ ...editingNews, image: result.url });
                          }
                        }}
                        onError={(error) => {
                          console.error('Upload failed:', error);
                          alert('فشل رفع الصورة: ' + error);
                        }}
                        placeholder="اختر صورة الخبر أو اسحبها هنا"
                      />
                    </div>
                    <div className="form-group">
                      <label>
                        <input type="checkbox" checked={!!editingNews.featured} onChange={(e)=>setEditingNews({ ...editingNews, featured: e.target.checked })} /> {' '}مميز
                      </label>
                    </div>
                    <div className="form-group">
                      <label>الحالة</label>
                      <select value={editingNews.status || 'published'} onChange={(e)=>setEditingNews({ ...editingNews, status: e.target.value })}>
                        <option value="published">منشور</option>
                        <option value="draft">مسودة</option>
                      </select>
                    </div>
                  </div>
                  <div className="form-actions">
                    <button type="submit" className="btn btn-primary">حفظ</button>
                    <button type="button" className="btn btn-secondary" onClick={() => setEditingNews(null)}>إلغاء</button>
                  </div>
                </form>
              </div>
            </div>
          )}
        </div>
        )}

        {activeTab === 'images' && (
        <div className="card">
          <h2>معرض الصور</h2>
          <p style={{ marginBottom: '2rem', color: '#64748b' }}>
            إدارة جميع الصور المرفوعة على الموقع. يمكنك عرض وحذف الصور وتنظيف الصور غير المستخدمة.
          </p>

          <ImageGallery
            showDetails={true}
            showDelete={true}
            style={{
              border: '1px solid #e2e8f0',
              borderRadius: '8px',
              padding: '1rem'
            }}
          />
        </div>
        )}
      </div>
    </div>
  );
};

export default AdminSettings;
