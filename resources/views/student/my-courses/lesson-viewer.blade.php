@extends('layouts.student-timeline')

@section('title', $lesson->title)
@section('header', $lesson->title)
@section('enable-content-protection', 'true')

@section('content')
<div class="st-lesson-viewer min-h-screen bg-[#0b1220]">
    <div class="st-lesson-viewer__bar px-4 sm:px-6 py-3 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <button type="button" onclick="exitLesson()" class="st-lesson-viewer__icon-btn" aria-label="{{ __('common.close') }}">
                <i class="fas fa-times"></i>
            </button>
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-bold text-white truncate">{{ $lesson->title }}</h1>
                <p class="text-xs sm:text-sm text-white/60 truncate">{{ $course->title }}</p>
            </div>
        </div>

        <div class="hidden sm:flex items-center gap-4 text-sm">
            <div class="flex items-center gap-2">
                <span class="text-white/50">{{ __('student.progress') }}:</span>
                <span id="lesson-progress" class="font-bold text-[var(--st-gold,#C9952A)]">0%</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-white/50">الوقت:</span>
                <span id="time-display" class="font-medium text-white">00:00 / {{ gmdate('i:s', ($lesson->duration_minutes ?? 0) * 60) }}</span>
            </div>
        </div>
    </div>

    <!-- مشغل الفيديو المحمي -->
    <div class="relative w-full h-screen bg-black" id="video-container">
        <!-- طبقة حماية شفافة -->
        <div class="absolute inset-0 z-10 pointer-events-none select-none screenshot-protection"></div>
        
        <!-- طبقة حماية إضافية -->
        <div class="absolute inset-0 z-15 pointer-events-none select-none" id="protection-overlay">
            <div class="absolute top-0 left-0 w-full h-full opacity-0 bg-black screenshot-blocker"></div>
        </div>
        
        <!-- علامات مائية متحركة -->
        <div class="absolute inset-0 z-20 pointer-events-none select-none">
            <div class="watermark-1 absolute text-white opacity-5 text-4xl font-bold select-none animate-pulse">
                {{ auth()->user()->name }} - {{ date('Y-m-d H:i') }}
            </div>
            <div class="watermark-2 absolute text-white opacity-5 text-6xl font-bold select-none">
                {{ config('app.name') }}
            </div>
            <div class="watermark-3 absolute text-white opacity-5 text-3xl font-bold select-none animate-bounce">
                {{ config('app.name') }}
            </div>
        </div>
        
        
        <!-- الفيديو -->
        <div class="w-full h-full flex items-center justify-center relative" id="video-player">
            @if($lesson->video_url)
                <div class="w-full h-full relative">
                    <!-- الفيديو المدمج -->
                    <div id="video-iframe-container" class="w-full h-full">
                        {!! \App\Helpers\VideoHelper::generateEmbedHtml($lesson->video_url, '100%', '100%') !!}
                    </div>
                    
                    <!-- طبقة حماية شفافة فوق الفيديو -->
                    <div class="absolute inset-0 z-25 pointer-events-none">
                        <canvas id="protection-canvas" class="w-full h-full opacity-0"></canvas>
                    </div>
                </div>
            @else
                <div class="text-center text-white">
                    <i class="fas fa-exclamation-triangle text-4xl mb-4 text-[var(--st-gold,#C9952A)]"></i>
                    <p>لا يوجد فيديو متاح لهذا الدرس</p>
                </div>
            @endif
        </div>
        
        <!-- شريط التقدم -->
        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-6 z-30">
            <div class="flex items-center space-x-4 space-x-reverse mb-4">
                <!-- زر التشغيل/الإيقاف -->
                <button id="play-pause-btn" onclick="togglePlayPause()" 
                        class="w-12 h-12 bg-[#1E4E8C] hover:bg-[#152A4A] rounded-full flex items-center justify-center text-white transition-colors">
                    <i id="play-pause-icon" class="fas fa-play"></i>
                </button>
                
                <!-- شريط التقدم -->
                <div class="flex-1">
                    <div class="w-full bg-white/20 rounded-full h-2 cursor-pointer" onclick="seekTo(event)">
                        <div id="progress-bar" class="bg-[var(--st-gold,#C9952A)] h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>
                
                <!-- التحكم في الصوت -->
                <button onclick="toggleMute()" 
                        class="w-10 h-10 bg-white/15 hover:bg-white/25 rounded-full flex items-center justify-center text-white transition-colors">
                    <i id="volume-icon" class="fas fa-volume-up"></i>
                </button>
                
                <!-- ملء الشاشة -->
                <button onclick="toggleFullscreen()" 
                        class="w-10 h-10 bg-white/15 hover:bg-white/25 rounded-full flex items-center justify-center text-white transition-colors">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
        </div>
        
        <!-- رسائل الحماية العائمة -->
        <div class="absolute inset-0 pointer-events-none select-none z-20">
            <div class="absolute top-1/4 left-1/4 text-white opacity-10 text-6xl font-bold rotate-45 select-none">
                {{ auth()->user()->name }}
            </div>
            <div class="absolute top-3/4 right-1/4 text-white opacity-10 text-4xl font-bold -rotate-45 select-none">
                {{ config('app.name') }}
            </div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-white opacity-5 text-8xl font-bold select-none">
                {{ config('app.name') }}
            </div>
        </div>
    </div>

    <!-- تحذير الخروج -->
    <div id="exit-warning" class="hidden fixed inset-0 bg-black/75 z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 max-w-md mx-4 border border-slate-200 shadow-xl">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-4xl text-[var(--st-gold,#C9952A)] mb-4"></i>
                <h3 class="text-lg font-bold text-gray-900 mb-2">تأكيد الخروج</h3>
                <p class="text-gray-600 mb-6">هل تريد الخروج من الدرس؟ سيتم حفظ تقدمك الحالي.</p>
                <div class="flex gap-3">
                    <button onclick="confirmExit()" class="flex-1 st-pill st-pill--solid" style="background:#dc2626;color:#fff;justify-content:center">
                        نعم، اخرج
                    </button>
                    <button onclick="cancelExit()" class="flex-1 st-pill st-pill--outline" style="justify-content:center">
                        إلغاء
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.st-lesson-viewer__bar {
  background: linear-gradient(135deg, #1E4E8C 0%, #152A4A 100%);
  border-bottom: 1px solid rgba(255,255,255,.12);
}
.st-lesson-viewer__icon-btn {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,.2);
  background: rgba(255,255,255,.08);
  color: #fff;
  display: inline-grid;
  place-items: center;
  cursor: pointer;
}
.st-lesson-viewer__icon-btn:hover { background: rgba(255,255,255,.16); }
</style>
@endpush

@push('scripts')
<script>
const PLATFORM_WATERMARK = @json(config('app.name'));
let youtubePlayer = null;
let vimeoPlayer = null;
let videoElement = null;
let watchStartTime = Date.now();
let totalWatchTime = 0;
let lastProgressUpdate = 0;
let isVideoReady = false;
let progressInterval = null;
let protectionCanvas = null;

// حماية متقدمة من التصوير
document.addEventListener('keydown', function(e) {
    // منع Print Screen مع تفعيل الحماية
    if (e.key === 'PrintScreen' || e.keyCode === 44) {
        e.preventDefault();
        activateScreenshotProtection();
        showProtectionMessage('التصوير ممنوع - تم تفعيل الحماية');
        return false;
    }
    
    // منع جميع اختصارات أدوات المطور
    if ((e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C')) ||
        (e.ctrlKey && e.key === 'u') ||
        e.key === 'F12' ||
        (e.ctrlKey && e.key === 's') ||
        (e.ctrlKey && e.shiftKey && e.key === 'Delete')) {
        e.preventDefault();
        activateScreenshotProtection();
        showProtectionMessage('هذا الإجراء معطل لحماية المحتوى');
        return false;
    }
    
    // منع Alt+Tab (تغيير النوافذ)
    if (e.altKey && e.key === 'Tab') {
        e.preventDefault();
        showProtectionMessage('يجب التركيز على الدرس');
        return false;
    }
});

// تم إزالة منع النقر بالزر الأيمن

// حماية من التصوير عند الكشف
function activateScreenshotProtection() {
    const overlay = document.getElementById('protection-overlay');
    const canvas = document.getElementById('protection-canvas');
    
    // تفعيل الخلفية السوداء
    overlay.style.backgroundColor = 'black';
    overlay.style.opacity = '1';
    overlay.style.zIndex = '999';
    
    // رسم نمط حماية على الـ canvas
    if (canvas) {
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        
        ctx.fillStyle = 'black';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        ctx.fillStyle = 'red';
        ctx.font = '48px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(PLATFORM_WATERMARK, canvas.width/2, canvas.height/2);
        
        canvas.style.opacity = '1';
        canvas.style.zIndex = '1000';
    }
    
    // إزالة الحماية بعد ثانيتين
    setTimeout(() => {
        overlay.style.backgroundColor = '';
        overlay.style.opacity = '0';
        overlay.style.zIndex = '15';
        if (canvas) {
            canvas.style.opacity = '0';
            canvas.style.zIndex = '25';
        }
    }, 2000);
}

// منع السحب والإفلات
document.addEventListener('dragstart', function(e) {
    e.preventDefault();
});

// مراقبة تغيير النافذة (منع فتح نوافذ أخرى)
window.addEventListener('blur', function() {
    if (videoElement && !videoElement.paused) {
        pauseVideo();
        showProtectionMessage('تم إيقاف الفيديو - التركيز مطلوب');
    }
});

// تحميل YouTube API
function loadYouTubeAPI() {
    if (!window.YT) {
        const tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        const firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    }
}

// تهيئة مشغل الفيديو
document.addEventListener('DOMContentLoaded', function() {
    setupProtection();
    initializeVideoPlayer();
    startProgressTracking();
    
    // تحميل YouTube API للتحكم في الفيديو
    loadYouTubeAPI();
});

function setupProtection() {
    // منع التحديد
    document.body.style.userSelect = 'none';
    document.body.style.webkitUserSelect = 'none';
    document.body.style.mozUserSelect = 'none';
    document.body.style.msUserSelect = 'none';
    
    // إعداد canvas الحماية
    const canvas = document.getElementById('protection-canvas');
    if (canvas) {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    
    // مراقبة مستمرة للحماية
    setInterval(activateRandomProtection, 5000);
}

function activateRandomProtection() {
    const overlay = document.getElementById('protection-overlay');
    if (Math.random() < 0.1) { // 10% احتمال كل 5 ثوانِ
        overlay.style.backgroundColor = 'rgba(0,0,0,0.8)';
        overlay.style.opacity = '1';
        overlay.style.zIndex = '999';
        
        setTimeout(() => {
            overlay.style.backgroundColor = '';
            overlay.style.opacity = '0';
            overlay.style.zIndex = '15';
        }, 100);
    }
}

function initializeVideoPlayer() {
    const iframe = document.querySelector('iframe');
    const video = document.querySelector('video');
    
    if (iframe && iframe.src.includes('youtube')) {
        setupYouTubePlayer(iframe);
    } else if (iframe && iframe.src.includes('vimeo')) {
        setupVimeoPlayer(iframe);
    } else if (video) {
        setupDirectVideoPlayer(video);
    } else if (iframe) {
        setupGenericIframePlayer(iframe);
    }
}

// إعداد YouTube Player مع API
function setupYouTubePlayer(iframe) {
    const videoId = iframe.src.match(/embed\/([^?&]+)/)[1];
    
    // استبدال iframe بـ div للـ API
    const playerDiv = document.createElement('div');
    playerDiv.id = 'youtube-player';
    iframe.parentNode.replaceChild(playerDiv, iframe);
    
    window.onYouTubeIframeAPIReady = function() {
        youtubePlayer = new YT.Player('youtube-player', {
            height: '100%',
            width: '100%',
            videoId: videoId,
            playerVars: {
                'autoplay': 0,
                'controls': 0,
                'rel': 0,
                'showinfo': 0,
                'modestbranding': 1,
                'iv_load_policy': 3,
                'cc_load_policy': 0,
                'disablekb': 1,
                'fs': 0,
                'playsinline': 1,
                'origin': window.location.origin,
                'widget_referrer': window.location.origin
            },
            events: {
                'onReady': function(event) {
                    isVideoReady = true;
                    updatePlayButton(true);
                    
                    // إخفاء عناصر YouTube
                    hideYouTubeElements();
                },
                'onStateChange': function(event) {
                    if (event.data == YT.PlayerState.PLAYING) {
                        startWatchTimer();
                        updatePlayButton(false);
                        hideYouTubeElements();
                    } else if (event.data == YT.PlayerState.PAUSED) {
                        stopWatchTimer();
                        updatePlayButton(true);
                    } else if (event.data == YT.PlayerState.ENDED) {
                        markLessonComplete();
                    }
                }
            }
        });
    };
}

function hideYouTubeElements() {
    // محاولة إخفاء عناصر YouTube (محدود بسبب CORS)
    setTimeout(() => {
        const iframe = document.querySelector('#youtube-player iframe');
        if (iframe) {
            try {
                const style = document.createElement('style');
                style.textContent = `
                    .ytp-share-button,
                    .ytp-watch-later-button,
                    .ytp-title,
                    .ytp-chrome-top,
                    .ytp-show-cards-title,
                    .ytp-youtube-button {
                        display: none !important;
                        visibility: hidden !important;
                    }
                `;
                iframe.contentDocument?.head?.appendChild(style);
            } catch(e) {
                // تجاهل أخطاء CORS
            }
        }
    }, 1000);
}

function setupGenericIframePlayer(iframe) {
    iframe.style.pointerEvents = 'auto';
    iframe.addEventListener('load', function() {
        isVideoReady = true;
        startWatchTimer();
    });
}

function setupDirectVideoPlayer(video) {
    videoElement = video;
    
    video.addEventListener('loadeddata', function() {
        isVideoReady = true;
    });
    
    video.addEventListener('play', function() {
        startWatchTimer();
        updatePlayButton(false);
    });
    
    video.addEventListener('pause', function() {
        stopWatchTimer();
        updatePlayButton(true);
    });
    
    video.addEventListener('timeupdate', function() {
        updateProgress();
    });
    
    video.addEventListener('ended', function() {
        markLessonComplete();
    });
    
    // تم إزالة منع النقر بالزر الأيمن على الفيديو
    
    // إزالة controls الافتراضية
    video.controls = false;
}

function startWatchTimer() {
    watchStartTime = Date.now();
}

function stopWatchTimer() {
    if (watchStartTime) {
        totalWatchTime += Math.floor((Date.now() - watchStartTime) / 1000);
        watchStartTime = null;
    }
}

function startProgressTracking() {
    progressInterval = setInterval(function() {
        if (isVideoReady) {
            updateProgress();
            if (watchStartTime) {
                updateWatchProgress();
            }
        }
    }, 1000); // كل ثانية
}

function updateWatchProgress() {
    const currentWatchTime = totalWatchTime + (watchStartTime ? Math.floor((Date.now() - watchStartTime) / 1000) : 0);
    const totalDuration = {{ ($lesson->duration_minutes ?? 0) * 60 }};
    
    if (totalDuration > 0) {
        const progressPercent = Math.min(100, (currentWatchTime / totalDuration) * 100);
        document.getElementById('lesson-progress').textContent = Math.floor(progressPercent) + '%';
        
        // إرسال التقدم للخادم كل 30 ثانية
        if (currentWatchTime - lastProgressUpdate >= 30) {
            sendProgressUpdate(currentWatchTime, progressPercent);
            lastProgressUpdate = currentWatchTime;
        }
        
        // إكمال الدرس تلقائياً عند الوصول لـ 90%
        if (progressPercent >= 90 && !document.body.dataset.completed) {
            document.body.dataset.completed = 'true';
            markLessonComplete();
        }
    }
}

function sendProgressUpdate(watchTime, progressPercent) {
    fetch(`/my-courses/{{ $course->id }}/lessons/{{ $lesson->id }}/progress`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            watch_time: watchTime,
            progress_percent: progressPercent,
            completed: progressPercent >= 90
        })
    }).catch(error => console.error('Error updating progress:', error));
}

function markLessonComplete() {
    fetch(`/my-courses/{{ $course->id }}/lessons/{{ $lesson->id }}/progress`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            completed: true,
            watch_time: totalWatchTime + (watchStartTime ? Math.floor((Date.now() - watchStartTime) / 1000) : 0)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showCompletionMessage();
        }
    })
    .catch(error => console.error('Error:', error));
}

