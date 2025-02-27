<?php

namespace App\Services\Laboratory\Tsr;

use App\Models\Tsr;
use App\Models\TsrGroup;
use App\Models\TsrSample;
use App\Models\TsrAnalysis;
use App\Models\TsrPayment;
use App\Models\TsrService;
use App\Http\Resources\Laboratory\AnalysisResource;

class AnalysisClass
{
    public function lists($request){
        $data = SampleResource::collection(
            TsrSample::query()->where('tsr_id',$request->id)
            ->when($request->keyword, function ($query, $keyword) {
                $query->where('code', 'LIKE', "%{$keyword}%")->orWhere('name', 'LIKE', "%{$keyword}%");
            })
            ->orderBy('created_at','ASC')
            ->get()
        );
        return $data;
    }

    public function save($request){
        foreach($request->samples as $sample){
            foreach($request->lists as $list){
                $data = TsrAnalysis::create(array_merge($request->all(),[
                    'status_id' => 10,
                    'testservice_id' => $list['id'],
                    'fee' => $list['fee_num'],
                    'sample_id' => $sample
                ]));
                if(!$list['is_fixed']){
                    $id = TsrSample::where('id',$sample)->value('tsr_id');
                    $tsr = Tsr::where('id',$id)->update(['is_shelf' => 1]);
                }
                $data = TsrAnalysis::with('sample','testservice.method.method')->where('id',$data->id)->first();
                $total =  $this->updateTotal($data->sample->tsr_id,$list['fee']);
            }
        }
        return [
            'data' => $total,
            'message' => 'Analysis added was successful!', 
            'info' => "You've successfully created the new analysis."
        ];
    }

    public function service($request){
        $data = Tsr::findOrFail($request->id);
        $data->service()->create([
            'service_id' => $request->service['value'],
            'fee' => $request->service['fee'],
            'quantity' => $request->quantity,
            'total' => $request->total,
        ]);
        $total = $this->updateTotal($request->id,$request->total);
        return [
            'data' => $total,
            'message' => 'Service added was successful!', 
            'info' => "You've successfully added a service."
        ];
    }

    public function fee($request){
        $data = TsrAnalysis::findOrFail($request->id);
        $data->addfee()->create([
            'service_id' => $request->service['id'],
            'fee' => $request->service['fee'],
            'total' => $request->total,
            'quantity' => $request->quantity,
            'is_additional' => 1
        ]);
        $total = $this->updateTotal($request->tsr_id,$request->total);
        return [
            'data' => $total,
            'message' => 'Service added was successful!', 
            'info' => "You've successfully added a service."
        ];
    }

    public function remove($request){
        $id = $request->id;
        $tsr_id = $request->tsr_id;
        $data = TsrAnalysis::find($id);
        $fee = (float) trim(str_replace(',','',$data->fee),'₱ ');
        if($data->delete()){
            $payment = TsrPayment::with('discounted')->where('tsr_id',$tsr_id)->first();
            $subtotal = (float) trim(str_replace(',','',$payment->subtotal),'₱ ');
            $total = (float) trim(str_replace(',','',$payment->total),'₱ ');
            if($payment->discount_id === 1){
                $discount = 0;
                $subtotal = $subtotal - $fee;
                $total = $total - $fee;
            }else{
                $subtotal = $subtotal - $fee;
                $discount = (float) (($payment->discounted->value/100) * $subtotal);
                $total =  ((float) $subtotal - (float) $discount);
            }
            $payment->subtotal = $subtotal;
            $payment->discount = $discount;
            $payment->total = $total;
            $payment->save();
        }
        return [
            'data' => $payment,
            'message' => 'Sample was removed successful!', 
            'info' => "You've successfully remove the sample."
        ];
    }

