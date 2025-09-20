<?php
/**
 * routes/api.php
 *
 * ملف تعريف جميع مسارات (Routes) واجهة برمجة التطبيقات (API) الخاصة بتطبيق Smart Transport.
 * - يستخدم Laravel Sanctum للمصادقة على الطلبات المحمية (auth:sanctum).
 * - يستخدم ميدلوير 'role' للتحكم بالوصول حسب نوع المستخدم (customer, driver, station, admin).
 * - تم تنظيم المسارات بمجموعات prefix لكل وحدة (auth, customer, driver, station, payments, earnings, support, admin, notifications).
 *
 * ملاحظات عامة قبل الشرح المفصّل لكل قسم:
 * 1) كل مسار محمي بحاجة إلى توكن Bearer header: Authorization: Bearer {token} عندما يُستخدم middleware auth:sanctum.
 * 2) يجب أن تقوم كل Controller المذكورة أدناه بتطبيق التحقق (validation)، التحقق من الصلاحية (authorization) على مستوى السجل،
 *    ومعالجة الأخطاء بشكل موحّد (HTTP status codes: 200, 201, 400, 401, 403, 404, 422, 500).
 * 3) استخدم تراكيب JSON ثابتة للردود، على سبيل المثال:
 *    {
 *      "status": true|false,
 *      "message": "نص توضيحي",
 *      "data": { ... }        // قابل للإزالة أو التسمية (result, orders, user, tickets...)
 *    }
 * 4) احرص على التعامل مع Race Conditions عند قبول الطلب (مثلاً: عندما يحاول أكثر من سائق قبول نفس الطلب): استخدم معاملة DB أو تحقق شرط WHERE مع update مشروط.
 * 5) راجع الـ RoleMiddleware وتأكد أنه مُسجَّل في Kernel.php تحت 'routeMiddleware' باسم 'role'.
 */

use Illuminate\Support\Facades\Route;

// ======= Controllers =======
// نعرّف الـ Controllers المستخدمة ـ كل Use يوضح مسؤولية الكنترولر باختصار.
use App\Http\Controllers\Api\AuthController;        // إدارة التسجيل/الدخول/الملف الشخصي/الخروج
use App\Http\Controllers\Api\OrderController;       // إنشاء الطلبات (طرود/رحلات)، قوائم الطلبات، تحديث الحالة، استقبال القبول
use App\Http\Controllers\Api\DriverController;      // عمليات خاصة بالسائق: تحديث الحالة، تحديث الموقع، قائمة الطلبات القريبة
use App\Http\Controllers\Api\StationController;     // عمليات المحطات: تأكيد استلام الطرود وتعيين السائقين
use App\Http\Controllers\Api\PaymentController;     // إدارة عمليات الدفع (تسجيل دفعات، تاريخها، استرجاع)
use App\Http\Controllers\Api\EarningController;     // عرض أرباح السائقين/ملخصاتها
use App\Http\Controllers\Api\RatingController;      // إضافة وعرض التقييمات الخاصة بكل رحلة (مرتبط بالـ order)
use App\Http\Controllers\Api\SupportController;     // نظام التذاكر (Support tickets) - جديد
use App\Http\Controllers\Api\AdminController;       // أدوات إدارة النظام (قوائم المستخدمين/الطلبات/تقارير) - جديد