function showCompletionMessage() {
    const message = document.createElement('div');
    message.className = 'fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50';
    message.innerHTML = `
        <div class="flex items-center space-x-2 space-x-reverse">
            <i class="fas fa-check-circle"></i>
            <span>تم إكمال الدرس بنجاح!</span>
        </div>
    `;
    document.body.appendChild(message);
    
    setTimeout(() => {
        message.remove();
    }, 3000);
}

function showProtectionMessage(text) {
    const message = document.createElement('div');
    message.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg z-50';
    message.innerHTML = `
        <div class="flex items-center space-x-2 space-x-reverse">
            <i class="fas fa-shield-alt"></i>
            <span>${text}</span>
        </div>
    `;
    document.body.appendChild(message);
    
    setTimeout(() => {
        message.remove();
    }, 2000);
}

function togglePlayPause() {
    if (youtubePlayer) {
        const state = youtubePlayer.getPlayerState();
        if (state === YT.PlayerState.PLAYING) {
            youtubePlayer.pauseVideo();
        } else {
            youtubePlayer.playVideo();
        }
    } else if (videoElement) {
        if (videoElement.paused) {
            playVideo();
        } else {
            pauseVideo();
        }
    }
}

function playVideo() {
    if (youtubePlayer) {
        youtubePlayer.playVideo();
    } else if (videoElement) {
        videoElement.play();
        updatePlayButton(false);
        startWatchTimer();
    }
}

