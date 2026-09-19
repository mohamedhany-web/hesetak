/**
 * نظام حماية المنصة من التصوير والتسجيل
 * يعمل على جميع صفحات المنصة
 */

(function() {
    'use strict';

    // تم تعطيل نظام الحماية بناءً على طلب الإدارة لإزالة الشاشة السوداء
    return;
    
    let isProtectionActive = false;
    let screenshotAttempts = 0;
    let protectionOverlay = null;
    
    // إنشاء طبقة الحماية
    function createProtectionOverlay() {
        if (protectionOverlay) return;
        
        protectionOverlay = document.createElement('div');
        protectionOverlay.id = 'global-protection-overlay';
        protectionOverlay.style.cssText = `
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #000000 100%) !important;
            z-index: 999999 !important;
            pointer-events: none !important;
            opacity: 0 !important;
            transition: opacity 0.3s ease !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-direction: column !important;
            text-align: center !important;
            font-family: 'Cairo', 'IBM Plex Sans Arabic', Arial, sans-serif !important;
        `;
        
        const currentDate = new Date().toLocaleString('ar-EG', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
        
        protectionOverlay.innerHTML = `
            <!-- المحتوى الرئيسي -->
            <div style="display: flex; flex-direction: column; align-items: center; gap: 30px;">
                <!-- أيقونة قبعة التخرج -->
                <div style="position: relative; width: 120px; height: 120px; display: flex; align-items: center; justify-content: center;">
                    <svg width="120" height="120" viewBox="0 0 120 120" style="filter: drop-shadow(0 8px 16px rgba(139, 92, 246, 0.5));">
                        <!-- القبعة -->
                        <path d="M30 50 L60 40 L90 50 L90 70 L30 70 Z" fill="#6b21a8" stroke="#8b5cf6" stroke-width="2"/>
                        <!-- المربع العلوي -->
                        <rect x="50" y="40" width="20" height="15" fill="#6b21a8"/>
                        <!-- الخيط الأصفر -->
                        <circle cx="60" cy="40" r="8" fill="#fbbf24" opacity="0.9"/>
                        <line x1="60" y1="32" x2="60" y2="20" stroke="#fbbf24" stroke-width="3" stroke-linecap="round"/>
                        <circle cx="60" cy="18" r="3" fill="#fbbf24"/>
                    </svg>
                </div>
                
                <!-- النصوص -->
                <div style="display: flex; flex-direction: column; align-items: center; gap: 15px;">
                    <div style="font-size: 56px; font-weight: 900; color: #dc2626; text-shadow: 0 4px 20px rgba(220, 38, 38, 0.6), 0 2px 10px rgba(220, 38, 38, 0.4), 0 0 5px rgba(220, 38, 38, 0.3); letter-spacing: 2px;">
                        حصتك
                    </div>
                    <div style="font-size: 28px; font-weight: 700; color: #dc2626; text-shadow: 0 2px 10px rgba(220, 38, 38, 0.5), 0 0 5px rgba(220, 38, 38, 0.3);">
                        تأهيل المعلّمين
                    </div>
                </div>
                
                <!-- معلومات المستخدم -->
                <div style="font-size: 16px; font-weight: 500; color: #d1d5db; margin-top: 20px; text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);">
                    ${getUsername()} - ${currentDate}
                </div>
            </div>
        `;
        
        document.body.appendChild(protectionOverlay);
    }
    
    // تفعيل حماية التصوير
    function activateScreenshotProtection(duration = 2000) {
        if (!protectionOverlay) createProtectionOverlay();
        
        protectionOverlay.style.opacity = '1';
        protectionOverlay.style.pointerEvents = 'auto';
        
        setTimeout(() => {
            if (protectionOverlay) {
                protectionOverlay.style.opacity = '0';
                protectionOverlay.style.pointerEvents = 'none';
            }
        }, duration);
    }
    
    // الحصول على اسم المستخدم
    function getUsername() {
        const userElement = document.querySelector('[data-user-name]');
        return userElement ? userElement.dataset.userName : 'مستخدم';
    }
    
    // حماية من Print Screen
    document.addEventListener('keydown', function(e) {
        // Print Screen
        if (e.key === 'PrintScreen' || e.keyCode === 44) {
            e.preventDefault();
            screenshotAttempts++;
            activateScreenshotProtection(3000);
            showMessage('حصتك', 'info');
            return false;
        }
        
        // أدوات المطور
        if ((e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C')) ||
            (e.ctrlKey && e.key === 'u') ||
            e.key === 'F12' ||
            (e.ctrlKey && e.key === 's') ||
            (e.ctrlKey && e.shiftKey && e.key === 'Delete')) {
            e.preventDefault();
            activateScreenshotProtection();
            showMessage('هذا الإجراء معطل لحماية المحتوى', 'warning');
            return false;
        }
        
        // Ctrl+A (تحديد الكل)
        if (e.ctrlKey && e.key === 'a') {
            e.preventDefault();
            showMessage('التحديد معطل', 'info');
            return false;
        }
    }, true);
    
    // تم إزالة منع النقر بالزر الأيمن
    
    // مراقبة تغيير visibility (محاولة تصوير)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            screenshotAttempts++;
            activateScreenshotProtection();
            
            if (screenshotAttempts > 5) {
                showMessage('تم اكتشاف محاولات تصوير متكررة', 'error');
            }
        }
    });
    
    // مراقبة تغيير حجم النافذة
    window.addEventListener('resize', function() {
        if (Math.abs(window.outerWidth - window.innerWidth) > 200 || 
            Math.abs(window.outerHeight - window.innerHeight) > 200) {
            activateScreenshotProtection();
        }
    });
    
    // منع تسجيل الشاشة
    if (navigator.mediaDevices && navigator.mediaDevices.getDisplayMedia) {
        const originalGetDisplayMedia = navigator.mediaDevices.getDisplayMedia;
        navigator.mediaDevices.getDisplayMedia = function() {
            activateScreenshotProtection(5000);
            showMessage('تسجيل الشاشة معطل', 'error');
            return Promise.reject(new Error('Screen recording is disabled'));
        };
    }
    
    // حماية Canvas APIs
    const originalToDataURL = HTMLCanvasElement.prototype.toDataURL;
    HTMLCanvasElement.prototype.toDataURL = function() {
        activateScreenshotProtection();
        showMessage('استخراج البيانات معطل', 'warning');
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    };
    
    // منع Copy/Cut/Paste
    ['copy', 'cut', 'paste'].forEach(event => {
        document.addEventListener(event, function(e) {
            e.preventDefault();
            activateScreenshotProtection();
            showMessage(`${event === 'copy' ? 'النسخ' : event === 'cut' ? 'القص' : 'اللصق'} معطل`, 'info');
        }, true);
    });
    
    // منع السحب والإفلات
    document.addEventListener('dragstart', function(e) {
        e.preventDefault();
        activateScreenshotProtection();
    }, true);
    
    // عرض رسائل الحماية
    function showMessage(text, type = 'info') {
        // إزالة الرسائل السابقة
        const existingMessages = document.querySelectorAll('.protection-message');
        existingMessages.forEach(msg => msg.remove());
        
        const message = document.createElement('div');
        message.className = 'protection-message';
        message.style.cssText = `
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            padding: 14px 24px !important;
            border-radius: 12px !important;
            color: white !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            z-index: 1000000 !important;
            pointer-events: none !important;
            font-family: 'Cairo', 'IBM Plex Sans Arabic', Arial, sans-serif !important;
            box-shadow: 0 6px 20px rgba(0,0,0,0.4), 0 2px 8px rgba(0,0,0,0.3) !important;
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            animation: slideInRight 0.3s ease-out !important;
            backdrop-filter: blur(10px) !important;
        `;
        
        const icons = {
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle',
            default: 'fa-shield-alt'
        };
        
        switch(type) {
            case 'error':
                message.style.background = 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)';
                message.innerHTML = `<i class="fas ${icons.error}"></i><span>${text}</span>`;
                break;
            case 'warning':
                message.style.background = 'linear-gradient(135deg, #f97316 0%, #ea580c 100%)';
                message.innerHTML = `<i class="fas ${icons.warning}"></i><span>${text}</span>`;
                break;
            case 'info':
                message.style.background = 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)';
                message.innerHTML = `<i class="fas ${icons.info}"></i><span>${text}</span>`;
                break;
            default:
                message.style.background = 'linear-gradient(135deg, #374151 0%, #1f2937 100%)';
                message.innerHTML = `<i class="fas ${icons.default}"></i><span>${text}</span>`;
        }
        
        document.body.appendChild(message);
        
        setTimeout(() => {
            if (message.parentNode) {
                message.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => {
                    if (message.parentNode) {
                        message.parentNode.removeChild(message);
                    }
                }, 300);
            }
        }, 3000);
    }
    
    // مراقبة أدوات المطور
    let devtools = {open: false};
    setInterval(function() {
        if (window.outerHeight - window.innerHeight > 200 || 
            window.outerWidth - window.innerWidth > 200) {
            if (!devtools.open) {
                devtools.open = true;
                activateScreenshotProtection(5000);
                showMessage('أدوات المطور معطلة', 'error');
            }
        } else {
            devtools.open = false;
        }
    }, 500);
    
    // حماية مستمرة عشوائية
    setInterval(function() {
        if (Math.random() < 0.05) { // 5% احتمال كل 3 ثوان
            activateScreenshotProtection(200);
        }
    }, 3000);
    
    // تهيئة الحماية عند تحميل الصفحة
    document.addEventListener('DOMContentLoaded', function() {
        createProtectionOverlay();
        
        // إضافة اسم المستخدم للـ body
        if (window.Laravel && window.Laravel.user) {
            document.body.setAttribute('data-user-name', window.Laravel.user.name);
        }
        
        // تطبيق CSS الحماية
        const style = document.createElement('style');
        style.textContent = `
            * {
                -webkit-user-select: none !important;
                -moz-user-select: none !important;
                -ms-user-select: none !important;
                user-select: none !important;
                -webkit-user-drag: none !important;
                -webkit-touch-callout: none !important;
            }
            
            @media print {
                body { 
                    display: none !important; 
                    visibility: hidden !important;
                }
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
            
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: scale(0.95);
                }
                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }
            
            #global-protection-overlay {
                animation: fadeIn 0.3s ease-out !important;
            }
        `;
        document.head.appendChild(style);
    });
    
    // منع إغلاق النافذة أثناء مشاهدة فيديو
    window.addEventListener('beforeunload', function(e) {
        if (window.location.pathname.includes('/watch')) {
            e.preventDefault();
            e.returnValue = 'هل تريد مغادرة الدرس؟ سيتم حفظ تقدمك.';
            return e.returnValue;
        }
    });
    
    // كشف محاولات استخدام أدوات خارجية
    Object.defineProperty(console, 'log', {
        value: function() {
            if (arguments.length > 0 && typeof arguments[0] === 'string' && 
                arguments[0].includes('screenshot') || arguments[0].includes('record')) {
                activateScreenshotProtection();
                showMessage('محاولة تلاعب مكتشفة', 'error');
            }
        }
    });
    
})();