// =============================
// AUTHENTICATION ROUTES
// =============================
// المسارات المتعلقة بالمصادقة: التسجيل، تسجيل الدخول، والوصول للمعلومات المحمية الخاصة بالمستخدم.
// المسارات داخل المجموعة auth ليست محمية (except) إلا أن المسارات داخل auth التي بداخل middleware('auth:sanctum') تتطلب توكن صالح.
Route::prefix('auth')->group(function () {
    // POST /api/auth/register
    // وصف: تسجيل مستخدم جديد. يجب التحقق من الحقول: name, phone/email, password, password_confirmation, role.
    // رد متوقع عند نجاح التسجيل: 201 Created مع token (مثلاً Sanctum token) وبيانات المستخدم.
    Route::post('/register', [AuthController::class, 'register']);
    // POST /api/auth/login
    // وصف: المصادقة باستخدام البريد/الهاتف وكلمة المرور. يعيد توكن عند نجاح المصادقة.
    Route::post('/login', [AuthController::class, 'login']);

    // الجزء التالي محمي بـ Sanctum: المستخدم يجب أن يكون مصادقاً (Bearer token).
    Route::middleware('auth:sanctum')->group(function () {

        // GET /api/auth/profile
        // وصف: جلب بيانات الملف الشخصي للمستخدم المسجل (id, name, email, phone, role, region, wallet_balance, ...).
        Route::get('/profile', [AuthController::class, 'profile']);

        // POST /api/auth/update-profile
        // وصف: تحديث معلومات الحساب (name, phone, avatar, region, vehicle_type, license_number,...).
        // يجب تطبيق validation: unique على email/phone مستثناء_id الحالي.
        Route::post('/update-profile', [AuthController::class, 'updateProfile']);

        // POST /api/auth/change-password
        // وصف: تغيير كلمة المرور. التحقق من old_password و new_password + confirmation.
        Route::post('/change-password', [AuthController::class, 'changePassword']);

        // POST /api/auth/logout
        // وصف: إبطال التوكن الحالي (single device logout).
        Route::post('/logout', [AuthController::class, 'logout']);

        // POST /api/auth/logout-all
        // وصف: إبطال جميع التوكنات الخاصة بالمستخدم (logout from all devices).
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    });
});

