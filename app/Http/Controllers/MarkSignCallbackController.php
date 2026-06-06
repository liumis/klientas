<?php
namespace App\Http\Controllers;

use App\Models\Claim;
use App\Services\MarkSignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\SharePointController;

class MarkSignCallbackController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('MarkSign Callback apdorojamas:', $request->all());

        $docUuid = $request->input('uuid');
        $status = $request->input('status');

        if ($status === 'signed') {
            $claim = Claim::where('marksign_uuid', $docUuid)->first();

            if ($claim) {
                $claim->status = \App\Enums\ClaimStatus::Signed;
                try {
                    $markSign = app(MarkSignService::class);
                    $path = $markSign->downloadSignedDocument($docUuid);

                    if ($path) {
                        $claim->signed_pdf_path = $path;
                    }
                } catch (\Exception $e) {
                    Log::error('Klaida parsiunčiant pasirašytą PDF: ' . $e->getMessage());
                }

                $claim->save();
                Log::info("Claim ID {$claim->id} sėkmingai pažymėtas kaip pasirašytas.");
                $sp = new SharePointController();
                $sp->run($claim->id);
            } else {
                Log::warning("Callback gautas, bet claim su UUID {$docUuid} nerastas.");
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
