<?php

namespace App\Services\Laboratory;

use App\Models\Tsr;
use App\Models\TsrSample;
use App\Models\TsrAnalysis;
use App\Models\TsrPayment;
use App\Models\Target;
use App\Models\Configuration;
use App\Models\ListLaboratory;
use App\Models\TargetBreakdown;

class TargetClass
{
    public function __construct()
    {
        $this->laboratory = (\Auth::check()) ? (\Auth::user()->userrole) ? \Auth::user()->userrole->laboratory_id : null : '';
        $this->ids =(\Auth::check()) ? (\Auth::user()->role == 'Administrator') ? [] : json_decode(Configuration::where('laboratory_id',$this->laboratory)->value('laboratories')) : '';
    }

    public function counts(){
        return [
            ['name' => 'KPIs with below 50%','color' => 'bg-danger-subtle'],
            ['name' => 'KPIs with 50% to 99%','color' => 'bg-warning-subtle'],
            ['name' => 'KPIs with 100% and above','color' => 'bg-success-subtle'],
            ['name' => 'Overall Accomplishment','color' => 'bg-info-subtle']
        ];
    }

    public function targets(){
        $year = date('Y');
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun','Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $data = Target::with('breakdowns.type')->where('year',$year)->where('laboratory_id',$this->laboratory)->first();
        if($data){

        }else{
            $kpis = [
                ['name' => 'Samples Received','is_consolidated' => 0, 'is_amount' => 0],
                ['name' => 'Services Conducted','is_consolidated' => 0, 'is_amount' => 0],
                ['name' => 'Customers Served','is_consolidated' => 0, 'is_amount' => 0],
                ['name' => 'New Customers Served','is_consolidated' => 1, 'is_amount' => 0],
                ['name' => 'Firms Served','is_consolidated' => 1, 'is_amount' => 0],
                ['name' => 'Actual Fees Collected','is_consolidated' => 0, 'is_amount' => 1],
                ['name' => 'Value of Assistance Rendered','is_consolidated' => 0, 'is_amount' => 1],
                ['name' => 'New Services Offered','is_consolidated' => 1, 'is_amount' => 0],
                ['name' => 'Weaned Out Services','is_consolidated' => 1, 'is_amount' => 0]
            ];
            $laboratories = $this->laboratories();

            $data = Target::create([
                'year' => $year,
                'data' => json_encode([]),
                'laboratory_id' => $this->laboratory
            ]);
            foreach($kpis as $kpi){
                if(!$kpi['is_consolidated']){
                    foreach($laboratories as $laboratory){
                        $breakdown = $data->breakdowns()->create([
                            'name' => $kpi['name'],
                            'count' => 0,
                            'laboratory_type' => $laboratory['value'],
                            'is_consolidated' => $kpi['is_consolidated'],
                            'is_amount' => $kpi['is_amount']
                        ]);
                    }
                }else{
                    $breakdown = $data->breakdowns()->create([
                        'name' => $kpi['name'],
                        'count' => 0,
                        'laboratory_type' => null,
                        'is_consolidated' => $kpi['is_consolidated'],
                        'is_amount' => $kpi['is_amount']
                    ]);
                }
            }
            $data = Target::with('breakdowns.type')->where('year',$year)->where('laboratory_id',$this->laboratory)->first();
        }
        $percentageCounts = [0,0,0,0];
        $breakdowns = $data->breakdowns;
        $grouped = $breakdowns->groupBy('name')->map(function ($items) use ($months, &$percentageCounts){
            $monthly = []; $total = 0;
            foreach($months as $index => $month){
                switch($items->first()['name']){
                    case 'Samples Received':
                        $count = TsrSample::whereMonth('created_at',$index+1)->whereHas('tsr', function ($query){
                            $query->where('laboratory_id',$this->laboratory);
                        })->count();
                    break;
                    case 'Services Conducted':
                        $count = TsrAnalysis::whereMonth('created_at',$index+1)->whereHas('sample', function ($query){
                            $query->whereHas('tsr', function ($query){
                                $query->where('laboratory_id',$this->laboratory);
                            });
                        })
                       ->count();
                    break;
                    case 'Customers Served':
                        $count = Tsr::whereMonth('created_at',$index+1)->count();
                    break;
                    case 'New Customers Served':
                        $count = Tsr::whereHas('customer', function ($query) use ($index){
                            $query->whereMonth('created_at',$index+1);
                        })
                        ->where('laboratory_id',$this->laboratory)->count();
                    break;
                    case 'Firms Served':
                        $count = Tsr::whereHas('customer', function ($query) use ($index){
                            $query->whereMonth('created_at',$index+1)->where('classification_id',8);
                        })
                        ->where('laboratory_id',$this->laboratory)->count();
                    break;
                    case 'Actual Fees Collected':
                        $count = TsrPayment::whereMonth('paid_at',$index+1)->where('is_paid',1)
                        ->whereHas('tsr', function ($query){
                            $query->where('laboratory_id',$this->laboratory);
                        })
                       ->sum('total');
                    break;
                    case 'Value of Assistance Rendered':
                        $count = TsrPayment::whereMonth('paid_at',$index+1)->where('is_free',1)
                        ->whereHas('tsr', function ($query){
                            $query->where('laboratory_id',$this->laboratory);
                        })
                       ->sum('discount');
                    break;
                    case 'New Services Offered':
                        $count = 0;
                    break;
                    case 'Weaned Out Services':
                        $count = 0;
                    break;
                    default: 
                    $count = 0;
                }
                $monthly[] = [
                    'name' => $month,
                    'is_amount' => $items->first()['is_amount'],
                    'count' => $count
                ];
                $total = $total + $count;
            }

            $target = $items->sum('count');
            $percentage = ($target > 0) ? ($total / $target) * 100 : 0;

            if ($percentage < 50) {
                $percentageCounts[0]++;
            } elseif ($percentage >= 50 && $percentage < 100) {
                $percentageCounts[1]++;
            } elseif ($percentage >= 100) {
                $percentageCounts[2]++;
            }

            $result = [
                'name' => $items->first()['name'],
                'target' => $items->sum('count'),
                'accom' => $total,
                'percentage' => $percentage,
                'is_consolidated' => $items->first()['is_consolidated'],
                'is_amount' => $items->first()['is_amount'],
                'breakdowns' => $items,
                'months' => $monthly,
            ];
            $percentageCounts[3] =  number_format($percentageCounts[3] + $percentage,2);
            return $result;
        });
        return [
            'year' => $data->year,
            'kpis' =>$grouped,
            'counts' => $percentageCounts
        ];
    }

