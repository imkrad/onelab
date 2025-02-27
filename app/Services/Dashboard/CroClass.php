<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use App\Models\Tsr;
use App\Models\TsrSample;
use App\Models\TsrAnalysis;
use App\Models\TsrPayment;
use App\Models\TsrPaymentDeduction;
use App\Models\ListStatus;

class CroClass
{
    public function __construct()
    {
        $this->laboratory = (\Auth::user()->userrole) ? \Auth::user()->userrole->laboratory_id : null;
        $this->start = now()->copy()->startOfMonth()->format('Y-m-d');
        $this->end = now()->copy()->endOfMonth()->format('Y-m-d');
    }

    public function info($request){
        return [
            'month' => date('F'),
            'date' => $this->start.' to '.$this->end
        ];
    }

    public function counts($request){
        return [
            $this->fees($request),
            $this->tsrs($request),
            $this->samples($request),
            $this->testservices($request),
        ];
    }

    private function tsrs($request){
        $series = [];
        $data = Tsr::select(\DB::raw('DATE(created_at) AS x'), \DB::raw('count(*) AS y'))
        ->where('laboratory_id', $this->laboratory)
        ->whereIn('status_id',[1,2,3,4]) //status is completed
        ->whereBetween('created_at', [$this->start, $this->end])
        ->groupBy(\DB::raw('DATE(created_at)'))
        ->orderBy(\DB::raw('DATE(created_at)'))
        ->get()->map(function ($item) {
            return [
                'x' => date('F d, Y',strtotime($item->x)),
                'y' => $item->y
            ];
        });
        $info = [
            'name' => 'Customer Served',
            'data' => $data
        ];
        array_push($series,$info);
        return $arr = [
            'name' => 'Customer Served',
            'icon' => 'ri-hand-coin-fill',
            'color' => '',
            'series' => $series,
            'total' => Tsr::whereBetween('created_at',[$this->start,$this->end])->whereIn('status_id',[1,2,3,4])->where('laboratory_id',$this->laboratory)->count()
        ];
    }

    private function samples($request){
        $series = [];
        $data = TsrSample::select(\DB::raw('DATE(created_at) AS x'), \DB::raw('count(*) AS y'))
        ->whereHas('tsr', function ($query){
            $query->where('laboratory_id',$this->laboratory)->whereIn('status_id',[1,2,3,4]);
        })
        ->whereBetween('created_at', [$this->start, $this->end])
        ->groupBy(\DB::raw('DATE(created_at)'))
        ->orderBy(\DB::raw('DATE(created_at)'))
        ->get()->map(function ($item) {
            return [
                'x' => date('F d, Y',strtotime($item->x)),
                'y' => $item->y
            ];
        });
        $info = [
            'name' => 'Samples Received',
            'data' => $data
        ];
        array_push($series,$info);
        return $arr = [
            'name' => 'Samples Received',
            'icon' => 'ri-inbox-archive-fill',
            'color' => '',
            'series' => $series,
            'total' => TsrSample::whereBetween('created_at',[$this->start,$this->end])->whereHas('tsr', function ($query){
                $query->where('laboratory_id',$this->laboratory)->whereIn('status_id',[1,2,3,4]);
            })->count()
        ];
    }

    private function testservices($request){
        $series = [];
        $data = TsrAnalysis::select(\DB::raw('DATE(created_at) AS x'), \DB::raw('count(*) AS y'))
        ->whereHas('sample', function ($query){
            $query->whereHas('tsr', function ($query){
                $query->where('laboratory_id',$this->laboratory)->whereIn('status_id',[1,2,3,4]);
            });
        })
        ->whereBetween('created_at', [$this->start, $this->end])
        ->groupBy(\DB::raw('DATE(created_at)'))
        ->orderBy(\DB::raw('DATE(created_at)'))
        ->get()->map(function ($item) {
            return [
                'x' => date('F d, Y',strtotime($item->x)),
                'y' => $item->y
            ];
        });
        $info = [
            'name' => 'Services Conducted',
            'data' => $data
        ];
        array_push($series,$info);
        return $arr = [
            'name' => 'Services Conducted',
            'icon' => 'ri-flask-fill',
            'color' => '',
            'series' => $series,
            'total' => TsrAnalysis::whereBetween('created_at',[$this->start,$this->end])->whereHas('sample', function ($query){
                $query->whereHas('tsr', function ($query){
                    $query->where('laboratory_id',$this->laboratory)->whereIn('status_id',[1,2,3,4]);
                });
            })
           ->count()
        ];
    }

