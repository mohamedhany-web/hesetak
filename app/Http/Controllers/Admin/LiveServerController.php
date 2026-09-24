<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LiveSetting;
use App\Models\LiveServer;
use App\Services\ServerSshService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class LiveServerController extends Controller
{
    public function index()
    {
        $servers = LiveServer::withCount(['sessions', 'activeSessions'])->latest()->get();
        $defaultLiveHost = LiveSetting::getLiveKitHost();

        return view('admin.live-servers.index', compact('servers', 'defaultLiveHost'));
    }

    /** لوحة التحكم بالسيرفرات (ريال تايم) — عرض كل السيرفرات مع روابط لوحة التحكم والاختبار. */
    public function control()
    {
        $servers = LiveServer::withCount(['sessions', 'activeSessions'])->latest()->get();
        $defaultLiveHost = LiveSetting::getLiveKitHost();

        return view('admin.live-servers.control', compact('servers', 'defaultLiveHost'));
    }

    public function create()
    {
        return view('admin.live-servers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'domain'           => 'required|string|max:255',
            'provider'         => 'required|in:livekit',
            'ip_address'       => 'nullable|string|max:45',
            'max_participants' => 'required|integer|min:2|max:10000',
            'notes'            => 'nullable|string',
            'control_panel_url'=> 'nullable|url|max:500',
            'ssh_host'         => 'nullable|string|max:255',
            'ssh_port'         => 'nullable|integer|min:1|max:65535',
            'ssh_username'     => 'nullable|string|max:255',
            'ssh_password'     => 'nullable|string|max:500',
        ]);

        $validated['status'] = 'active';
        $config = $this->buildServerConfig($request, null);
        $validated['config'] = $config;
        LiveServer::create($validated);

        return redirect()->route('admin.live-servers.index')
            ->with('success', 'تم إضافة سيرفر البث بنجاح');
    }

    public function edit(LiveServer $liveServer)
    {
        return view('admin.live-servers.edit', compact('liveServer'));
    }

    public function update(Request $request, LiveServer $liveServer)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'domain'           => 'required|string|max:255',
            'provider'         => 'required|in:livekit',
            'status'           => 'required|in:active,inactive,maintenance',
            'ip_address'       => 'nullable|string|max:45',
            'max_participants' => 'required|integer|min:2|max:10000',
            'notes'            => 'nullable|string',
            'control_panel_url'=> 'nullable|url|max:500',
            'ssh_host'         => 'nullable|string|max:255',
            'ssh_port'         => 'nullable|integer|min:1|max:65535',
            'ssh_username'     => 'nullable|string|max:255',
            'ssh_password'     => 'nullable|string|max:500',
        ]);

        $config = $this->buildServerConfig($request, $liveServer);
        $validated['config'] = $config;
        $liveServer->update($validated);

        return redirect()->route('admin.live-servers.index')
            ->with('success', 'تم تحديث سيرفر البث بنجاح');
    }

    public function destroy(LiveServer $liveServer)
    {
        if ($liveServer->activeSessions()->count() > 0) {
            return back()->with('error', 'لا يمكن حذف سيرفر عليه جلسات نشطة');
        }
        $liveServer->delete();
        return redirect()->route('admin.live-servers.index')
            ->with('success', 'تم حذف السيرفر بنجاح');
    }

    public function toggleStatus(LiveServer $liveServer)
    {
        $newStatus = $liveServer->status === 'active' ? 'inactive' : 'active';
        $liveServer->update(['status' => $newStatus]);
        return back()->with('success', 'تم تغيير حالة السيرفر');
    }

    /**
     * اختبار الاتصال الفعلي بالسيرفر (طلب HTTP لنطاق Hissatak Meeting).
     */
    public function testConnection(LiveServer $liveServer)
    {
        $domain = $liveServer->domain;
        $domain = preg_replace('#^https?://#i', '', $domain);
        $domain = rtrim($domain, '/');
        if ($domain === '') {
            return back()->with('error', 'نطاق السيرفر غير صالح');
        }

        $urls = [
            "https://{$domain}/",
            "http://{$domain}:7880/",
        ];

        foreach ($urls as $url) {
            try {
                $response = Http::timeout(10)
                    ->connectTimeout(5)
                    ->withOptions(['verify' => true])
                    ->get($url);

                if ($response->successful()) {
                    return back()->with('success', "الاتصال بنجاح: السيرفر «{$liveServer->name}» يستجيب على {$url}");
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                continue;
            } catch (\Exception $e) {
                return back()->with('error', 'فشل الاتصال: '.$e->getMessage());
            }
        }

        return back()->with('error', 'لا يمكن الوصول إلى السيرفر. تحقق من النطاق واتصال الشبكة و SSL، وأن خدمة Hissatak Meeting تعمل.');
    }

    /**
     * تعيين هذا السيرفر كنطاق Hissatak Meeting الافتراضي.
     */
    public function setAsDefault(LiveServer $liveServer)
    {
        if ($liveServer->status !== 'active') {
            return back()->with('error', 'يجب تفعيل السيرفر أولاً لاستخدامه كنطاق افتراضي.');
        }
        $domain = LiveSetting::normalizeLiveHost($liveServer->domain);
        LiveSetting::set('live_provider', 'livekit');
        LiveSetting::set('livekit_host', $domain);

        return back()->with('success', "تم تعيين «{$liveServer->name}» كنطاق Hissatak Meeting الافتراضي.");
    }

    /** بناء مصفوفة config مع بيانات SSH (كلمة المرور مشفّرة). */
    private function buildServerConfig(Request $request, ?LiveServer $server): array
    {
        $config = $server ? ($server->config ?? []) : [];
        if ($request->filled('control_panel_url')) {
            $config['control_panel_url'] = $request->control_panel_url;
        } elseif ($server) {
            $config['control_panel_url'] = $config['control_panel_url'] ?? '';
        }
        if ($request->filled('ssh_host')) {
            $config['ssh_host'] = $request->ssh_host;
        }
        if ($request->has('ssh_port')) {
            $config['ssh_port'] = (int) $request->ssh_port ?: 22;
        }
        if ($request->filled('ssh_username')) {
            $config['ssh_username'] = $request->ssh_username;
        }
        if ($request->filled('ssh_password')) {
            $config['ssh_password_encrypted'] = Crypt::encryptString($request->ssh_password);
        }
        return $config;
    }

    /** تصفح الملفات على السيرفر عبر SSH. */
    public function sshBrowse(Request $request, LiveServer $liveServer)
    {
        $path = $request->get('path', '/');
        $path = preg_replace('#/+#', '/', trim(str_replace('\\', '/', $path)));
        if ($path === '') {
            $path = '/';
        }

        $service = new ServerSshService();
        if (!$service->connect($liveServer)) {
            return redirect()->route('admin.live-servers.edit', $liveServer)
                ->with('error', 'فشل الاتصال عبر SSH. تحقق من العنوان والمنفذ واسم المستخدم وكلمة المرور.');
        }

        $result = $service->listDirectory($path);
        if ($result['error']) {
            return back()->with('error', $result['error']);
        }

        return view('admin.live-servers.ssh-browse', [
            'server' => $liveServer,
            'path'   => $path,
            'dirs'   => $result['dirs'],
            'files'  => $result['files'],
        ]);
    }

    /** عرض محتوى ملف نصي عبر SSH. */
    public function sshFile(Request $request, LiveServer $liveServer)
    {
        $path = $request->get('path', '');
        $path = preg_replace('#/+#', '/', trim(str_replace('\\', '/', $path)));
        if ($path === '') {
            return redirect()->route('admin.live-servers.ssh-browse', [$liveServer])->with('error', 'لم يُحدد مسار الملف.');
        }

        $service = new ServerSshService();
        if (!$service->connect($liveServer)) {
            return redirect()->route('admin.live-servers.edit', $liveServer)
                ->with('error', 'فشل الاتصال عبر SSH.');
        }

        $result = $service->readFile($path);
        if ($result['error']) {
            return redirect()->route('admin.live-servers.ssh-browse', [$liveServer, 'path' => dirname($path)])
                ->with('error', $result['error']);
        }

        return view('admin.live-servers.ssh-file', [
            'server'  => $liveServer,
            'path'    => $path,
            'content' => $result['content'],
        ]);
    }
}
