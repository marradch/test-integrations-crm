<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Http\Requests\ContactFormRequest;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function sendContactForm(ContactFormRequest $request)
    {
        $data = $request->validated();

        $name = $data['accountName'] ?? '';
        $phone = $data['accountPhone'] ?? '';

        // Dispatch job to send to SalesDrive (minimal payload)
        \App\Jobs\SendSalesDriveJob::dispatch($name, $phone);

        return response()->json(['status' => 'success']);
    }
}
