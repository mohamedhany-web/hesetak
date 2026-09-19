@extends('layouts.student-timeline')

@push('styles')
<style>
    .course-video-card {
        position: relative;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    }
    
    .play-button {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }
    
    .play-button:hover {
        transform: scale(1.08);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
    }
    
    .play-button::after {
        content: '';
        width: 0;
        height: 0;
        border-style: solid;
        border-width: 10px 0 10px 16px;
        border-color: transparent transparent transparent #2CA9BD;
        margin-right: -2px;
    }
    
    @media (min-width: 640px) {
        .play-button {
            width: 70px;
            height: 70px;
        }
        
        .play-button::after {
            border-width: 12px 0 12px 20px;
        }
    }
    
    .stat-card {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 20px;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, rgba(44, 169, 189, 0.5), transparent);
        transform: translateX(-100%);
        transition: transform 0.6s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }
    
    .stat-card:hover::before {
        transform: translateX(100%);
    }
    
    .sticky-purchase-card {
        position: sticky;
        top: 100px;
        z-index: 10;
        max-height: calc(100vh - 120px);
        overflow-y: auto;
    }
    
    .sticky-purchase-card::-webkit-scrollbar {
        width: 6px;
    }
    
    .sticky-purchase-card::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .sticky-purchase-card::-webkit-scrollbar-thumb {
        background: linear-gradient(to bottom, #2CA9BD, #65DBE4);
        border-radius: 10px;
    }
    
    .sticky-purchase-card::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(to bottom, #1F3A56, #2CA9BD);
    }
    
    .level-badge {
        border-radius: 20px;
        padding: 6px 16px;
        font-weight: 600;
        font-size: 14px;
    }
    
    /* Prevent card overlap */
    .course-video-card,
    .stat-card,
    .sticky-purchase-card {
        isolation: isolate;
    }
    
    /* Animation for form appearance */
    #orderForm {
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Hover effects for buttons */
    button, a[class*="bg-gradient"] {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-50 py-6 sm:py-8 lg:py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- تفاصيل الكورس -->
            <div class="lg:col-span-2 space-y-6">
                <!-- فيديو الكورس -->
                <div class="course-video-card bg-gradient-to-br from-[#2CA9BD] via-[#65DBE4] to-[#2CA9BD] h-[320px] sm:h-[380px] md:h-[450px] flex items-center justify-center relative group overflow-hidden">
                    @if($advancedCourse->image)
                        <img src="{{ storage_asset($advancedCourse->image) }}" alt="{{ $advancedCourse->title }}" 
                             class="w-full h-full object-cover absolute inset-0 transition-transform duration-700 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-br from-[#2CA9BD]/90 via-[#65DBE4]/85 to-[#2CA9BD]/90 transition-opacity duration-300 group-hover:opacity-75"></div>
                    @else
                        <div class="absolute inset-0 bg-gradient-to-br from-[#2CA9BD] via-[#65DBE4] to-[#2CA9BD]"></div>
                    @endif
                    
                    <!-- Overlay Pattern -->
                    <div class="absolute inset-0 opacity-10" style="background-image: url('https://www.transparenttextures.com/patterns/cubes.png');"></div>
                    
                    <!-- زر التشغيل -->
                    <div class="play-button relative z-10 transform transition-all duration-300 group-hover:scale-110">
                    </div>
                    
                    <!-- شارة المستوى -->
                    <div class="absolute top-4 right-4 z-10">
                        <span class="level-badge inline-flex items-center gap-1.5 text-xs px-4 py-2 rounded-full font-bold shadow-lg backdrop-blur-sm
                            @if($advancedCourse->level == 'beginner') bg-green-500/90 text-white
                            @elseif($advancedCourse->level == 'intermediate') bg-yellow-500/90 text-white
                            @else bg-red-500/90 text-white
                            @endif">
                            <i class="fas fa-signal"></i>
                            <span>{{ $advancedCourse->level_badge['text'] ?? 'مبتدئ' }}</span>
                        </span>
                    </div>
                    
                    <!-- Featured Badge -->
                    @if($advancedCourse->is_featured)
                    <div class="absolute top-4 left-4 z-10">
                        <span class="inline-flex items-center gap-1.5 text-xs px-4 py-2 rounded-full font-bold bg-gradient-to-r from-yellow-400 to-orange-500 text-white shadow-lg backdrop-blur-sm">
                            <i class="fas fa-star"></i>
                            <span>مميز</span>
                        </span>
                    </div>
                    @endif
                </div>

                <!-- محتوى الكورس -->
                <div class="bg-white rounded-3xl shadow-2xl border-2 border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-3xl">
                    <div class="p-6 sm:p-8 lg:p-10">
                        <!-- Breadcrumb -->
                        <nav class="text-sm text-gray-500 dark:text-gray-400 mb-6 flex items-center gap-2 flex-wrap">
                            <a href="{{ route('academic-years') }}" class="hover:text-[#2CA9BD] transition-colors font-medium flex items-center gap-1">
                                <i class="fas fa-home text-xs"></i>
                                السنوات الدراسية
                            </a>
                            @if($advancedCourse->academicYear)
                            <span class="text-[#2CA9BD]">/</span>
                            <a href="{{ route('academic-years.subjects', $advancedCourse->academicYear) }}" class="hover:text-[#2CA9BD] transition-colors font-medium">
                                {{ $advancedCourse->academicYear->name }}
                            </a>
                            @endif
                            @if($advancedCourse->academicSubject)
                            <span class="text-[#2CA9BD]">/</span>
                            <a href="{{ route('subjects.courses', $advancedCourse->academicSubject) }}" class="hover:text-[#2CA9BD] transition-colors font-medium">
                                {{ $advancedCourse->academicSubject->name }}
                            </a>
                            @endif
                            <span class="text-[#2CA9BD]">/</span>
                            <span class="text-[#1C2C39] font-bold">{{ $advancedCourse->title }}</span>
                        </nav>

                        <!-- عنوان الكورس -->
                        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-[#1C2C39] mb-4 leading-tight">{{ $advancedCourse->title }}</h1>
                        
                        <!-- وصف الكورس -->
                        <div class="prose max-w-none mb-8">
                            <p class="text-lg sm:text-xl text-[#1F3A56] leading-relaxed font-medium">{{ $advancedCourse->description }}</p>
                        </div>

                        <!-- معلومات الكورس -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                            <div class="stat-card bg-gradient-to-br from-[#2CA9BD]/10 to-[#65DBE4]/10 rounded-2xl p-5 border-2 border-[#2CA9BD]/20 hover:border-[#2CA9BD]/40 transition-all">
                                <div class="flex flex-col items-start">
                                    <div class="w-14 h-14 bg-gradient-to-br from-[#2CA9BD] to-[#65DBE4] rounded-2xl flex items-center justify-center shadow-lg mb-3">
                                        <i class="fas fa-users text-white text-xl"></i>
                                    </div>
                                    <div class="text-xs sm:text-sm font-semibold text-[#1F3A56] mb-1 uppercase tracking-wide">الطلاب</div>
                                    <div class="text-2xl sm:text-3xl font-black text-[#1C2C39] leading-none">{{ $advancedCourse->enrollments_count ?? 0 }}</div>
                                </div>
                            </div>
                            
                            <div class="stat-card bg-gradient-to-br from-purple-50 to-purple-100 rounded-2xl p-5 border-2 border-purple-200 hover:border-purple-400 transition-all">
                                <div class="flex flex-col items-start">
                                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg mb-3">
                                        <i class="fas fa-signal text-white text-xl"></i>
                                    </div>
                                    <div class="text-xs sm:text-sm font-semibold text-[#1F3A56] mb-1 uppercase tracking-wide">المستوى</div>
                                    <div class="text-lg sm:text-xl font-black text-[#1C2C39] leading-none">
                                        {{ $advancedCourse->level_badge['text'] ?? 'مبتدئ' }}
                                    </div>
                                </div>
                            </div>
                            
                            @if($advancedCourse->duration_hours)
                            <div class="stat-card bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-5 border-2 border-blue-200 hover:border-blue-400 transition-all">
                                <div class="flex flex-col items-start">
                                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg mb-3">
                                        <i class="fas fa-clock text-white text-xl"></i>
                                    </div>
                                    <div class="text-xs sm:text-sm font-semibold text-[#1F3A56] mb-1 uppercase tracking-wide">المدة</div>
                                    <div class="text-2xl sm:text-3xl font-black text-[#1C2C39] leading-none">{{ $advancedCourse->duration_hours }} س</div>
                                </div>
                            </div>
                            @endif
                            
                            @if($advancedCourse->lessons_count)
                            <div class="stat-card bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-2xl p-5 border-2 border-emerald-200 hover:border-emerald-400 transition-all">
                                <div class="flex flex-col items-start">
                                    <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center shadow-lg mb-3">
                                        <i class="fas fa-video text-white text-xl"></i>
                                    </div>
                                    <div class="text-xs sm:text-sm font-semibold text-[#1F3A56] mb-1 uppercase tracking-wide">الدروس</div>
                                    <div class="text-2xl sm:text-3xl font-black text-[#1C2C39] leading-none">{{ $advancedCourse->lessons_count }}</div>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- محتوى الكورس -->
                        @if($advancedCourse->syllabus)
                            <div class="mb-6 pt-6 border-t-2 border-gray-100">
                                <h3 class="text-2xl sm:text-3xl font-black text-[#1C2C39] mb-6 flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-[#2CA9BD] to-[#65DBE4] rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-list-ul text-white"></i>
                                    </div>
                                    محتوى الكورس
                                </h3>
                                <div class="prose max-w-none">
                                    <div class="text-[#1F3A56] bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-6 sm:p-8 border-2 border-gray-200 whitespace-pre-line leading-relaxed font-medium text-base sm:text-lg shadow-inner">{{ $advancedCourse->syllabus }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- بطاقة الشراء -->
            <div class="lg:col-span-1">
                <div class="sticky-purchase-card bg-white rounded-3xl shadow-2xl border-2 border-gray-100 p-6 sm:p-8 hover:shadow-3xl transition-all duration-300">
                    <!-- السعر -->
                    <div class="text-center mb-6 pb-6 border-b-2 border-gray-100" id="priceDisplay">
                        @if(!$advancedCourse->is_free && $advancedCourse->effectivePurchasePrice() > 0)
                            <div class="original-price">
                                @if($advancedCourse->hasPromotionalPrice())
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-1">قبل الخصم</p>
                                    <div class="text-2xl text-gray-400 line-through mb-3 tabular-nums">{{ number_format($advancedCourse->listPriceAmount()) }} <span class="text-base">$</span></div>
                                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wide mb-1">بعد الخصم</p>
                                @endif
                                <div class="text-5xl sm:text-6xl font-black text-[#1C2C39] mb-2 tabular-nums" id="coursePrice" data-price="{{ $advancedCourse->effectivePurchasePrice() }}">{{ number_format($advancedCourse->effectivePurchasePrice()) }}</div>
                                <div class="text-lg font-semibold text-[#1F3A56]">$</div>
                            </div>
                            <!-- عرض السعر بعد كوبون إضافي (مخفي افتراضياً) -->
                            <div class="discount-price hidden mt-4 pt-4 border-t-2 border-gray-200">
                                <div class="flex items-center justify-center gap-2 mb-3">
                                    <span class="text-sm text-gray-400 line-through font-medium" id="originalPriceDisplay">{{ number_format($advancedCourse->effectivePurchasePrice()) }} $</span>
                                    <span class="text-xs bg-gradient-to-r from-emerald-500 to-green-500 text-white px-3 py-1 rounded-full font-bold shadow-md" id="discountPercentage"></span>
                                </div>
                                <div class="text-5xl sm:text-6xl font-black text-emerald-600 mb-2 tabular-nums" id="finalPriceDisplay">{{ number_format($advancedCourse->effectivePurchasePrice()) }}</div>
                                <div class="text-lg font-semibold text-emerald-600">$</div>
                                <p class="text-sm text-emerald-600 mt-2 font-medium" id="discountAmountText"></p>
                            </div>
                        @else
                            <div class="text-5xl sm:text-6xl font-black bg-gradient-to-r from-green-500 to-emerald-600 bg-clip-text text-transparent mb-2">مجاني</div>
                            <div class="text-sm text-green-600 font-semibold">ابدأ التعلم الآن</div>
                        @endif
                    </div>

                    <!-- حالة التسجيل -->
                    @if($isEnrolled)
                        <div class="bg-gradient-to-r from-green-50 via-emerald-50 to-green-50 border-2 border-green-300 rounded-2xl p-5 mb-6 shadow-md">
                            <div class="flex items-center gap-3 text-green-800">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                                    <i class="fas fa-check-circle text-white text-xl"></i>
                                </div>
                                <span class="font-bold text-base">أنت مسجل في هذا الكورس</span>
                            </div>
                        </div>
                        <a href="{{ route('my-courses.show', $advancedCourse->id) }}" class="w-full bg-gradient-to-r from-green-600 via-emerald-600 to-green-600 hover:from-green-700 hover:via-emerald-700 hover:to-green-700 text-white py-4 px-6 rounded-2xl font-black text-lg transition-all duration-300 shadow-xl hover:shadow-2xl hover:scale-105 text-center block flex items-center justify-center gap-2 transform">
                            <i class="fas fa-play-circle"></i>
                            ادخل للكورس
                        </a>
                    @elseif($existingOrder)
                        @if($existingOrder->status == 'pending')
                            <div class="bg-gradient-to-r from-yellow-50 via-amber-50 to-yellow-50 border-2 border-yellow-300 rounded-2xl p-5 mb-6 shadow-md">
                                <div class="flex items-center gap-3 text-yellow-800 mb-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-amber-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-clock text-white text-xl"></i>
                                    </div>
                                    <span class="font-bold text-base">طلبك قيد المراجعة</span>
                                </div>
                                <p class="text-sm text-yellow-700 pr-14">سيتم مراجعة طلبك والرد عليك قريباً</p>
                            </div>
                            <a href="{{ route('orders.show', $existingOrder) }}" class="w-full bg-gradient-to-r from-yellow-600 via-amber-600 to-yellow-600 hover:from-yellow-700 hover:via-amber-700 hover:to-yellow-700 text-white py-4 px-6 rounded-2xl font-black text-lg transition-all duration-300 shadow-xl hover:shadow-2xl hover:scale-105 text-center block flex items-center justify-center gap-2 transform">
                                <i class="fas fa-eye"></i>
                                عرض حالة الطلب
                            </a>
                        @elseif($existingOrder->status == 'rejected')
                            <div class="bg-gradient-to-r from-red-50 via-rose-50 to-red-50 border-2 border-red-300 rounded-2xl p-5 mb-6 shadow-md">
                                <div class="flex items-center gap-3 text-red-800 mb-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-rose-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-times-circle text-white text-xl"></i>
                                    </div>
                                    <span class="font-bold text-base">تم رفض طلبك</span>
                                </div>
                                <p class="text-sm text-red-700 pr-14">يمكنك تقديم طلب جديد</p>
                            </div>
                            <button onclick="toggleOrderForm()" class="w-full bg-gradient-to-r from-[#2CA9BD] via-[#65DBE4] to-[#2CA9BD] hover:from-[#1F3A56] hover:via-[#2CA9BD] hover:to-[#1F3A56] text-white py-4 px-6 rounded-2xl font-black text-lg transition-all duration-300 shadow-xl hover:shadow-2xl hover:scale-105 flex items-center justify-center gap-2 transform">
                                <i class="fas fa-shopping-cart"></i>
                                اطلب الآن
                            </button>
                        @endif
                    @else
                        <button onclick="toggleOrderForm()" class="w-full bg-gradient-to-r from-[#2CA9BD] via-[#65DBE4] to-[#2CA9BD] hover:from-[#1F3A56] hover:via-[#2CA9BD] hover:to-[#1F3A56] text-white py-4 px-6 rounded-2xl font-black text-lg transition-all duration-300 shadow-xl hover:shadow-2xl hover:scale-105 flex items-center justify-center gap-2 transform">
                            <i class="fas fa-shopping-cart"></i>
                            @if(!$advancedCourse->is_free && $advancedCourse->effectivePurchasePrice() > 0)
                                اشتري الآن
                            @else
                                سجل مجاناً
                            @endif
                        </button>
                    @endif

                    <!-- نموذج الطلب -->
                    @if(!$isEnrolled && (!$existingOrder || $existingOrder->status == 'rejected'))
                        <div id="orderForm" class="hidden mt-6 pt-6 border-t-2 border-gray-100">
                            <form action="{{ route('courses.order', $advancedCourse) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                                @csrf
                                
                                <!-- حقل كوبون الخصم -->
                                @if(!$advancedCourse->is_free && $advancedCourse->effectivePurchasePrice() > 0)
                                <div class="bg-gradient-to-br from-[#2CA9BD]/10 to-[#65DBE4]/10 rounded-2xl p-5 border-2 border-[#2CA9BD]/20">
                                    <label class="block text-sm font-black text-[#1C2C39] mb-3 flex items-center gap-2">
                                        <div class="w-8 h-8 bg-gradient-to-br from-[#2CA9BD] to-[#65DBE4] rounded-lg flex items-center justify-center shadow-md">
                                            <i class="fas fa-ticket-alt text-white text-sm"></i>
                                        </div>
                                        كوبون الخصم (اختياري)
                                    </label>
                                    <div class="flex gap-2">
                                        <input type="text" 
                                               name="coupon_code" 
                                               id="coupon_code" 
                                               value="{{ old('coupon_code') }}"
                                               placeholder="أدخل كود الكوبون"
                                               autocomplete="off"
                                               class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-[#2CA9BD] focus:border-[#2CA9BD] transition-all uppercase font-medium bg-white">
                                        <button type="button" 
                                                id="applyCouponBtn"
                                                onclick="validateCoupon()"
                                                class="bg-gradient-to-r from-[#2CA9BD] to-[#65DBE4] hover:from-[#1F3A56] hover:to-[#2CA9BD] text-white px-6 py-3 rounded-xl font-black transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105 flex items-center gap-2 transform">
                                            <i class="fas fa-check"></i>
                                            تطبيق
                                        </button>
                                    </div>
                                    <div id="couponMessage" class="mt-3 text-sm hidden"></div>
                                    <input type="hidden" name="applied_coupon_id" id="applied_coupon_id" value="">
                                </div>
                                @endif
                                
                                <div>
                                    <label class="block text-sm font-black text-[#1C2C39] mb-3 flex items-center gap-2">
                                        <div class="w-8 h-8 bg-gradient-to-br from-[#2CA9BD] to-[#65DBE4] rounded-lg flex items-center justify-center shadow-md">
                                            <i class="fas fa-credit-card text-white text-sm"></i>
                                        </div>
                                        طريقة الدفع
                                    </label>
                                    <select name="payment_method" id="payment_method" required 
                                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-[#2CA9BD] focus:border-[#2CA9BD] transition-all bg-white font-medium">
                                        <option value="">اختر طريقة الدفع</option>
                                        <option value="bank_transfer">تحويل بنكي</option>
                                        <option value="cash">نقدي</option>
                                        <option value="other">أخرى</option>
                                    </select>
                                </div>

                                <!-- اختيار المحفظة الإلكترونية -->
                                @if(isset($availableWallets) && $availableWallets->count() > 0)
                                <div id="wallet_selection" class="hidden">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">
                                        <i class="fas fa-wallet text-sky-600 ml-2"></i>
                                        المحفظة الإلكترونية
                                    </label>
                                    <select name="wallet_id" id="wallet_id"
                                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-all">
                                        <option value="">اختر المحفظة الإلكترونية</option>
                                        @foreach($availableWallets as $wallet)
                                            <option value="{{ $wallet->id }}" 
                                                    data-type="{{ $wallet->type }}"
                                                    data-name="{{ $wallet->name }}"
                                                    data-account-number="{{ $wallet->account_number }}"
                                                    data-bank-name="{{ $wallet->bank_name }}"
                                                    data-account-holder="{{ $wallet->account_holder }}"
                                                    data-notes="{{ $wallet->notes }}">
                                                {{ $wallet->name ?? \App\Models\Wallet::typeLabel($wallet->type) }}
                                                @if($wallet->account_number)
                                                    - {{ $wallet->account_number }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>

                                    <!-- تفاصيل المحفظة المختارة -->
                                    <div id="wallet_details" class="hidden mt-4 p-4 bg-gradient-to-br from-sky-50 to-blue-50 rounded-xl border-2 border-sky-200">
                                        <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                                            <i class="fas fa-info-circle text-sky-600"></i>
                                            تفاصيل المحفظة للتحويل
                                        </h4>
                                        <div class="space-y-2 text-sm">
                                            <div id="wallet_type_detail" class="flex items-center justify-between">
                                                <span class="text-gray-600">النوع:</span>
                                                <span class="font-semibold text-gray-900" id="wallet_type_text"></span>
                                            </div>
                                            <div id="wallet_name_detail" class="hidden flex items-center justify-between">
                                                <span class="text-gray-600">الاسم:</span>
                                                <span class="font-semibold text-gray-900" id="wallet_name_text"></span>
                                            </div>
                                            <div id="wallet_account_detail" class="hidden flex items-center justify-between">
                                                <span class="text-gray-600">رقم الحساب:</span>
                                                <span class="font-semibold text-gray-900 font-mono" id="wallet_account_text"></span>
                                            </div>
                                            <div id="wallet_bank_detail" class="hidden flex items-center justify-between">
                                                <span class="text-gray-600">اسم البنك:</span>
                                                <span class="font-semibold text-gray-900" id="wallet_bank_text"></span>
                                            </div>
                                            <div id="wallet_holder_detail" class="hidden flex items-center justify-between">
                                                <span class="text-gray-600">صاحب الحساب:</span>
                                                <span class="font-semibold text-gray-900" id="wallet_holder_text"></span>
                                            </div>
                                            <div id="wallet_notes_detail" class="hidden mt-3 pt-3 border-t border-sky-200">
                                                <span class="text-gray-600 block mb-1">ملاحظات:</span>
                                                <span class="text-sm text-gray-700" id="wallet_notes_text"></span>
                                            </div>
                                        </div>
                                        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                            <p class="text-xs text-yellow-800 flex items-center gap-2">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <span>يرجى التحويل على البيانات المذكورة أعلاه وإرفاق صورة الإيصال</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div>
                                    <label class="block text-sm font-black text-[#1C2C39] mb-3 flex items-center gap-2">
                                        <div class="w-8 h-8 bg-gradient-to-br from-[#2CA9BD] to-[#65DBE4] rounded-lg flex items-center justify-center shadow-md">
                                            <i class="fas fa-image text-white text-sm"></i>
                                        </div>
                                        صورة الإيصال
                                    </label>
                                    <input type="file" name="payment_proof" accept="image/*" required 
                                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-[#2CA9BD] focus:border-[#2CA9BD] transition-all bg-white font-medium">
                                    <p class="text-xs text-[#1F3A56] mt-2 flex items-center gap-1">
                                        <i class="fas fa-info-circle text-[#2CA9BD]"></i>
                                        ارفع صورة الإيصال أو الفاتورة
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-black text-[#1C2C39] mb-3 flex items-center gap-2">
                                        <div class="w-8 h-8 bg-gradient-to-br from-[#2CA9BD] to-[#65DBE4] rounded-lg flex items-center justify-center shadow-md">
                                            <i class="fas fa-sticky-note text-white text-sm"></i>
                                        </div>
                                        ملاحظات (اختياري)
                                    </label>
                                    <textarea name="notes" rows="3" 
                                              class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-[#2CA9BD] focus:border-[#2CA9BD] transition-all resize-none bg-white font-medium"
                                              placeholder="أي ملاحظات إضافية..."></textarea>
                                </div>

                                <button type="submit" class="w-full bg-gradient-to-r from-green-600 via-emerald-600 to-green-600 hover:from-green-700 hover:via-emerald-700 hover:to-green-700 text-white py-4 px-6 rounded-2xl font-black text-lg transition-all duration-300 shadow-xl hover:shadow-2xl hover:scale-105 flex items-center justify-center gap-2 transform">
                                    <i class="fas fa-paper-plane"></i>
                                    إرسال الطلب
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleOrderForm() {
    const form = document.getElementById('orderForm');
    if (form) {
        form.classList.toggle('hidden');
        // Scroll to form smoothly
        if (!form.classList.contains('hidden')) {
            setTimeout(() => {
                form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
        }
    }
}

// إظهار/إخفاء اختيار المحفظة حسب طريقة الدفع
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethod = document.getElementById('payment_method');
    const walletSelection = document.getElementById('wallet_selection');
    const walletId = document.getElementById('wallet_id');
    const walletDetails = document.getElementById('wallet_details');

        if (paymentMethod && walletSelection) {
            function syncWalletForPaymentMethod() {
                if (paymentMethod.value === 'bank_transfer') {
                    walletSelection.classList.remove('hidden');
                    if (walletId) {
                        walletId.required = true;
                    }
                } else {
                    walletSelection.classList.add('hidden');
                    walletDetails.classList.add('hidden');
                    if (walletId) {
                        walletId.required = false;
                        walletId.value = '';
                    }
                }
            }
            paymentMethod.addEventListener('change', syncWalletForPaymentMethod);
            syncWalletForPaymentMethod();

        // عرض تفاصيل المحفظة المختارة
        if (walletId && walletDetails) {
            walletId.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                
                if (this.value && selectedOption) {
                    // عرض التفاصيل
                    const type = selectedOption.getAttribute('data-type');
                    const name = selectedOption.getAttribute('data-name');
                    const accountNumber = selectedOption.getAttribute('data-account-number');
                    const bankName = selectedOption.getAttribute('data-bank-name');
                    const accountHolder = selectedOption.getAttribute('data-account-holder');
                    const notes = selectedOption.getAttribute('data-notes');

                    // تحديث النوع
                    const typeLabels = {
                        'vodafone_cash': 'فودافون كاش',
                        'instapay': 'إنستا باي',
                        'bank_transfer': 'تحويل بنكي',
                        'cash': 'كاش',
                        'other': 'أخرى'
                    };
                    document.getElementById('wallet_type_text').textContent = typeLabels[type] || type;

                    // إظهار/إخفاء الحقول حسب البيانات المتاحة
                    if (name) {
                        document.getElementById('wallet_name_detail').classList.remove('hidden');
                        document.getElementById('wallet_name_text').textContent = name;
                    } else {
                        document.getElementById('wallet_name_detail').classList.add('hidden');
                    }

                    if (accountNumber) {
                        document.getElementById('wallet_account_detail').classList.remove('hidden');
                        document.getElementById('wallet_account_text').textContent = accountNumber;
                    } else {
                        document.getElementById('wallet_account_detail').classList.add('hidden');
                    }

                    if (bankName) {
                        document.getElementById('wallet_bank_detail').classList.remove('hidden');
                        document.getElementById('wallet_bank_text').textContent = bankName;
                    } else {
                        document.getElementById('wallet_bank_detail').classList.add('hidden');
                    }

                    if (accountHolder) {
                        document.getElementById('wallet_holder_detail').classList.remove('hidden');
                        document.getElementById('wallet_holder_text').textContent = accountHolder;
                    } else {
                        document.getElementById('wallet_holder_detail').classList.add('hidden');
                    }

                    if (notes) {
                        document.getElementById('wallet_notes_detail').classList.remove('hidden');
                        document.getElementById('wallet_notes_text').textContent = notes;
                    } else {
                        document.getElementById('wallet_notes_detail').classList.add('hidden');
                    }

                    walletDetails.classList.remove('hidden');
                } else {
                    walletDetails.classList.add('hidden');
                }
            });
        }
    }

    // التحقق من الكوبون
    window.validateCoupon = function() {
        const couponCode = document.getElementById('coupon_code');
        const couponMessage = document.getElementById('couponMessage');
        const applyBtn = document.getElementById('applyCouponBtn');
        const coursePrice = parseFloat(document.getElementById('coursePrice')?.dataset.price || 0);
        const courseId = {{ $advancedCourse->id }};

        if (!couponCode || !couponCode.value.trim()) {
            couponMessage.classList.remove('hidden', 'text-green-600', 'text-red-600', 'bg-green-50', 'bg-red-50', 'border-green-200', 'border-red-200');
                couponMessage.classList.add('text-red-600', 'bg-red-50', 'border', 'border-red-200', 'p-3', 'rounded-lg');
            couponMessage.innerHTML = '<i class="fas fa-exclamation-circle ml-2"></i> يرجى إدخال كود الكوبون';
            return;
        }

        // تعطيل الزر أثناء التحقق
        applyBtn.disabled = true;
        applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التحقق...';
        couponMessage.classList.add('hidden');

        fetch('{{ route("api.validate-coupon") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                coupon_code: couponCode.value.trim(),
                course_id: courseId
            })
        })
        .then(response => response.json())
        .then(data => {
            applyBtn.disabled = false;
            
            if (data.valid) {
                // عرض رسالة نجاح
                couponMessage.classList.remove('hidden', 'text-red-600', 'bg-red-50', 'border-red-200');
                couponMessage.classList.add('text-green-600', 'bg-green-50', 'border', 'border-green-200', 'p-3', 'rounded-lg');
                couponMessage.innerHTML = '<i class="fas fa-check-circle ml-2"></i> ' + data.message;

                // حفظ الكوبون
                document.getElementById('applied_coupon_id').value = data.coupon.id;

                // تحديث السعر
                updatePriceDisplay(data.pricing);

                // تغيير زر التطبيق
                applyBtn.classList.remove('from-sky-600', 'to-sky-500', 'hover:from-sky-700', 'hover:to-sky-600');
                applyBtn.classList.add('from-green-600', 'to-green-500', 'hover:from-green-700', 'hover:to-green-600');
                applyBtn.innerHTML = '<i class="fas fa-check"></i> مطبق';
            } else {
                // عرض رسالة خطأ
                couponMessage.classList.remove('hidden', 'text-green-600', 'bg-green-50', 'border-green-200');
                couponMessage.classList.add('text-red-600', 'bg-red-50', 'border', 'border-red-200', 'p-3', 'rounded-lg');
                couponMessage.innerHTML = '<i class="fas fa-exclamation-circle ml-2"></i> ' + data.message;

                // إعادة تعيين الكوبون
                document.getElementById('applied_coupon_id').value = '';
                resetPriceDisplay();
            }
        })
        .catch(error => {
            applyBtn.disabled = false;
            applyBtn.innerHTML = '<i class="fas fa-check"></i> تطبيق';
            
            couponMessage.classList.remove('hidden', 'text-green-600', 'bg-green-50', 'border-green-200');
                couponMessage.classList.add('text-red-600', 'bg-red-50', 'border', 'border-red-200', 'p-3', 'rounded-lg');
            couponMessage.innerHTML = '<i class="fas fa-exclamation-circle ml-2"></i> حدث خطأ أثناء التحقق من الكوبون';
            console.error('Error:', error);
        });
    }

    // تحديث عرض السعر
    function updatePriceDisplay(pricing) {
        const originalPriceDisplay = document.getElementById('originalPriceDisplay');
        const finalPriceDisplay = document.getElementById('finalPriceDisplay');
        const discountAmountText = document.getElementById('discountAmountText');
        const discountPercentage = document.getElementById('discountPercentage');
        const discountPriceSection = document.querySelector('.discount-price');
        const originalPriceSection = document.querySelector('.original-price');

        if (discountPriceSection && originalPriceDisplay && finalPriceDisplay && discountAmountText && discountPercentage) {
            originalPriceDisplay.textContent = number_format(pricing.original_price) + ' ' + @json(currency_symbol());
            finalPriceDisplay.textContent = number_format(pricing.final_amount);
            discountAmountText.textContent = 'وفرت: ' + number_format(pricing.discount_amount) + ' ' + @json(currency_symbol());
            discountPercentage.textContent = '-' + pricing.discount_percentage + '%';

            // إخفاء السعر الأصلي وإظهار السعر بعد الخصم
            originalPriceSection.classList.add('hidden');
            discountPriceSection.classList.remove('hidden');
        }
    }

    // إعادة تعيين عرض السعر
    function resetPriceDisplay() {
        const discountPriceSection = document.querySelector('.discount-price');
        const originalPriceSection = document.querySelector('.original-price');
        const applyBtn = document.getElementById('applyCouponBtn');

        if (discountPriceSection && originalPriceSection) {
            originalPriceSection.classList.remove('hidden');
            discountPriceSection.classList.add('hidden');
        }

        // إعادة تعيين زر التطبيق
        if (applyBtn) {
            applyBtn.classList.remove('from-green-600', 'to-green-500', 'hover:from-green-700', 'hover:to-green-600');
            applyBtn.classList.add('from-sky-600', 'to-sky-500', 'hover:from-sky-700', 'hover:to-sky-600');
            applyBtn.innerHTML = '<i class="fas fa-check"></i> تطبيق';
        }
    }

    // تنسيق الأرقام
    function number_format(number) {
        return new Intl.NumberFormat('ar-EG').format(number);
    }

    // إعادة تعيين الكوبون عند تغيير الكود
    const couponInput = document.getElementById('coupon_code');
    if (couponInput) {
        couponInput.addEventListener('input', function() {
            if (this.value.trim() === '') {
                resetPriceDisplay();
                document.getElementById('applied_coupon_id').value = '';
                document.getElementById('couponMessage').classList.add('hidden');
            }
        });

        // تطبيق الكوبون عند الضغط على Enter
        couponInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                validateCoupon();
            }
        });
    }
});
</script>
@endsection


