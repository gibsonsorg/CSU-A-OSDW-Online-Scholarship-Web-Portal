<?php

namespace App\Http\Controllers;

use App\Imports\OldGrantImport;
use App\Models\OldGrantRecord;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OldGrantController extends Controller
{
    /**
     * Display old academic grants
     */
    public function academicGrants()
    {
        $oldRecords = OldGrantRecord::where('scholarship_type', 'academic')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $filterType = 'old-academic';
        return view('admin.welcome', compact('oldRecords', 'filterType'));
    }

    /**
     * Display old non-academic grants
     */
    public function nonAcademicGrants()
    {
        $oldRecords = OldGrantRecord::where('scholarship_type', 'non_academic')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $filterType = 'old-non-academic';
        return view('admin.welcome', compact('oldRecords', 'filterType'));
    }

    /**
     * Show upload form (modal)
     */
    public function showUploadForm()
    {
        return view('admin.partials.old-grant-upload-modal');
    }

    /**
     * Handle Excel file upload
     */
    public function import(Request $request)
    {
        $request->validate([
            'grant_type' => 'required|in:academic,non_academic',
            'excel_file' => 'required|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);

        try {
            $grantType = $request->input('grant_type');
            $file = $request->file('excel_file');

            // Import the Excel file
            $import = new OldGrantImport($grantType);
            Excel::import($import, $file);

            $successCount = $import->getSuccessCount();
            $errorCount = $import->getErrorCount();

            if ($successCount === 0) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'No records were imported. Please check your Excel file format.',
                    'details' => $import->getErrors(),
                ], 400);
            }

            $message = "$successCount record(s) imported successfully.";
            if ($errorCount > 0) {
                $message .= " ($errorCount row(s) skipped due to errors)";
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'count' => $successCount,
                'errors' => $errorCount > 0 ? $import->getErrors() : [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
