<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminProfileController extends Controller
{
    /**
     * Display Super Admin profile and list of all super admins.
     */
    public function index(Request $request): Response
    {
        $admins = User::where('user_type', 'superadmin')
            ->select('id', 'name', 'email', 'phone', 'created_at', 'is_active')
            ->orderBy('id', 'asc')
            ->get();

        return Inertia::render('SuperAdmin/Admins/Index', [
            'admins' => $admins,
            'currentAdmin' => $request->user()->only('id', 'name', 'email', 'phone'),
        ]);
    }

    /**
     * Update the authenticated Super Admin's personal profile (Name, Email, Password).
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'يرجى إدخال اسمك.',
            'email.required' => 'يرجى إدخال البريد الإلكتروني.',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 8 خانات أو أرقام.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $user->name = $validated['name'];
        $user->email = strtolower($validated['email']);
        $user->phone = $validated['phone'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->back()->with('success', 'تم تحديث بيانات حسابك بنجاح.');
    }

    /**
     * Create a new Super Admin user.
     */
    public function storeAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'name.required' => 'يرجى إدخال اسم المشرف.',
            'email.required' => 'يرجى إدخال البريد الإلكتروني.',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.required' => 'يرجى إدخال كلمة المرور.',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 8 خانات أو أرقام.',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'user_type' => 'superadmin',
            'is_active' => true,
            'tenant_id' => null,
        ]);

        return redirect()->back()->with('success', 'تم إضافة المشرف الجديد بنجاح.');
    }

    /**
     * Delete a Super Admin user.
     */
    public function destroyAdmin(Request $request, User $admin): RedirectResponse
    {
        if ($admin->id === $request->user()->id) {
            return redirect()->back()->with('error', 'لا يمكنك حذف حسابك الشخصي الحالي.');
        }

        if ($admin->user_type !== 'superadmin') {
            return redirect()->back()->with('error', 'هذا المستخدم ليس مشرفاً عاماً.');
        }

        $admin->delete();

        return redirect()->back()->with('success', 'تم حذف المشرف بنجاح.');
    }
}
