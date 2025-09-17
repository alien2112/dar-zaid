# تعليمات التشغيل - دار زيد للنشر والتوزيع

## الإعداد الأولي

### 1. إعداد قاعدة البيانات
```sql
-- قم بتشغيل الملف database.sql في MySQL
mysql -u root -p < database.sql
```

### 2. إعداد Backend (PHP)
1. انسخ مجلد `backend/` إلى مجلد الخادم الخاص بك
2. تأكد من أن PHP 7.4+ مثبت
3. حدث إعدادات قاعدة البيانات في `backend/config/database.php`:
   ```php
   private $host = 'localhost';
   private $db_name = 'dar_zaid_db';
   private $username = 'your_username';
   private $password = 'your_password';
   ```

### 3. إعداد Frontend (React)
```bash
cd frontend
npm install
npm start
```

## الاستخدام

### عناوين URL المهمة
- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost/dar-zaid-website/backend/api

### صفحات الموقع
1. **الرئيسية**: `/` - صفحة ترحيبية مع معلومات الدار
2. **تسجيل الدخول**: `/login` - نظام المصادقة
3. **الإصدارات**: `/releases` - آخر الإصدارات وشريط الأخبار
4. **متجر الكتب**: `/bookstore` - عرض الكتب المتاحة
5. **باقات النشر**: `/packages` - عرض باقات الطباعة والنشر
6. **تفاصيل الباقة**: `/package/:id` - تفاصيل كاملة للباقة
7. **المدونة**: `/blog` - المقالات والمحتوى الثقافي
8. **اتصل بنا**: `/contact` - نموذج التواصل

### بيانات التجربة
- **تسجيل الدخول**: admin@darzaid.com / admin123

### API Endpoints
- `GET /api/packages` - جلب باقات النشر
- `GET /api/books` - جلب الكتب
- `GET /api/blog` - جلب مقالات المدونة
- `POST /api/contact` - إرسال رسالة تواصل
- `POST /api/auth` - تسجيل الدخول

## استكشاف الأخطاء

### مشاكل شائعة:

1. **خطأ CORS**: تأكد من أن Backend يعمل على نفس النطاق أو قم بتحديث إعدادات CORS في `api/index.php`

2. **خطأ قاعدة البيانات**: تأكد من:
   - إنشاء قاعدة البيانات
   - صحة بيانات الاتصال
   - صلاحيات المستخدم

3. **خطأ في النصوص العربية**: تأكد من ضبط encoding على UTF-8 في قاعدة البيانات والـ PHP

### إعدادات الخادم الموصى بها:
```
PHP >= 7.4
MySQL >= 5.7
Apache/Nginx
Node.js >= 16
```

## التطوير

### إضافة ميزات جديدة:
1. **Backend**: أضف ملف PHP جديد في `backend/api/`
2. **Frontend**: أنشئ مكون جديد في `frontend/src/components/` أو `frontend/src/pages/`
3. **قاعدة البيانات**: أضف الجداول اللازمة في `database.sql`

### هيكل المشروع:
```
dar-zaid-website/
├── backend/
│   ├── api/
│   └── config/
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   └── services/
│   └── public/
├── database.sql
└── README.md
```

## الدعم
للحصول على المساعدة أو الإبلاغ عن مشاكل، يرجى التواصل عبر:
- 📧 info@darzaid.com
- 📞 +966 50 123 4567
