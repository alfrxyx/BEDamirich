<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Ekspor laporan project ke PDF
     */
    public function exportProjectPdf(Request $request, $id)
    {
        // Validasi input tanggal
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        if ($start && $end) {
            $request->validate([
                'start_date' => 'date',
                'end_date' => 'date|after_or_equal:start_date'
            ]);
        }

        // Ambil project dengan relasi
        $project = Project::with([
            'tasks' => function ($query) use ($start, $end) {
                if ($start && $end) {
                    $query->whereBetween('tasks.created_at', [$start, $end]);
                }
                $query->with(['assignees:id,name', 'board:board_id,nama_board']);
            }
        ])->find($id);

        if (!$project) {
            return response()->json(['message' => 'Project tidak ditemukan'], 404);
        }

        // Siapkan data
        $periode = ($start && $end) 
            ? "Periode: " . date('d-m-Y', strtotime($start)) . " s/d " . date('d-m-Y', strtotime($end))
            : "Semua Periode";

        $data = [
            'project' => $project,
            'date' => date('d-m-Y'),
            'periode' => $periode
        ];

        // Generate & download PDF
        $pdf = Pdf::loadView('project_pdf', $data);
        return $pdf->download("Laporan_Project_{$project->nama_project}.pdf");
    }

    /**
     * API untuk laporan harian/mingguan (JSON)
     */
    public function getProjectReport(Request $request, $id)
    {
        $project = Project::with([
            'tasks.assignees:id,name',
            'tasks.board:board_id,nama_board'
        ])->find($id);

        if (!$project) {
            return response()->json(['message' => 'Project tidak ditemukan'], 404);
        }

        return response()->json([
            'message' => 'Laporan project berhasil diambil',
            'data' => $project
        ]);
    }
}