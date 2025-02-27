<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventorySupplier;
use App\Models\InventoryEquipment;
use App\Http\Resources\Inventory\ItemResource;
use App\Http\Resources\Inventory\StockResource;
use App\Http\Resources\Inventory\EquipmentResource;
use App\Http\Resources\Inventory\SupplierResource;

class ViewService
{
    public function __construct()
    {   
        $this->role = (\Auth::check()) ? \Auth::user()->role : null;
        $this->laboratory = (\Auth::user()->userrole) ? \Auth::user()->userrole->laboratory_id : null;
        $this->type = (\Auth::user()->userrole) ? \Auth::user()->userrole->laboratory_type : null;
    }

    public function suppliers($request){
        $data = SupplierResource::collection(
            InventorySupplier::query()
            ->with('user.profile')
            ->with('municipality.province.region','municipality:code,name,province_code','barangay:code,name')
            // ->where('laboratory_id',$this->laboratory)
            // ->when($this->laboratory, function ($query, $laboratory) {
            //     $query->where('laboratory_id', $laboratory);
            // })
            ->when($this->role != 'Administrator', function ($query) {
                $query->where('laboratory_id',$this->laboratory);
            })
            ->where('is_active',$request->is_active)
            ->get()
        );
        return $data;
    }

    public function equipments($request){
        $data = EquipmentResource::collection(
            InventoryEquipment::query()
            ->with('type','supplier','user')
            ->where('laboratory_id',$this->laboratory)
            ->when($request->keyword, function ($query, $keyword) {
                $query->where('name', 'LIKE', "%{$keyword}%");
            })
            ->paginate($request->count)
        );
        return $data;
    }

    public function supplier_lists(){
        $data = InventorySupplier::where('laboratory_id',$this->laboratory)->where('is_active',1)->get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => $item->name
            ];
        });
        return $data;
    }

    public function statistics(){
        return [
            [
                'name' => 'Items',
                'color' => 'text-success',
                'icon' => 'ri-shopping-basket-2-fill',
                'total' => InventoryStock::count(),
                'select' => null
            ],
            [
                'name' => 'Ouf of Stock',
                'color' => 'text-warning',
                'icon' => 'ri-alert-fill',
                'total' => InventoryStock::whereHas('item', function ($query) {
                    $query->whereColumn('reorder', '>', 'quantity');
                })->count(),
                'select' => 'outofstock'
            ],
            [
                'name' => 'Expired',
                'color' => 'text-danger',
                'icon' => 'ri-alarm-warning-fill',
                'total' => InventoryStock::where('expired_at', '<=', now())->count(),
                'select' => 'expired'
            ],
        ];
    }

    public function search($request){
        $keyword = $request->keyword;
        $data = InventoryItem::with('category','unittype','stocks.withdrawals','stocks.unittype')
            ->where('laboratory_id',$this->laboratory)
            ->where('name', 'LIKE', "%{$keyword}%")->limit(5)->get();
        return ItemResource::collection($data);
    }

    public function lists($request){
        $data = StockResource::collection(
            InventoryStock::query()
            ->with('item.unittype','item.category','supplier')
            ->when($request->keyword, function ($query, $keyword) {
                $query->whereHas('item',function ($query) use ($keyword){
                    $query->where('name', 'LIKE', "%{$keyword}%");
                });
            })
            ->when($request->type, function ($query, $type) {
                if($type == 'expired'){
                    $query->where('date', '<=', now());
                }else{
                    $query->whereHas('item', function ($query) {
                        $query->whereColumn('reorder', '>', 'quantity');
                    });
                }
            })
            // ->whereHas('item',function ($query){
            //     $query ->where('laboratory_id',$this->laboratory)->where('laboratory_type',$this->type);
            // })
            ->paginate($request->count)
        );
        return $data;
    }
}
