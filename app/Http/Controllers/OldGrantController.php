<?php

namespace App\Http\Controllers;

use App\Imports\OldGrantImport;
use App\Models\OldGrantRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class OldGrantController extends Controller
{
    /**
     * Display old academic grants
     */
    public function academicGrants(Request $request)
    {
        $oldRecords = $this->filteredOldRecords($request, 'academic');
        
        $filterType = 'old-academic';
        return view('admin.welcome', compact('oldRecords', 'filterType'));
    }

    /**
     * Display old non-academic grants
     */
    public function nonAcademicGrants(Request $request)
    {
        $oldRecords = $this->filteredOldRecords($request, 'non_academic');
        
        $filterType = 'old-non-academic';
        return view('admin.welcome', compact('oldRecords', 'filterType'));
    }

    private function filteredOldRecords(Request $request, string $grantType)
    {
        $search = trim((string) $request->query('search', ''));
        $status = strtolower((string) $request->query('status', 'all'));
        $allowedStatuses = ['all', 'approved', 'pending', 'rejected'];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $query = OldGrantRecord::where('scholarship_type', $grantType)
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function ($query) use ($like) {
                    $query->where('first_name', 'like', $like)
                        ->orWhere('middle_name', 'like', $like)
                        ->orWhere('course', 'like', $like)
                        ->orWhere('scholarship_type', 'like', $like)
                        ->orWhere('scholarship_name', 'like', $like)
                        ->orWhere('funding_source', 'like', $like)
                        ->orWhere('campus', 'like', $like);
                });
            })
            ->when($status !== 'all', function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->orderByDesc('created_at');

        return $query->paginate(10)->withQueryString();
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
            Log::error('Old grant import failed', ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Import failed.',
            ], 500);
        }
    }
}
