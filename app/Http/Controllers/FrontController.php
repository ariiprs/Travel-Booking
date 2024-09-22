<?php

namespace App\Http\Controllers;

use App\Models\PackageBank;
use App\Models\PackageTour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StorePackageTourRequest;
use App\Http\Requests\StorePackageBookingRequest;
use App\Models\PackageBooking;
use Carbon\Carbon;

class FrontController extends Controller
{
    public function index(){
        $package_tours = PackageTour::orderByDesc('id')->take(3)->get();
        return view('front.index', compact('package_tours'));
    }

    public function details(PackageTour $packageTour)
    {
        $latestPhotos = $packageTour->package_photos()->orderByDesc('id')->take(3)->get();
        return view('front.details', compact('packageTour', 'latestPhotos'));
    }

    public function book (PackageTour $packageTour){
        return view ('front.book', compact('packageTour'));
    }

    public function book_store(StorePackageBookingRequest $request ,PackageTour $packageTour){
        $user = Auth::user();
        $bank = PackageBank::orderByDesc('id')->first();

        $packageBookingId =  null;

        DB::transaction(function () use($request, $user, $packageTour, $bank, &$packageBookingId) {
            $validated = $request->validated();

            $startDate = new Carbon($validated['start_date']);
            $totalDays = $packageTour->days - 1;

            $endDate = $startDate->addDays($totalDays);

            $sub_total = $packageTour->price * $validated['quantity'];

            $insurance = 300000 * $validated['quantity'];
            $tax = $sub_total * 0.10;

            $validated['end_date'] = $endDate;
            $validated['user_id'] = $user->id;
            $validated['is_paid'] = false;
            $validated['proof'] = 'dummytrx.png';
            $validated['package_tour_id'] = $packageTour->id;
            $validated['package_bank_id'] = $bank->id;
            $validated['insurance'] = $insurance;
            $validated['tax'] = $tax;
            $validated['sub_total'] = $sub_total;
            $validated['total_amount'] = $sub_total + $insurance + $tax;

            $packageBooking = PackageBooking::create($validated);
            $packageBookingId = $packageBooking->id;
        });

        if($packageBookingId){
            return redirect()->route('front.choose_bank', $packageBookingId);
        }else{
            return back()->withErrors('Gagal melakukan booking');
        }

    }
}