// =============================
// PROTECTED ROUTES (require auth:sanctum)
// =============================
// المجموعة الرئيسية للمسارات المحمية: كل المسارات داخل هذا الـ group تتطلب توكن سانكتوم صالح.
// داخلها نستخدم ميدلوير role:xxx للتحكم في من يستطيع الوصول لأي مجموعة.
Route::middleware('auth:sanctum')->group(function () {

    // =============================
    // CUSTOMER ROUTES  (role:customer)
    // =============================
    // هذه المسارات متاحة فقط للمستخدمين الذين دورهم 'customer' — يتحقق RoleMiddleware من ذلك.
    Route::middleware('role:customer')->prefix('customer')->group(function () {

        // POST /api/customer/orders
        // وصف: إنشاء طلب فوري (parcel أو ride). الحقل المطلوب يختلف حسب type:
        // - type=parcel: يتطلب package_type (مثال: small/medium/large).
        // - type=ride: يتطلب passenger_count.
        // أيضاً يجب توفير pickup_location, dropoff_location, price.
        // تحقق: region يملأ تلقائياً من ملف المستخدم أو من body (توحيد ضروري).
        // رد: 201 مع كائن الطلب.
        Route::post('/orders', [OrderController::class, 'createAsCustomer']);

        // POST /api/customer/orders/scheduled   // جديد
        // وصف: إنشاء طلب مجدول في تاريخ/وقت محدد. يتطلب scheduled_at (YYYY-MM-DD HH:MM:SS).
        // ملاحظات: النظام يحتاج job scheduler لإشعار السائقين في الوقت المناسب.
        Route::post('/orders/scheduled', [OrderController::class, 'createScheduled']); // جديد: إنشاء طلب مجدول

        // GET /api/customer/orders
        // وصف: استرجاع جميع طلبات العميل (سواء الحالية أو السابقة)، مع خيارات فلترة (status، paginated).
        Route::get('/orders', [OrderController::class, 'listForCustomer']);

        // GET /api/customer/orders/{id}
        // وصف: استرجاع تفاصيل طلب واحد. يجب التأكد أن الطلب ينتمي لهذا العميل (authorization).
        Route::get('/orders/{id}', [OrderController::class, 'show']);

        // POST /api/customer/orders/{id}/cancel
        // وصف: إلغاء طلب — قابل للإلغاء فقط عندما يكون في حالة 'pending' أو حسب سياسة التطبيق.
        // body مقترح: { "reason": "نص" }
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);

        // POST /api/customer/orders/{id}/remove
        // وصف: إزالة (أو حذف/أرشفة) الطلب من قائمة العميل — قد تكون عملية soft-delete.
        // التأكد من السياسات: من يمكنه إزالة الطلب ومتى.
        Route::post('/orders/{id}/remove', [OrderController::class, 'remove']);

        // POST /api/customer/orders/{id}/rate  // جديد
        // وصف: إضافة تقييم للطلب بعد اكتماله. يجب ربط التقييم بالـ order والـ driver.
        // body: { "rating": 1..5, "comment": "نص اختياري" }
        Route::post('/orders/{id}/rate', [RatingController::class, 'store']); // جديد
    });

    // =============================
    // DRIVER ROUTES  (role:driver)
    // =============================
    // مسارات خاصة بالسائقين: رؤية الطلبات المتاحة في نفس المنطقة، قبول الطلبات، تحديث الحالة، إرسال الموقع.
    Route::middleware('role:driver')->prefix('driver')->group(function () {

        // GET /api/driver/orders
        // وصف: عرض الطلبات المتاحة للسائق — قاعدة التصفية عادةً: status = pending و region = سطر السائق.
        // تأكد من أن OrderController::availableOrders يتحقق من وجود region في ملف المستخدم.
        Route::get('/orders', [OrderController::class, 'availableOrders']);

        // POST /api/driver/orders/{id}/accept
        // وصف: قبول طلب. يجب حماية من السباق (race condition) — استخدم update مشروط مثلاً:
        // UPDATE orders SET driver_id = X, status = 'accepted' WHERE id = ? AND status = 'pending'
        // body ممكن: { } أو { "offer_note": "نص" } إن رغبت.
        Route::post('/orders/{id}/accept', [OrderController::class, 'acceptOrder']);

        // POST /api/driver/update-status
        // وصف: تحديث حالة السائق (متصل/غير متصل/مشغول). يختلف عن حالة order.
        // body مثال: { "status": "available" } أو { "status": "offline" }.
        Route::post('/update-status', [DriverController::class, 'updateStatus']);

        // GET /api/driver/parcels
        // وصف: عرض الطرود المتاحة أو المعينة للسائق — خاصة للـ 'parcel' type.
        Route::get('/parcels', [DriverController::class, 'availableParcels']);

        // GET /api/driver/requests
        // وصف: طلبات الركاب القريبة (ربما طلبات ride على مقربة جغرافياً).
        // قد يستخدم DriverController::nearbyRequests مع حساب مسافات (haversine) بناءً على lat/lng.
        Route::get('/requests', [DriverController::class, 'nearbyRequests']);

        // POST /api/driver/orders/{id}/status
        // وصف: تحديث حالة الطلب من قبل السائق (on_the_way, picked_up, in_progress, completed, cancelled).
        // body: { "status": "on_the_way" }
        // التحقق: السائق يجب أن يكون مُعيناً لهذا الطلب (driver_id).
        Route::post('/orders/{id}/status', [OrderController::class, 'updateStatus']);

        // POST /api/driver/location/update  // جديد
        // وصف: السائق يمّرر موقعه الحالي (lat, lng) لتحديث التتبع الواقعي.
        // body: { "lat": 36.75, "lng": 3.06 }
        // نصيحة: استعمل endpoint خفيف (لا ترجع بيانات كبيرة) واحفظ في جدول driver_locations أو في cache/redis للـ real-time.
        Route::post('/location/update', [DriverController::class, 'updateLocation']); // جديد: تحديث الموقع

        // GET /api/driver/ratings  // جديد
        // وصف: جلب تقييمات السائق (مجمعة أو مفصّلة).
        Route::get('/ratings', [RatingController::class, 'driverRatings']); // جديد: عرض التقييمات

        // GET /api/driver/earnings/history  // جديد
        // وصف: جلب تاريخ أرباح السائق مفصّل (orders, amounts, commission, payout status).
        Route::get('/earnings/history', [EarningController::class, 'history']); // جديد: أرباح مفصلة
    });

    // =============================
    // STATION ROUTES  (role:station)
    // =============================
    // عمليات المحطة: إدارة الطرود داخل المحطة، تأكيد الاستلام، وتعيين سائقي التوصيل.
    Route::middleware('role:station')->prefix('station')->group(function () {

        // GET /api/station/parcels
        // وصف: قائمة الطرود المخزنة/الواردة للمحطة.
        Route::get('/parcels', [StationController::class, 'index']);

        // POST /api/station/confirm
        // وصف: تأكيد استلام طرد في المحطة. body: { "parcel_id": 22, "status": "received" }
        Route::post('/confirm', [StationController::class, 'confirmReceive']);

        // POST /api/station/assign-driver
        // وصف: تعيين سائق لطرد معين. body: { "parcel_id": 22, "driver_id": 5 }
        Route::post('/assign-driver', [StationController::class, 'assignDriver']);
    });

    // =============================
    // PAYMENTS ROUTES
    // =============================
    // طرق الدفع والسجلات — يمكن أن تتضمن تكامل مع Stripe/PayPal أو تسجيل دفع نقدي (cash).
    Route::prefix('payments')->group(function () {

        // POST /api/payments/record
        // وصف: تسجيل دفعة (قد يتم من السائق عند استلام نقدي أو من العميل عند الدفع عبر بوابة).
        // body مثال: { "order_id": 10, "amount": 1500, "method": "cash" }
        Route::post('/record', [PaymentController::class, 'record']);

        // POST /api/payments/refund  // جديد
        // وصف: طلب أو تنفيذ استرجاع دفعة. يجب وجود سياسة و authorization (من الأدمن أو خدمة المدفوعات).
        // body: { "payment_id": 5, "reason": "Customer cancelled order" }
        Route::post('/refund', [PaymentController::class, 'refund']); // جديد: استرجاع دفعة

        // GET /api/payments/history  // جديد
        // وصف: سجل المدفوعات للمستخدم الحالي أو كأدمن (يمكن أن يقبل فلاتر: from,to,status).
        Route::get('/history', [PaymentController::class, 'history']); // جديد: سجل المدفوعات
    });

    // =============================
    // EARNINGS ROUTES
    // =============================
    // عرض أرباح السائقين أو ملخص الشركة.
    Route::prefix('earnings')->group(function () {

        // GET /api/earnings
        // وصف: قائمة الأرباح (قد تكون خاصة بالأدمن أو السائق حسب السياق).
        Route::get('/', [EarningController::class, 'index']);

        // GET /api/earnings/summary
        // وصف: ملخص أرباح اليوم/الأسبوع/الشهر للسائق المسجل أو للشركة.
        Route::get('/summary', [EarningController::class, 'summary']);
    });

    // =============================
    // SUPPORT ROUTES  (جديد)
    // =============================
    // نظام تذاكر الدعم الفني للمستخدمين.
    Route::prefix('support')->group(function () {

        // POST /api/support/create
        // وصف: فتح تذكرة جديدة. body: { "subject": "...", "message": "..." }
        Route::post('/create', [SupportController::class, 'create']);   // فتح تذكرة

        // GET /api/support/my-tickets
        // وصف: عرض تذاكر المستخدم الحالي (owner = user_id).
        Route::get('/my-tickets', [SupportController::class, 'myTickets']); // عرض تذاكري

        // GET /api/support/ticket/{id}
        // وصف: تفاصيل تذكرة معينة — يجب أن تكون مملوكة للمستخدم أو الأدمن.
        Route::get('/ticket/{id}', [SupportController::class, 'show']); // تفاصيل تذكرة
    });

    // =============================
    // ADMIN ROUTES  (جديد)
    // =============================
    // مجموعة وظائف خاصة بمستخدمي الأدمن (role:admin) — لا يمكن الوصول إليها إلا للأدمن.
    Route::middleware('role:admin')->prefix('admin')->group(function () {

        // GET /api/admin/users
        // وصف: جلب كل المستخدمين (مع فلاتر: role, status). مناسب لإدارة المستخدمين.
        Route::get('/users', [AdminController::class, 'listUsers']);

        // POST /api/admin/users/{id}/ban
        // وصف: حظر مستخدم (تغيير is_active/blocked). body يمكن أن يحتوي سبب الحظر.
        Route::post('/users/{id}/ban', [AdminController::class, 'banUser']);

        // GET /api/admin/orders
        // وصف: قائمة الطلبات لكل النظام مع إمكانيات الفلترة (status, date range, region).
        Route::get('/orders', [AdminController::class, 'listOrders']);

        // GET /api/admin/payments
        // وصف: قائمة المدفوعات لجميع المستخدمين (سجل المدفوعات).
        Route::get('/payments', [AdminController::class, 'listPayments']);

        // GET /api/admin/reports
        // وصف: تقارير النظام (مجموع المستخدمين، إجمالي الطلبات، إجمالي المدفوعات ...).
        Route::get('/reports', [AdminController::class, 'reports']);
    });

    // =============================
    // NOTIFICATIONS (جديد)
    // =============================
    // للتعامل مع الإشعارات (يمكن ربطها بـ FCM أو OneSignal):
    // ملاحظة: حالياً استخدم AuthController للإشعارات، ويفضل إنشاء NotificationController لاحقاً.
    Route::prefix('notifications')->group(function () {

        // GET /api/notifications
        // وصف: جلب إشعارات المستخدم (نوع: system, order updates, support replies ...).
        Route::get('/', [AuthController::class, 'notifications']); // عرض الإشعارات

        // POST /api/notifications/mark-read
        // وصف: تعليم إشعار كمقروء. body: { "notification_id": 12 } أو مجموعة ids.
        Route::post('/mark-read', [AuthController::class, 'markNotificationsRead']); // تعليم كمقروء
    });
});

