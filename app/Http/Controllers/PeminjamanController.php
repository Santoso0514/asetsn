<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Asettlsn;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use App\Models\ItemPeminjaman;

class PeminjamanController extends Controller
{

    public function index(Request $request)
    {
        $katakunci = $request->katakunci;
        if (strlen($katakunci)) {
            $assets = Asettlsn::where('namabarang', 'like',"%$katakunci%")->where('jumlah','>',0)->get();
        } else{
            $assets = Asettlsn::where('kondisi','Baik')->where('jumlah','>',0)->get();
        }
        return view('peminjaman.daftar', compact('assets'));
    }

    public function create(Request $request)
    {
        $request->validate([
            'pinjam_barang' => 'required|array',
            'pinjam_barang.*' => 'exists:asettlsn,id',
        ]);
        $selectedAssets = Asettlsn::whereIn('id', $request->pinjam_barang)->get();
        return view('peminjaman.create', compact('selectedAssets'));
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $currentDate = now()->startOfDay();

        $validatedData = $request->validate([
        'nama_peminjam' => 'required|string|max:255',
        'nomor_hp_peminjam' => 'required|string|max:15',
        'program' => 'required|string|max:255',
        'judul_kegiatan' => 'required|string|max:255',
        'lokasi_kegiatan' => 'required|string|max:255',
        'tgl_peminjaman' => [
            'required', 
            'date', 
            function ($attribute, $value, $fail) use ($currentDate) {
                $minDate = $currentDate->copy()->addDays(3);
                if (Carbon::parse($value)->lt($minDate)) {
                    $fail('Tanggal peminjaman harus H+3 dari tanggal permohonan.');
                }
            }
        ],
        'tgl_kembali' => [
            'required', 
            'date', 
            'after_or_equal:tgl_peminjaman'
        ],
        'lampiran' => 'required|file',
        'barang' => 'required|array',
        'barang.*.id' => 'required|integer|exists:asettlsn,id',
        'barang.*.jumlah_dipinjam' => 'required|integer|min:1',
        ]);

        // Buat objek Peminjaman dan isi dengan data yang sudah divalidasi
        $peminjaman = new Peminjaman();
        $peminjaman->nama_peminjam = $validatedData['nama_peminjam'];
        $peminjaman->nomor_hp_peminjam = $validatedData['nomor_hp_peminjam'];
        $peminjaman->program = $validatedData['program'];
        $peminjaman->judul_kegiatan = $validatedData['judul_kegiatan'];
        $peminjaman->lokasi_kegiatan = $validatedData['lokasi_kegiatan'];
        $peminjaman->tgl_peminjaman = $validatedData['tgl_peminjaman'];
        $peminjaman->tgl_kembali = $validatedData['tgl_kembali'];
        $peminjaman->id_user = auth()->id();
        $peminjaman->lampiran = $request->file('lampiran')->store('lampiran');
        $peminjaman->save();
        
        // Simpan setiap item peminjaman
        foreach ($validatedData['barang'] as $barang) {
            $itemPeminjaman = new ItemPeminjaman();
            $itemPeminjaman->id_aset = $barang['id'];
            $itemPeminjaman->id_peminjaman = $peminjaman->id_peminjaman;
            $itemPeminjaman->jumlah_dipinjam = $barang['jumlah_dipinjam'];
            $itemPeminjaman->save();
        }
        
        return redirect('/peminjaman')->with('success', 'Permohonan peminjaman berhasil dibuat, mohon tunggu permohonan disetujui');
    }
}