function pauseVideo() {
    if (youtubePlayer) {
        youtubePlayer.pauseVideo();
    } else if (videoElement) {
        videoElement.pause();
        updatePlayButton(true);
        stopWatchTimer();
    }
}

function updatePlayButton(isPaused) {
    const icon = document.getElementById('play-pause-icon');
    if (icon) {
        icon.className = isPaused ? 'fas fa-play' : 'fas fa-pause';
    }
}

function toggleMute() {
    if (videoElement) {
        videoElement.muted = !videoElement.muted;
        const icon = document.getElementById('volume-icon');
        icon.className = videoElement.muted ? 'fas fa-volume-mute' : 'fas fa-volume-up';
    }
}

function toggleFullscreen() {
    const container = document.getElementById('video-container');
    if (document.fullscreenElement) {
        document.exitFullscreen();
    } else {
        container.requestFullscreen();
    }
}

function seekTo(event) {
    if (videoElement) {
        const progressBar = event.currentTarget;
        const rect = progressBar.getBoundingClientRect();
        const pos = (event.clientX - rect.left) / rect.width;
        videoElement.currentTime = pos * videoElement.duration;
    }
}

function updateProgress() {
    let currentTime = 0;
    let duration = 0;
    
    if (youtubePlayer && youtubePlayer.getCurrentTime) {
        currentTime = youtubePlayer.getCurrentTime();
        duration = youtubePlayer.getDuration();
    } else if (videoElement) {
        currentTime = videoElement.currentTime;
        duration = videoElement.duration;
    }
    
    if (duration > 0) {
        const progress = (currentTime / duration) * 100;
        document.getElementById('progress-bar').style.width = progress + '%';
        
        const currentTimeFloor = Math.floor(currentTime);
        const durationFloor = Math.floor(duration);
        document.getElementById('time-display').textContent = 
            `${Math.floor(currentTimeFloor / 60)}:${(currentTimeFloor % 60).toString().padStart(2, '0')} / ${Math.floor(durationFloor / 60)}:${(durationFloor % 60).toString().padStart(2, '0')}`;
    }
}

