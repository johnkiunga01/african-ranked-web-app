<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('staff.profile', ['member' => $request->user()->fresh()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'job_title' => ['required', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'updated_at' => ['required', 'string'],
        ]);
        DB::transaction(function () use ($request, $data) {
            $user = User::lockForUpdate()->findOrFail($request->user()->id);
            if ($user->updated_at->toISOString() !== $data['updated_at']) {
                throw ValidationException::withMessages(['name' => 'Your account changed. Reload this page before saving.']);
            }
            $user->name = trim($data['name']);
            $user->job_title = trim($data['job_title']);
            $user->bio = $data['bio'] ?? null;
            $user->save();
            AuditEvent::record($user, 'staff.profile_updated', $user);
        });

        return redirect()->route('staff.profile.edit')->with('status', 'Profile saved. New draft revisions use these details; existing article bylines are preserved.');
    }
}
