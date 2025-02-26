<?php

namespace App\Exports;

use App\Models\FinanceReceipt;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class OrExport implements FromView
{
    protected $id;

    function __construct($id) {
            $this->id = $id;
    }

    public function view(): View
    {
        
        dd(FinanceReceipt::with('op.payorable','op.items.itemable','laboratory','op.collection')->where('id',$this->id)->first());
        return view('exports.or', [
            'receipt' => FinanceReceipt::with('op.payorable.customer_name','op.items.itemable.payment','laboratory','op.collection')->where('id',$this->id)->first()
        ]);
    }
}
