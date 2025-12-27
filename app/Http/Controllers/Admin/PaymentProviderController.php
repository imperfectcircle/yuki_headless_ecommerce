<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Payments\Models\PaymentProviderConfig;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentProviderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Payments/Providers/Index', [
            'providers' => PaymentProviderConfig::query()
                ->orderBy('position')
                ->get(),
        ]);
    }

    public function toggle(PaymentProviderConfig $provider)
    {
        $provider->update([
            'enabled' => !$provider->enabled,
        ]);

        return back();
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'providers' => ['required', 'array'],
            'providers.*.id' => ['required', 'exists:payment_providers,id'],
            'providers.*.position' => ['required', 'integer'],
        ]);

        foreach ($request->providers as $item) {
            PaymentProviderConfig::where('id', $item['id'])
                ->update(['position' => $item['position']]);
        }

        return back();
    }
}
