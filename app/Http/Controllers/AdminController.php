<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ChatbotFlow;
use App\Models\ButtonTemplate;
use App\Models\ChatbotConfig;
use App\Models\DemoRequest;
use App\Models\ServiceEnquiry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
   // Show Login Form
    public function showLogin()
    {
        //dd("PP");
        // If already logged in, redirect to dashboard
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    // Handle Login
    public function handleLogin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Try to login with default guard
        if (Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard')->with('success', 'Login successful!');
        }

        return redirect()->back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Invalid email or password']);
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Logged out successfully!');
    }

    // Dashboard
    public function dashboard()
    {
        // Data for dashboard
        return view('admin.dashboard', [
            'activeFlows' => ChatbotFlow::where('is_active', true)->count(),
            'totalCompanies' => Company::count(),
            'totalFlows' => ChatbotFlow::count(),
            'totalButtons' => ButtonTemplate::count(),
            'totalConfigs' => ChatbotConfig::count(),
            'totalDemoRequests' => DemoRequest::count(),
            'totalEnquiries' => ServiceEnquiry::count(),
            'companies' => Company::with('chatbotFlows')->latest()->take(5)->get(),
            'recentDemos' => DemoRequest::latest()->take(5)->get(),
            'recentEnquiries' => ServiceEnquiry::latest()->take(5)->get(),
            'flows' => ChatbotFlow::with('company')->latest()->take(10)->get(),
        ]);
    }

    // Analytics
    public function analytics()
    {
        $companies = Company::withCount('chatbotFlows')->get();
        $flowStats = ChatbotFlow::all();
        $configStats = ChatbotConfig::all();

        return view('admin.analytics', [
            'companies' => $companies,
            'flowStats' => $flowStats,
            'configStats' => $configStats,
        ]);
    }

    // System Status
    public function systemStatus()
    {
        return view('admin.system-status', [
            'appVersion' => config('app.version'),
            'phpVersion' => phpversion(),
            'laravelVersion' => app()->version(),
        ]);
    }

    // Clear Cache
    public function clearCache()
    {
        \Illuminate\Support\Facades\Cache::flush();
        return back()->with('success', 'Cache cleared successfully!');
    }
}
