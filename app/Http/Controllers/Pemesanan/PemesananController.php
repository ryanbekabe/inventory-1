<?php

namespace App\Http\Controllers\Pemesanan;

use Auth;
use App\Pemesanan;
use App\DetailPemesanan;
use App\Barang;
use App\BarangKeluar;
use App\DetailBarangKeluar;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Yajra\Datatables\Datatables;
use Exception;

class PemesananController extends Controller
{
    public function __construct() {
        $this->middleware('auth');
    }

    public function index()
    {
        // $pemesanan = Pemesanan::all()->toArray();
        return view("pages.{$this->departemen()}.pemesanan");
    }

    /**
     * Show pemesanan form
     */
    public function create()
    {
        $barang = Barang::all()->toArray();
        return view("pages.{$this->departemen()}.addPemesanan", compact('barang'));
    }

    public function store(Request $request)
    {
        try {
            // collect all request
            $data = $request->all();
            $pemesananNextID = (new Pemesanan)->nextID();

            /**
             * Collect kode_barang and qty
             * @param kode_barang save to detail pemesanan
             * @param qty save to detail pemesanan
             */
            $dataPesanan = [
                'kode_pemesanan' => $pemesananNextID,
                'user_id' => Auth::user()->user_id
            ];
            $pemesanan = Pemesanan::create($dataPesanan);

            /**
             * Save to detail pemesanan
             * @param kode_detail_pemesanan
             * @param kode_pemesanan
             * @param kode_barang
             * @param jumlah_pemesanan
             * @param jumlah_disetujui
             * @param status status penerimaan setiap barang
             */
            for ($i=0; $i < count($data['kode_barang']); $i++) { 
                $detailPemesananNextID = (new DetailPemesanan)->nextID();
                $dataDetailPesanan = [
                    'kode_detail_pemesanan' => $detailPemesananNextID,
                    'kode_pemesanan' => $pemesananNextID,
                    'kode_barang' => $data['kode_barang'][$i],
                    'jumlah_pemesanan' => $data['qty'][$i],
                    'jumlah_disetujui' => 0,
                    'status' => 'no'
                ];
                // update or create missing barang
                $barang = Barang::updateOrCreate([
                    'kode_barang' => $data['kode_barang'][$i],
                    'nama_barang' => $data['nama_barang'][$i],
                    'stok' => $data['stok_barang'][$i],
                    'minimum_stok' => 0,
                    'satuan' => $data['satuan_barang'][$i]
                ]);
                // save to detail pemesanan
                $detailPemesanan = DetailPemesanan::create($dataDetailPesanan);
            }

            return response()->json([
                'success' => true,
                'redirect' => route('pemesanan.index')
            ]);
            
        } catch (Exception $e) {
            return response()->json(['result' => $e->getMessage()]);
        }
    }

    /**
     * Show detail dari kode pemesanan
     */
    public function show($id)
    {
        
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        try {
            // collect all request
            $data = $request->all();
            // check data
            if (empty($data['jumlah_disetujui'])) {
                throw new Exception("Error Processing Request");
            }
            // save information
            $kodeBarangKeluar = BarangKeluar::nextID();
            BarangKeluar::create([
                'kode_barang_keluar' => $kodeBarangKeluar,
                'user_id' => Auth::user()->user_id
            ]);
            foreach ($data['jumlah_disetujui'] as $kode => $qty) {
                if ($qty > 0) {
                    // get and update
                    $detailPemesanan = DetailPemesanan::findOrFail($kode);
                    // update
                    if ($qty > $detailPemesanan->jumlah_pemesanan) {
                        throw new Exception("Jumlah disetujui melebihi jumlah pemesanan pada kode detail pemesanan {$kode}");
                    }

                    $detailPemesanan->jumlah_disetujui = $qty;
                    $detailPemesanan->save();

                    // and now save pemesanan to barang keluar
                    DetailBarangKeluar::create([
                        'kode_barang_keluar' => $kodeBarangKeluar,
                        'kode_pemesanan' => $detailPemesanan->kode_pemesanan,
                        'kode_barang' => $detailPemesanan->kode_barang,
                        'jumlah_pemesanan' => $detailPemesanan->jumlah_pemesanan,
                        'jumlah_disetujui' => $detailPemesanan->jumlah_disetujui,
                        'status' => 'disetujui'
                    ]);

                    // update stok barang
                    Barang::decreaseStock($detailPemesanan->kode_barang, $qty);
                }
            }

            return response()->json([
                'success' => true,
                'redirect' => route('pemesanan.index')
            ]);
        } catch (Exception $e) {
            return response()->json(['result' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        //
    }

    public function departemen() {
        return Auth::user()->departemen;
    }

    public function getData() {
        $pemesanan = Pemesanan::select(['kode_pemesanan', 'user_id', 'created_at'])
                        ->with('users')
                        ->get();
                        
        return Datatables::of($pemesanan)
                ->addColumn('action', function ($p) {
                    return "<a href='". route('pemesananDetail.index', $p->kode_pemesanan) ."'>Detail</a>";
                })
                ->make();
    }
}