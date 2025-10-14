<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TravelPlan;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\Response;

class PlanPdfController extends Controller
{
    /**
     * Export travel plan to PDF.
     */
    public function export(TravelPlan $plan): Response
    {
        // Authorization check
        if ($plan->user_id !== Auth::id()) {
            abort(403, 'Ten plan nie należy do Ciebie.');
        }

        // Can only export plans with AI content (not drafts)
        if ($plan->status === 'draft' || ! $plan->has_ai_plan) {
            abort(400, 'Nie można eksportować szkicu planu. Wygeneruj plan najpierw.');
        }

        // Load plan with relationships
        $plan->load(['days.points', 'user.preferences']);

        // Track PDF export
        $plan->increment('pdf_exports_count');

        // Generate filename
        $filename = $this->generateFilename($plan);

        // Generate PDF and save to temporary file
        $tempPath = storage_path('app/temp/' . $filename);

        // Ensure temp directory exists
        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        Pdf::view('pdf.travel-plan', ['plan' => $plan])
            ->format('a4')
            ->margins(15, 15, 15, 15)
            ->headerView('pdf.partials.header')
            ->footerView('pdf.partials.footer')
            ->save($tempPath);

        // Return as download and delete temp file after sending
        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generate PDF filename.
     */
    private function generateFilename(TravelPlan $plan): string
    {
        $title = str($plan->title)->slug()->toString();
        $destination = str($plan->destination)->slug()->toString();

        return "{$title}_{$destination}.pdf";
    }
}