    private function updateTotal($id,$fee){
        $data = TsrPayment::with('discounted')->where('tsr_id',$id)->first();
        $fee = (float) trim(str_replace(',','',$fee),'₱ ');
        $subtotal = (float) trim(str_replace(',','',$data->subtotal),'₱ ');
        if($data->discount_id === 1){
            $discount = 0;
            $subtotal = $subtotal + $fee;
            $total = $subtotal;
        }else{
            $subtotal = $subtotal + $fee;
            $discount = (float) (($data->discounted->value/100) * $subtotal);
            $total =  ((float) $subtotal - (float) $discount);
        }
        $data->subtotal = $subtotal;
        $data->discount = $discount;
        $data->total = $total;
        $data->save();
        return $data;
    }

    public function start($request){
        $tsr_id = $request->tsr_id;
        $data = TsrAnalysis::whereIn('id',$request->id)->update([
            'status_id' => $request->status_id,
            'analyst_id' => \Auth::user()->id,
            'start_at' => $request->start_at
        ]);
        
        if($data){
            if(TsrAnalysis::whereHas('sample',function ($query) use ($tsr_id){
                $query->whereHas('tsr',function ($query) use ($tsr_id){
                    $query->where('id',$tsr_id);
                });
            })->whereIn('status_id',[10,11])->count() === 0){
                $tsr = Tsr::where('id',$tsr_id)->update(['status_id' => 4]);
            }else{
                $tsr = Tsr::where('id',$tsr_id)->update(['status_id' => 3]);
            }
        }
        
        return [
            'data' => $data,
            'message' => 'Sample analysis successfully started!', 
            'info' => "You've successfully started the analyzation.",
        ];
    }

    public function end($request){
        $tsr_id = $request->tsr_id;
        $data = TsrAnalysis::whereIn('id',$request->id)->update([
            'status_id' => $request->status_id,
            'end_at' => $request->end_at
        ]);
        if($data){
            $count = TsrAnalysis::whereHas('sample',function ($query) use ($tsr_id){
                $query->whereHas('tsr',function ($query) use ($tsr_id){
                    $query->where('id',$tsr_id);
                });
            })->whereIn('status_id',[10,11])->count();
            if($count === 0){
                $tsr = Tsr::where('id',$tsr_id)->update(['status_id' => 4]);   
            }
        }
        return [
            'data' => $data,
            'message' => 'Analysis was completed!', 
            'info' => "You've successfully completed the analysis.",
        ];
    }

    public function top($request){
        $year = $request->year;
        $sort = $request->sort;
        $laboratory = $request->laboratory;
        $data = TsrAnalysis::whereHas('sample',function ($query) use ($year,$laboratory){
            $query->whereHas('tsr',function ($query) use ($year,$laboratory){
                $query->where('status_id',3)->whereYear('created_at',$year)
                ->when($laboratory, function ($query, $laboratory) {
                    $query->where('laboratory_id', $laboratory);
                });
            });
        })
        ->join('list_testservices', 'tsr_analyses.testservice_id', '=', 'list_testservices.id')
        ->join('list_names', 'list_testservices.testname_id', '=', 'list_names.id')
        ->select('list_names.name', \DB::raw('COUNT(*) as count'))
        ->groupBy('list_testservices.testname_id')
        ->orderBy('count', $sort)->paginate(10);
        return TestnameTopResource::collection($data);
    }

    public function group($request){
        $lists = $request->lists;
        foreach($lists as $list){
            $data = TsrGroup::create([
                'days' => $request->days,
                'date' => $request->date,
                'quantity' => $list['quantity'],
                'fee' => $list['fee_num'],
                'total' => $list['quantity']*$list['fee_num'],
                'testservice_id' => $list['id'],
                'status_id' => 23,
                'tsr_id' => $request->tsr_id
            ]);
            $total =  $this->updateTotal($request->tsr_id,$list['fee_num']);
        }

        return [
            'data' => true,
            'message' => 'Group added was successful!', 
            'info' => "You've successfully added a group."
        ];
    }
}