function exitLesson() {
    document.getElementById('exit-warning').classList.remove('hidden');
}

function confirmExit() {
    stopWatchTimer();
    if (progressInterval) {
        clearInterval(progressInterval);
    }
    
    // حفظ التقدم النهائي
    const finalWatchTime = totalWatchTime + (watchStartTime ? Math.floor((Date.now() - watchStartTime) / 1000) : 0);
    sendProgressUpdate(finalWatchTime, 0);
    
    // العودة للكورس
    window.location.href = '{{ route("my-courses.show", $course) }}';
}

function cancelExit() {
    document.getElementById('exit-warning').classList.add('hidden');
}

// منع إغلاق النافذة بدون تأكيد
window.addEventListener('beforeunload', function(e) {
    if (isVideoReady) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
});

// تنظيف عند إغلاق الصفحة
window.addEventListener('unload', function() {
    stopWatchTimer();
    if (progressInterval) {
        clearInterval(progressInterval);
    }
});

// منع نسخ المحتوى
document.addEventListener('copy', function(e) {
    e.preventDefault();
    showProtectionMessage('النسخ معطل');
});

// منع لصق المحتوى
document.addEventListener('paste', function(e) {
    e.preventDefault();
});

// منع تحديد النص
document.addEventListener('selectstart', function(e) {
    e.preventDefault();
});

