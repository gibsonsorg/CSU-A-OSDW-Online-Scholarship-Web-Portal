<?php

namespace App\Http\Controllers;

use App\Models\Scholarship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ScholarshipController extends Controller
{
    public function create(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', 'all');
        $scholarships = Scholarship::query()
            ->where('is_admin_created', true)
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('type_label', 'like', $like)
                        ->orWhere('short_information', 'like', $like);
                });
            })
            ->when(in_array($type, ['academic', 'non-academic'], true), fn ($query) => $query->where('type', $type))
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('admin.scholarships.create', compact('scholarships', 'search', 'type'));
    }

    public function store(Request $request)
    {
        $isAcademic = $request->input('type') === 'academic';
        $data = $request->validate([
            'type' => ['required', 'in:academic,non-academic'],
            'name' => ['required', 'string', 'max:255'],
            'type_label' => ['required', 'string', 'max:255'],
            'funding_type' => [
                'required',
                'in:' . implode(',', $isAcademic
                    ? ['Internationally Funded', 'In-house', 'Government-based', 'Private based']
                    : ['In house', 'Government based', 'locally funded', 'Private']),
            ],
            'scholarship_classification' => [
                'required',
                'in:' . implode(',', $isAcademic
                    ? ['Academic', 'Merit-based', 'Locally funded', "Local gov't", 'Academic-Merit']
                    : ['Sport-based', 'Employee benefit', 'Cultural Arts', 'Publication', 'Inclusive/PWD', 'Leadership', 'Alumni funded', 'Locally funded', 'Private']),
            ],
            'short_information' => ['required', 'string', 'max:1000'],
            'description' => ['required', 'string', 'max:10000'],
            'requirements' => ['required', 'array', 'min:1'],
            'requirements.*' => ['required', 'string', 'max:1000'],
            'required_documents' => ['nullable', 'array'],
            'required_documents.*' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
        ]);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('scholarship-logos', 'public');
        }

        $data['requirements'] = array_values(array_filter(array_map('trim', $data['requirements'])));
        $data['required_documents'] = array_values(array_filter(array_map('trim', $data['required_documents'] ?? [])));
        $data['status'] = 'active';
        $data['is_admin_created'] = true;
        unset($data['logo']);

        Scholarship::create($data);

        return redirect()
            ->route('admin.scholarships.create')
            ->with('success', 'Scholarship added successfully and is now available to students.');
    }

    public function edit(Scholarship $scholarship)
    {
        return view('admin.scholarships.edit', compact('scholarship'));
    }

    public function update(Request $request, Scholarship $scholarship)
    {
        $isAcademic = $request->input('type', $scholarship->type) === 'academic';
        $data = $request->validate([
            'type' => ['required', 'in:academic,non-academic'],
            'name' => ['required', 'string', 'max:255'],
            'type_label' => ['required', 'string', 'max:255'],
            'funding_type' => [
                'required',
                'in:' . implode(',', $isAcademic
                    ? ['Internationally Funded', 'In-house', 'Government-based', 'Private based']
                    : ['In house', 'Government based', 'locally funded', 'Private']),
            ],
            'scholarship_classification' => [
                'required',
                'in:' . implode(',', $isAcademic
                    ? ['Academic', 'Merit-based', 'Locally funded', "Local gov't", 'Academic-Merit']
                    : ['Sport-based', 'Employee benefit', 'Cultural Arts', 'Publication', 'Inclusive/PWD', 'Leadership', 'Alumni funded', 'Locally funded', 'Private']),
            ],
            'short_information' => ['required', 'string', 'max:1000'],
            'description' => ['required', 'string', 'max:10000'],
            'requirements' => ['required', 'array', 'min:1'],
            'requirements.*' => ['required', 'string', 'max:1000'],
            'required_documents' => ['nullable', 'array'],
            'required_documents.*' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
        ]);

        if ($request->hasFile('logo')) {
            if ($scholarship->logo_path) {
                Storage::disk('public')->delete($scholarship->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('scholarship-logos', 'public');
        } else {
            $data['logo_path'] = $scholarship->logo_path;
        }

        $data['requirements'] = array_values(array_filter(array_map('trim', $data['requirements'])));
        $data['required_documents'] = array_values(array_filter(array_map('trim', $data['required_documents'] ?? [])));
        unset($data['logo']);
        $scholarship->update($data);

        return redirect()->route('admin.scholarships.create')->with('success', 'Scholarship updated successfully.');
    }

    public function updateStatus(Request $request, Scholarship $scholarship)
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $scholarship->update($data);

        return response()->json([
            'status' => $scholarship->status,
            'message' => 'Scholarship status updated successfully.',
        ]);
    }

    public function destroy(Scholarship $scholarship)
    {
        if ($scholarship->logo_path) {
            Storage::disk('public')->delete($scholarship->logo_path);
        }

        $scholarship->delete();

        return redirect()->route('admin.scholarships.create')->with('success', 'Scholarship permanently deleted.');
    }
}
