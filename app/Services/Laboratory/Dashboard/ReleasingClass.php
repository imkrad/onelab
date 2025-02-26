<?php

namespace App\Services\Laboratory\Dashboard;

class ReleasingClass
{
    public function lists(){
        $data = ListResource::collection(
            Tsr::query()
            ->with('customer:id,name_id,name,is_main','customer.customer_name:id,name,has_branches')
            ->with('payment:tsr_id,id,total,subtotal,discount,or_number,is_paid,paid_at,status_id','payment.status:id,name,color,others')
            ->where('status_id',4)
            ->where('released_at',NULL)
            ->orderBy('created_at','DESC')
            ->get()
        );
        return $data;
    }
}