// كشف أدوات المطور
let devtools = {open: false, orientation: null};
setInterval(function() {
    if (window.outerHeight - window.innerHeight > 200 || window.outerWidth - window.innerWidth > 200) {
        if (!devtools.open) {
            devtools.open = true;
            showProtectionMessage('أدوات المطور معطلة');
            // يمكن إضافة إجراءات إضافية هنا
        }
    } else {
        devtools.open = false;
    }
}, 500);

// حماية شاملة من التسجيل والتصوير
navigator.mediaDevices.getDisplayMedia = function() {
    activateScreenshotProtection();
    showProtectionMessage('تسجيل الشاشة معطل');
    return Promise.reject(new Error('Screen recording is disabled'));
};

// مراقبة مستمرة لمحاولات التصوير
let screenshotAttempts = 0;
setInterval(function() {
    // كشف محاولات التصوير عبر مراقبة تغييرات الصفحة
    if (document.visibilityState === 'hidden') {
        screenshotAttempts++;
        activateScreenshotProtection();
        
        if (screenshotAttempts > 3) {
            showProtectionMessage('تم اكتشاف محاولات تصوير متكررة');
            // يمكن إضافة إجراءات إضافية هنا مثل إيقاف الفيديو
        }
    }
}, 100);

// مراقبة تغيير حجم النافذة (محاولة تصوير)
window.addEventListener('resize', function() {
    if (Math.abs(window.outerWidth - window.innerWidth) > 200 || 
        Math.abs(window.outerHeight - window.innerHeight) > 200) {
        activateScreenshotProtection();
    }
});