/**
 * *** نصائح واعتبارات نهائية (مهمة) ***
 *
 * 1) Validation:
 *    - اجعل كافة البيانات الواردة عبر الطلبات تُعالج بواسطة Request classes (Form Requests) لتبسيط الاختبارات.
 *    - أعد استخدام rules مشتركة (مثل phone, email, price).
 *
 * 2) Authorization:
 *    - استخدم Policies أو Gate أو تحقق يدوي داخل الكونترولرز للتأكد من أن المستخدم يملك الحق بتعديل السجل.
 *    - مثال: order->driver_id يجب أن يطابق auth()->id() قبل السماح بتغيير الحالة من قبل السائق.
 *
 * 3) Atomicity / Race Conditions:
 *    - عند عمليات حسّاسة (قبول طلب / تحويل حالة / دفع)، استخدم DB transactions وعمليات update مشروطة:
 *      Order::where('id', $id)->where('status', 'pending')->update(['driver_id' => $id,'status' => 'accepted']);
 *
 * 4) Logging & Monitoring:
 *    - سجل العمليات الهامة (قبول الطلب، دفعات، استرجاع) لتسهيل التحقيق في الأخطاء والاحتيالات.
 *
 * 5) Pagination & Filtering:
 *    - لجميع الـ GETs التي قد ترجع قوائم (orders, users, payments, earnings) يجب دعم pagination, sort, filters.
 *
 * 6) Response Standard:
 *    - اتفق على صيغة ثابتة للـ responses (status, message, data, errors) لتسهيل التكامل مع الـ Frontend/Clients.
 *
 * 7) Security:
 *    - تحقق من مصادقة Sanctum وأنك تستخدم HTTPS في البيئات الحقيقية.
 *    - تحقق من صلاحيات المستخدم بدقّة (role middleware + policies).
 *    - حدد قواعد rate-limiting للمسارات الحسّاسة (مثلاً تسجيل الدخول، إرسال OTP).
 *
 * 8) Tests:
 *    - أضف اختبارات وحدة (unit tests) واختبارات تكامل (feature tests) لكل route مهمّة.
 *
 * 9) Documentation:
 *    - أنشئ مستند API (Swagger/OpenAPI أو حتى Postman collection موثق) واحتفظ بنسخة محدثة.
 *
 * 10) تحسينات مستقبلية:
 *    - استخدم WebSockets أو Pusher/Redis لتحديثات الحالة في الوقت الحقيقي (real-time status updates & tracking).
 *    - إضافة نظام محفظة داخل التطبيق (wallet) مع عمليات إيداع/سحب وإشعارات بالمسموحيات.
 *
 * بهذا الشكل يصبح ملف routes/api.php واضحًا ومُوثقًا داخليًا — مناسب لفريق تطوير ومراجعة سريعة.
 */
