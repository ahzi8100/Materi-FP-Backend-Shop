<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use RealRashid\SweetAlert\Facades\Alert;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->q;
        $users = User::latest()->when($q, function ($query) use ($q) {
            $query->where('name', 'like', '%' . $q . '%');
        })->paginate(10);

        confirmDelete('Delete User!', "Are you sure you want to delete?");
        return view('admin.user.index', compact('users'));
    }

    public function create()
    {
        return view('admin.user.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required',
            'email'      => 'required|email|unique:users',
            'password'   => 'required|confirmed'
        ]);

        //save to DB
        $user = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => bcrypt($request->password),
        ]);

        if (!$user) {
            Alert::error('Create Failed', 'Data Gagal Disimpan!');
            return redirect()->route('admin.user.index');
        }

        Alert::success('Create Successfully', 'Data Berhasil Disimpan!');
        return redirect()->route('admin.user.index');
    }

    public function edit(User $user)
    {
        return view('admin.user.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'       => 'required',
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'password'   => 'required|confirmed'
        ]);

        //cek password
        if ($request->password == "") {

            //update tanpa password
            // $user = User::findOrFail($user->id);
            $user->update([
                'name'       => $request->name,
                'email'      => $request->email
            ]);
        } else {
            //update dengan password
            $user = User::findOrFail($user->id);
            $user->update([
                'name'       => $request->name,
                'email'      => $request->email,
                'password'   => bcrypt($request->password),
            ]);
        }

        if (!$user) {
            Alert::error('Updated Failed', 'Data Gagal Disimpan!');
            return redirect()->route('admin.user.index');
        }

        Alert::success('Updated Successfully', 'Data Berhasil Disimpan!');
        return redirect()->route('admin.user.index');
    }

    public function destroy(User $user)
    {
        $user->delete();

        if (!$user) {
            Alert::error('Deleted Failed', 'Data Gagal Disimpan!');
            return redirect()->route('admin.user.index');
        }

        Alert::success('Deleted Successfully', 'Data Berhasil Disimpan!');
        return redirect()->route('admin.user.index');
    }
}