// مراقبة Focus/Blur للنافذة
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        activateScreenshotProtection();
        if (youtubePlayer) {
            youtubePlayer.pauseVideo();
        } else if (videoElement && !videoElement.paused) {
            videoElement.pause();
        }
    }
});

// حماية من Copy/Paste
document.addEventListener('copy', function(e) {
    e.preventDefault();
    activateScreenshotProtection();
    showProtectionMessage('النسخ معطل');
});

document.addEventListener('paste', function(e) {
    e.preventDefault();
});

// منع السحب
document.addEventListener('dragstart', function(e) {
    e.preventDefault();
    activateScreenshotProtection();
});

// مراقبة محاولات الوصول للـ canvas
Object.defineProperty(HTMLCanvasElement.prototype, 'toDataURL', {
    value: function() {
        activateScreenshotProtection();
        showProtectionMessage('استخراج البيانات معطل');
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    }
});

// حماية إضافية للفيديو بدون تشويه
setInterval(function() {
    const iframes = document.querySelectorAll('iframe');
    iframes.forEach(iframe => {
        // إزالة أي أزرار مشاركة قد تظهر
        try {
            if (iframe.contentDocument) {
                const shareButtons = iframe.contentDocument.querySelectorAll('[aria-label*="Share"], [title*="Share"], .ytp-share-button');
                shareButtons.forEach(btn => btn.style.display = 'none');
            }
        } catch(e) {
            // تجاهل الأخطاء بسبب CORS
        }
    });
}, 1000);
</script>

