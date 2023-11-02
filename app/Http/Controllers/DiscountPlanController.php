<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DiscountPlan;
use App\DiscountPlanCustomer;
use App\Customer;
use Spatie\Permission\Models\Role;
use Auth;

class DiscountPlanController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('discount_plan')) {
            $lims_discount_plan_all = DiscountPlan::with('customers')->orderBy('id', 'desc')->get();
            return view('backend.discount_plan.index', compact('lims_discount_plan_all'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function create()
    {
        $lims_customer_list = Customer::where('is_active', true)->get();
        return view('backend.discount_plan.create', compact('lims_customer_list'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        if(!isset($data['is_active'])) {
            $data['is_active'] = 0;
        }
        $lims_discount_plan = DiscountPlan::create($data);
        foreach ($data['customer_id'] as $key => $customer_id) {
            DiscountPlanCustomer::create(['discount_plan_id' => $lims_discount_plan->id, 'customer_id' => $customer_id]);
        }
        return redirect()->route('discount-plans.index')->with('message', 'DiscountPlan created successfully');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $lims_discount_plan = DiscountPlan::find($id);
        $lims_customer_list = Customer::where('is_active', true)->get();
        $customer_ids = DiscountPlanCustomer::where('discount_plan_id', $id)->pluck('customer_id')->toArray();
        return view('backend.discount_plan.edit', compact('lims_discount_plan', 'lims_customer_list', 'customer_ids'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        $lims_discount_plan = DiscountPlan::find($id);
        if(!isset($data['is_active'])) {
            $data['is_active'] = 0;
        }
        $pre_customer_ids = DiscountPlanCustomer::where('discount_plan_id', $id)->pluck('customer_id')->toArray();
        //deleting previous customer id if not exist
        foreach ($pre_customer_ids as $key => $customer_id) {
            if(!in_array($customer_id, $data['customer_id'])) {
                DiscountPlanCustomer::where([
                    ['discount_plan_id', $id],
                    ['customer_id', $customer_id]
                ])->first()->delete();
            }
        }
        //inserting new customer id
        foreach ($data['customer_id'] as $key => $customer_id) {
            if(!in_array($customer_id, $pre_customer_ids)) {
                DiscountPlanCustomer::create(['discount_plan_id' => $id, 'customer_id' => $customer_id]);
            }
        }
        $lims_discount_plan->update($data);
        return redirect()->route('discount-plans.index')->with('message', 'DiscountPlan updated successfully');
    }

    public function destroy($id)
    {
        //
    }
}
