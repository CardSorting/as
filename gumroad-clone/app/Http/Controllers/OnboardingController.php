<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function start()
    {
        return view('onboarding.start');
    }

    public function trial()
    {
        return view('onboarding.trial');
    }

    public function checkout()
    {
        return view('onboarding.checkout', [
            'trialPrice' => 3.00,
            'trialDays' => 30,
        ]);
    }

    public function process(Request $request)
    {
        // Here we would process the payment and create the subscription
        // For now, we'll just redirect to the setup wizard
        return redirect()->route('onboarding.setup');
    }

    public function setup()
    {
        return view('onboarding.setup');
    }
}