<style>
/* منع تحديد النص */
* {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    -webkit-touch-callout: none;
    -webkit-tap-highlight-color: transparent;
}

/* إخفاء شريط التمرير */
::-webkit-scrollbar {
    display: none;
}

/* منع السحب */
* {
    -webkit-user-drag: none;
    -khtml-user-drag: none;
    -moz-user-drag: none;
    -o-user-drag: none;
    user-drag: none;
}

/* حماية قوية من التصوير */
body {
    -webkit-touch-callout: none !important;
    -webkit-user-select: none !important;
    -khtml-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    user-select: none !important;
    overflow: hidden !important;
}

/* حماية Canvas من التصوير */
canvas {
    image-rendering: pixelated !important;
    image-rendering: -moz-crisp-edges !important;
    image-rendering: crisp-edges !important;
}

/* إخفاء أدوات التحكم في الفيديو المدمج */
iframe {
    pointer-events: auto !important;
    border: none !important;
}

/* إخفاء عناصر YouTube */
iframe[src*="youtube"] {
    position: relative !important;
}

iframe[src*="youtube"]::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: transparent;
    z-index: 1;
    pointer-events: none;
}

/* إخفاء شريط العنوان في Vimeo */
iframe[src*="vimeo"] .vp-overlay,
iframe[src*="vimeo"] .vp-title,
iframe[src*="vimeo"] .vp-byline {
    display: none !important;
    visibility: hidden !important;
}

/* منع الطباعة */
@media print {
    body { 
        display: none !important; 
        visibility: hidden !important;
    }
}

/* العلامات المائية المتحركة */
.watermark-1 {
    top: 20%;
    left: 15%;
    transform: rotate(15deg);
    animation: float1 6s ease-in-out infinite;
}

.watermark-2 {
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-10deg);
    animation: float2 8s ease-in-out infinite;
}

.watermark-3 {
    bottom: 25%;
    right: 20%;
    transform: rotate(25deg);
    animation: float3 7s ease-in-out infinite;
}

@keyframes float1 {
    0%, 100% { transform: rotate(15deg) translateY(0px); }
    50% { transform: rotate(15deg) translateY(-20px); }
}

@keyframes float2 {
    0%, 100% { transform: translate(-50%, -50%) rotate(-10deg) scale(1); }
    50% { transform: translate(-50%, -50%) rotate(-10deg) scale(1.1); }
}

@keyframes float3 {
    0%, 100% { transform: rotate(25deg) translateX(0px); }
    50% { transform: rotate(25deg) translateX(15px); }
}

/* حماية من تسجيل الشاشة */
#video-container {
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    will-change: transform;
    backface-visibility: hidden;
}

/* طبقة حماية ديناميكية */
.screenshot-blocker {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: black !important;
    z-index: 9999 !important;
    pointer-events: none !important;
    opacity: 0 !important;
    transition: opacity 0.1s ease !important;
}

.screenshot-blocker.active {
    opacity: 1 !important;
}
</style>
@endpush
@endsection