    private function fees($request){
        $month = ($request->month) ? \DateTime::createFromFormat('F', $request->month)->format('m') : date('m');  
        $year = ($request->year) ? $request->year : date('Y');
        $wallet = TsrPaymentDeduction::whereMonth('created_at',$month)->whereYear('created_at',$year)
        ->whereHas('payment', function ($query){
            $query->whereHas('tsr', function ($query){
                $query->where('laboratory_id',$this->laboratory);
            });
        })->sum('amount');

        $contract = TsrPayment::where('status_id',18)->whereHas('tsr', function ($query) use ($month,$year){
            $query->whereMonth('created_at',$month)->whereYear('created_at',$year)->where('laboratory_id',$this->laboratory);
        })->sum('total');

        $pending = TsrPayment::where('status_id',6)->whereHas('tsr', function ($query) use ($month,$year){
            $query->whereMonth('created_at',$month)->whereYear('created_at',$year)->where('laboratory_id',$this->laboratory);
        })->sum('total');

        $gratis = TsrPayment::whereMonth('paid_at',$month)->whereYear('paid_at',$year)->where('is_free',1)
        ->whereHas('tsr', function ($query){
            $query->where('laboratory_id',$this->laboratory);
        })->sum('discount');

        $discount = TsrPayment::whereMonth('paid_at',$month)->whereYear('paid_at',$year)->where('is_free',0)
        ->whereHas('tsr', function ($query){
            $query->where('laboratory_id',$this->laboratory);
        })->sum('discount');

        $total = TsrPayment::where('is_child',0)->where('paid_at','!=',NULL)->whereHas('tsr', function ($query) use ($month,$year){
            $query->where('laboratory_id',$this->laboratory)->whereMonth('created_at',$month)->whereYear('created_at',$year)->where('status_id','!=',5);
        })->sum('total');

        return $arr = [
            'name' => 'Actual Fees Collected',
            'icon' => 'ri-bank-card-fill',
            'color' => 'bg-info-subtle',
            'total' => $total+$pending+$contract+$wallet+$gratis+$discount
        ];
    }

    public function statuses(){
        $data = ListStatus::whereIn('id',[1,2,3])
        ->withCount(['tsrs' => function ($query) {
            $query->where('laboratory_id',$this->laboratory);
        }])->get();
        return $data;
    }

    public function reminders($request){
        return [
            [
                'name' => 'Due Soon',
                'description' => '5 days ahead of the due date',
                'count' => Tsr::whereBetween('due_at', [Carbon::now()->startOfDay(), Carbon::now()->addDays(5)->endOfDay()])->where('laboratory_id',$this->laboratory)->where('status_id','!=',4)->count(),
                'icon' => 'ri-error-warning-line',
                'color' => 'bg-warning-subtle text-warning'
            ],
            [
                'name' => 'Overdue Request',
                'description' => 'Keep track of all laboratory tasks',
                'count' => Tsr::whereDate('due_at','<',now())->where('laboratory_id',$this->laboratory)->whereNotIn('status_id',[4,5])->count(),
                'icon' => 'ri-error-warning-fill',
                'color' => 'bg-danger-subtle text-danger'
            ],
            [
                'name' => 'For Released',
                'description' => 'Reports that are ready to be released',
                'count' => Tsr::where('status_id',4)->where('due_at','>',now())->where('released_at',null)->where('laboratory_id',$this->laboratory)->whereHas('samples', function ($query) {
                    $query->doesntHave('report');
                }, '=', 0)->count(),
                'icon' => 'ri-alert-fill',
                'color' => 'bg-success-subtle text-success'
            ],
            [
                'name' => 'Unclaimed Reports',
                'description' => 'Ensure follow-up on unclaimed reports.',
                'count' => Tsr::where('status_id',4)->where('due_at','<=', now()->subDays(30))->where('released_at',null)->where('laboratory_id',$this->laboratory)->whereHas('samples', function ($query) {
                    $query->doesntHave('report');
                }, '=', 0)->count(),
                'icon' => 'ri-information-fill',
                'color' => 'bg-dark-subtle text-dark'
            ],
        ];
    }
    
}