    public function laboratories(){
        $lab_id = ($this->ids) ? array_column($this->ids, 'value') : null;
        $query = ListLaboratory::query();
        ($lab_id) ? $query->whereIn('id',$lab_id) : '';
        $data = $query->get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => $item->name,
                'short' => $item->short
            ];
        });
        return $data;
    }

    public function save($result){
        $items = $result->items;
        foreach($items as $item){
            $data = TargetBreakdown::where('id',$item['id'])->update([
                'count' => ($item['is_amount'])  ? trim(str_replace(',','',$item['count']),'₱') : $item['count'],
                'is_set' => 1
            ]);
        }

        return [
            'data' => [],
            'message' => 'Target udpate was successful!', 
            'info' => "You've successfully updated the target."
        ];
    }

    public function view($code){
        $year = date('Y');
        $code = ucwords($code);
        $data = Target::withWhereHas('breakdowns', function ($query) use ($code){
            $query->with('type')->where('name',$code);
        })
        ->where('year',$year)->where('laboratory_id',$this->laboratory)->first();
        $breakdowns = $data->breakdowns;

        $grouped = $breakdowns->groupBy('name')->map(function ($items){
            $target = $items->sum('count');
            $total = $items->sum('accom');
            $percentage = ($target > 0) ? ($total / $target) * 100 : 0;

            $topNames = [];
            foreach($items as $item){
                $lab_id = $item['laboratory_type'];
                $year = date('Y');
        
                switch($items->first()['name']){
                    case 'Samples Received':
                        $topNames[] = TsrSample::select('name', \DB::raw('COUNT(*) as count'))
                        ->whereHas('tsr',function ($query) use ($lab_id) {
                            $query->where('laboratory_id',$this->laboratory)->where('laboratory_type',$lab_id)->whereIn('status_id',[2,3,4]);
                        })->whereYear('created_at',$year)->groupBy('name')->orderBy('count', 'desc')->limit(10)->get();
                    break;
                    case 'Services Conducted':
                        $topNames[] = TsrAnalysis::with('testservice.testname')
                        ->whereHas('sample',function ($query) use ($lab_id) {
                            $query->whereHas('tsr',function ($query) use ($lab_id) {
                                $query->where('laboratory_id',$this->laboratory)->where('laboratory_type',$lab_id)->whereIn('status_id',[2,3,4]);
                            });
                        })
                        ->whereYear('created_at', $year)
                        ->select('testservice_id')
                        ->groupBy('testservice_id')
                        ->orderByRaw('COUNT(*) DESC')
                        ->limit(10)
                        ->get()
                        ->map(function ($item) {
                            return [
                                'name' => $item->testservice->testname->name, // Access the name via the relationship
                                'count' => TsrAnalysis::where('testservice_id', $item->testservice_id)->count()
                            ];
                        });
                    break;
                    default:
                     $topNames[]= null;
                }
            }

            return [
                'name' => $items->first()['name'],
                'target' => $items->sum('count'),
                'accom' => $items->sum('accom'),
                'percentage' => $percentage,
                'is_consolidated' => $items->first()['is_consolidated'],
                'is_amount' => $items->first()['is_amount'],
                'breakdowns' => $items,
                'tops' => $topNames
            ];
        });
        return inertia('Modules/Laboratory/Targets/View/Index',[
            'selected' => $grouped[$code]
        ]);
    }
}
