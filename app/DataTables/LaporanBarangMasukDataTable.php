<?php

namespace App\DataTables;

use App\DetailBarangMasuk;
use Yajra\DataTables\Services\DataTable;

class LaporanBarangMasukDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables($query);
    }

    public function query()
    {
        return DetailBarangMasuk::with('barang')->whereBetween('created_at', [$this->start, $this->end])->get();
    }

    public function html()
    {
        return $this->builder()
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->parameters($this->getBuilderParameters());
    }

    protected function getBuilderParameters()
    {
        return [
            'dom' => 'Bfrtip',
            'order'   => [[0, 'asc']],
            'buttons' => [],
        ];
    }

    protected function getColumns()
    {
        return [
            'Tanggal' => ['name' => 'created_at', 'data' => 'created_at'],
            'kode_barang',
            'Nama Barang' => ['name' => 'barang.nama_barang', 'data' => 'barang.nama_barang'],
            'jumlah'
        ];
    }

    protected function filename()
    {
        return 'LaporanBarangMasuk_' . date('YmdHis');
    }
}
