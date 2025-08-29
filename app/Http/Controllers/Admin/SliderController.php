<?php

namespace App\Http\Controllers\Admin;

use App\Models\Slider;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class SliderController extends Controller
{
    public function index()
    {
        $sliders = Slider::latest()->paginate(5);

        confirmDelete('Delete Slider!', "Are you sure you want to delete?");
        return view('admin.slider.index', compact('sliders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png|max:2000',
            'link'  => 'required'
        ]);

        //upload image
        $image = $request->file('image');
        $image->storeAs('sliders', $image->hashName(), 'public');

        //save to DB
        $slider = Slider::create([
            'image'  => $image->hashName(),
            'link'   => $request->link
        ]);

        if (!$slider) {
            Alert::error('Create Failed', 'Data Gagal Disimpan!');
            return redirect()->route('admin.slider.index');
        }

        Alert::success('Create Successfully', 'Data Berhasil Disimpan!');
        return redirect()->route('admin.slider.index');
    }

    public function destroy(Slider $slider)
    {
        if (!$slider) {
            Alert::error('Deleted Failed', 'Data Gagal Dihapus!');
            return redirect()->route('admin.slider.index');
        }

        Storage::disk('public')->delete('sliders/' . basename($slider->image));
        $slider->delete();

        Alert::success('Deleted Successfully', 'Data Berhasil Dihapus!');
        return redirect()->route('admin.slider.index');
    }
}
