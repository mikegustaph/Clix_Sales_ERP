<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Keygen;
use App\Brand;
use App\Category;
use App\Unit;
use App\Tax;
use App\Warehouse;
use App\Supplier;
use App\Product;
use App\ProductBatch;
use App\Product_Warehouse;
use App\Product_Supplier;
use Auth;
use DNS1D;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;
use DB;
use App\Variant;
use App\ProductVariant;
use App\Purchase;
use App\ProductPurchase;
use App\Payment;
use App\Traits\TenantInfo;
use App\Traits\CacheForget;

class ProductController extends Controller
{
    use CacheForget;
    use TenantInfo;

    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('products-index')){            
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if(empty($all_permission))
                $all_permission[] = 'dummy text';
            $role_id = $role->id;
            $numberOfProduct = Product::where('is_active', true)->count();
            return view('backend.product.index', compact('all_permission', 'role_id', 'numberOfProduct'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function productData(Request $request)
    {
        $columns = array( 
            2 => 'name', 
            3 => 'code',
            4 => 'brand_id',
            5 => 'category_id',
            6 => 'qty',
            7 => 'unit_id',
            8 => 'price',
            9 => 'cost',
            10 => 'stock_worth'
        );
        
        $totalData = Product::where('is_active', true)->count();
        $totalFiltered = $totalData; 

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'products.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        if(empty($request->input('search.value'))){
            $products = Product::with('category', 'brand', 'unit')->offset($start)
                        ->where('is_active', true)
                        ->limit($limit)
                        ->orderBy($order,$dir)
                        ->get();
        }
        else
        {
            $search = $request->input('search.value'); 
            $products =  Product::select('products.*')
                        ->with('category', 'brand', 'unit')
                        ->join('categories', 'products.category_id', '=', 'categories.id')
                        ->leftjoin('brands', 'products.brand_id', '=', 'brands.id')
                        ->where([
                            ['products.name', 'LIKE', "%{$search}%"],
                            ['products.is_active', true]
                        ])
                        ->orWhere([
                            ['products.code', 'LIKE', "%{$search}%"],
                            ['products.is_active', true]
                        ])
                        ->orWhere([
                            ['categories.name', 'LIKE', "%{$search}%"],
                            ['categories.is_active', true],
                            ['products.is_active', true]
                        ])
                        ->orWhere([
                            ['brands.title', 'LIKE', "%{$search}%"],
                            ['brands.is_active', true],
                            ['products.is_active', true]
                        ])
                        ->offset($start)
                        ->limit($limit)
                        ->orderBy($order,$dir)->get();

            $totalFiltered = Product::
                            join('categories', 'products.category_id', '=', 'categories.id')
                            ->leftjoin('brands', 'products.brand_id', '=', 'brands.id')
                            ->where([
                                ['products.name','LIKE',"%{$search}%"],
                                ['products.is_active', true]
                            ])
                            ->orWhere([
                                ['products.code', 'LIKE', "%{$search}%"],
                                ['products.is_active', true]
                            ])
                            ->orWhere([
                                ['categories.name', 'LIKE', "%{$search}%"],
                                ['categories.is_active', true],
                                ['products.is_active', true]
                            ])
                            ->orWhere([
                                ['brands.title', 'LIKE', "%{$search}%"],
                                ['brands.is_active', true],
                                ['products.is_active', true]
                            ])
                            ->count();
        }
        $data = array();
        if(!empty($products))
        {
            foreach ($products as $key=>$product)
            {
                $nestedData['id'] = $product->id;
                $nestedData['key'] = $key;
                $product_image = explode(",", $product->image);
                $product_image = htmlspecialchars($product_image[0]);
                if($product_image)
                    $nestedData['image'] = '<img src="'.url('images/product', $product_image).'" height="80" width="80">';
                else
                    $nestedData['image'] = '<img src="images/zummXD2dvAtI.png" height="80" width="80">';
                $nestedData['name'] = $product->name;
                $nestedData['code'] = $product->code;
                if($product->brand)
                    $nestedData['brand'] = $product->brand->title;
                else
                    $nestedData['brand'] = "N/A";
                $nestedData['category'] = $product->category->name;
                if(Auth::user()->role_id > 2 && $product->type == 'standard') {
                    $nestedData['qty'] = Product_Warehouse::where([
                                                ['product_id', $product->id],
                                                ['warehouse_id', Auth::user()->warehouse_id]
                                            ])->sum('qty');
                }
                else
                    $nestedData['qty'] = $product->qty;

                if($product->unit_id)
                    $nestedData['unit'] = $product->unit->unit_name;
                else
                    $nestedData['unit'] = 'N/A';
                
                $nestedData['price'] = $product->price;
                $nestedData['cost'] = $product->cost;

                if(config('currency_position') == 'prefix')
                    $nestedData['stock_worth'] = config('currency').' '.($nestedData['qty'] * $product->price).' / '.config('currency').' '.($nestedData['qty'] * $product->cost);
                else
                    $nestedData['stock_worth'] = ($nestedData['qty'] * $product->price).' '.config('currency').' / '.($nestedData['qty'] * $product->cost).' '.config('currency');

                $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.trans("file.action").'
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                            <li>
                                <button="type" class="btn btn-link view"><i class="fa fa-eye"></i> '.trans('file.View').'</button>
                            </li>';
                
                if(in_array("products-edit", $request['all_permission']))
                    $nestedData['options'] .= '<li>
                            <a href="'.route('products.edit', $product->id).'" class="btn btn-link"><i class="fa fa-edit"></i> '.trans('file.edit').'</a>
                        </li>';
                if(in_array("product_history", $request['all_permission']))
                    $nestedData['options'] .= \Form::open(["route" => "products.history", "method" => "GET"] ).'
                            <li>
                                <input type="hidden" name="product_id" value="'.$product->id.'" />
                                <button type="submit" class="btn btn-link"><i class="dripicons-checklist"></i> '.trans("file.Product History").'</button> 
                            </li>'.\Form::close();
                if(in_array("print_barcode", $request['all_permission'])) {
                    $product_info = $product->code.' ('.$product->name.')';
                    $nestedData['options'] .= \Form::open(["route" => "product.printBarcode", "method" => "GET"] ).'
                        <li>
                            <input type="hidden" name="data" value="'.$product_info.'" />
                            <button type="submit" class="btn btn-link"><i class="dripicons-print"></i> '.trans("file.print_barcode").'</button> 
                        </li>'.\Form::close(); 
                }
                if(in_array("products-delete", $request['all_permission']))
                    $nestedData['options'] .= \Form::open(["route" => ["products.destroy", $product->id], "method" => "DELETE"] ).'
                            <li>
                              <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="fa fa-trash"></i> '.trans("file.delete").'</button> 
                            </li>'.\Form::close().'
                        </ul>
                    </div>';
                // data for product details by one click
                if($product->tax_id)
                    $tax = Tax::find($product->tax_id)->name;
                else
                    $tax = "N/A";

                if($product->tax_method == 1)
                    $tax_method = trans('file.Exclusive');
                else
                    $tax_method = trans('file.Inclusive');

                $nestedData['product'] = array( '[ "'.$product->type.'"', ' "'.$product->name.'"', ' "'.$product->code.'"', ' "'.$nestedData['brand'].'"', ' "'.$nestedData['category'].'"', ' "'.$nestedData['unit'].'"', ' "'.$product->cost.'"', ' "'.$product->price.'"', ' "'.$tax.'"', ' "'.$tax_method.'"', ' "'.$product->alert_quantity.'"', ' "'.preg_replace('/\s+/S', " ", $product->product_details).'"', ' "'.$product->id.'"', ' "'.$product->product_list.'"', ' "'.$product->variant_list.'"', ' "'.$product->qty_list.'"', ' "'.$product->price_list.'"', ' "'.$nestedData['qty'].'"', ' "'.$product->image.'"', ' "'.$product->is_variant.'"]'
                );
                //$nestedData['imagedata'] = DNS1D::getBarcodePNG($product->code, $product->barcode_symbology);
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),  
            "recordsTotal"    => intval($totalData),  
            "recordsFiltered" => intval($totalFiltered), 
            "data"            => $data   
        );
            
        echo json_encode($json_data);
    }
    
    public function create()
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('products-add')){
            $lims_product_list_without_variant = $this->productWithoutVariant();
            $lims_product_list_with_variant = $this->productWithVariant();
            $lims_brand_list = Brand::where('is_active', true)->get();
            $lims_category_list = Category::where('is_active', true)->get();
            $lims_unit_list = Unit::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $numberOfProduct = Product::where('is_active', true)->count();
            return view('backend.product.create',compact('lims_product_list_without_variant', 'lims_product_list_with_variant', 'lims_brand_list', 'lims_category_list', 'lims_unit_list', 'lims_tax_list', 'lims_warehouse_list', 'numberOfProduct'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'code' => [
                'max:255',
                    Rule::unique('products')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'name' => [
                'max:255',
                    Rule::unique('products')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ]
        ]);
        $data = $request->except('image', 'file');
        
        if(isset($data['is_variant'])) {
            $data['variant_option'] = json_encode($data['variant_option']);
            $data['variant_value'] = json_encode($data['variant_value']);
        }
        else {
            $data['variant_option'] = $data['variant_value'] = null;
        }
        $data['name'] = preg_replace('/[\n\r]/', "<br>", htmlspecialchars(trim($data['name'])));
        if($data['type'] == 'combo') {
            $data['product_list'] = implode(",", $data['product_id']);
            $data['variant_list'] = implode(",", $data['variant_id']);
            $data['qty_list'] = implode(",", $data['product_qty']);
            $data['price_list'] = implode(",", $data['unit_price']);
            $data['cost'] = $data['unit_id'] = $data['purchase_unit_id'] = $data['sale_unit_id'] = 0;
        }
        elseif($data['type'] == 'digital' || $data['type'] == 'service')
            $data['cost'] = $data['unit_id'] = $data['purchase_unit_id'] = $data['sale_unit_id'] = 0;

        $data['product_details'] = str_replace('"', '@', $data['product_details']);

        if($data['starting_date'])
            $data['starting_date'] = date('Y-m-d', strtotime($data['starting_date']));
        if($data['last_date'])
            $data['last_date'] = date('Y-m-d', strtotime($data['last_date']));
        $data['is_active'] = true;
        $images = $request->image;
        $image_names = [];
        if($images) {          
            foreach ($images as $key => $image) {
                $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = date("Ymdhis") . ($key+1);
                if(!config('database.connections.saleprosaas_landlord')) {
                    $imageName = $imageName . '.' . $ext;
                    $image->move('public/images/product', $imageName);
                }
                else {
                    $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                    $image->move('public/images/product', $imageName);
                }
                $image_names[] = $imageName;
            }
            $data['image'] = implode(",", $image_names);
        }
        else {
            $data['image'] = 'zummXD2dvAtI.png';
        }
        $file = $request->file;
        if ($file) {
            $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $fileName = strtotime(date('Y-m-d H:i:s'));
            $fileName = $fileName . '.' . $ext;
            $file->move('public/product/files', $fileName);
            $data['file'] = $fileName;
        }
        //return $data;
        $lims_product_data = Product::create($data);
        //dealing with initial stock and auto purchase
        $initial_stock = 0;
        if(isset($data['is_initial_stock']) && !isset($data['is_variant']) && !isset($data['is_batch'])) {
            foreach ($data['stock_warehouse_id'] as $key => $warehouse_id) {
                $stock = $data['stock'][$key];
                if($stock > 0) {
                    $this->autoPurchase($lims_product_data, $warehouse_id, $stock);
                    $initial_stock += $stock;
                }
            }
        }
        if($initial_stock > 0) {
            $lims_product_data->qty += $initial_stock;
            $lims_product_data->save();
        }
        //dealing with product variant
        if(!isset($data['is_batch']))
            $data['is_batch'] = null;
        if(isset($data['is_variant'])) {
            foreach ($data['variant_name'] as $key => $variant_name) {
                $lims_variant_data = Variant::firstOrCreate(['name' => $data['variant_name'][$key]]);
                $lims_variant_data->name = $data['variant_name'][$key];
                $lims_variant_data->save();
                $lims_product_variant_data = new ProductVariant;             
                $lims_product_variant_data->product_id = $lims_product_data->id;
                $lims_product_variant_data->variant_id = $lims_variant_data->id;
                $lims_product_variant_data->position = $key + 1;
                $lims_product_variant_data->item_code = $data['item_code'][$key];
                $lims_product_variant_data->additional_cost = $data['additional_cost'][$key];
                $lims_product_variant_data->additional_price = $data['additional_price'][$key];
                $lims_product_variant_data->qty = 0;
                $lims_product_variant_data->save();
            }
        }
        if(isset($data['is_diffPrice'])) {
            foreach ($data['diff_price'] as $key => $diff_price) {
                if($diff_price) {
                    Product_Warehouse::create([
                        "product_id" => $lims_product_data->id,
                        "warehouse_id" => $data["warehouse_id"][$key],
                        "qty" => 0,
                        "price" => $diff_price
                    ]);
                }
            }
        }
        $this->cacheForget('product_list');
        $this->cacheForget('product_list_with_variant');
        \Session::flash('create_message', 'Product created successfully');
    }

    public function autoPurchase($product_data, $warehouse_id, $stock)
    {
        $data['reference_no'] = 'pr-' . date("Ymd") . '-'. date("his");
        $data['user_id'] = Auth::id();
        $data['warehouse_id'] = $warehouse_id;
        $data['item'] = 1;
        $data['total_qty'] = $stock;
        $data['total_discount'] = 0;
        $data['status'] = 1;
        $data['payment_status'] = 2;
        if($product_data->tax_id) {
            $tax_data = DB::table('taxes')->select('rate')->find($product_data->tax_id);
            if($product_data->tax_method == 1) {
                $net_unit_cost = number_format($product_data->cost, 2, '.', '');
                $tax = number_format($product_data->cost * $stock * ($tax_data->rate / 100), 2, '.', '');
                $cost = number_format(($product_data->cost * $stock) + $tax, 2, '.', '');
            }
            else {
                $net_unit_cost = number_format((100 / (100 + $tax_data->rate)) * $product_data->cost, 2, '.', '');
                $tax = number_format(($product_data->cost - $net_unit_cost) * $stock, 2, '.', '');
                $cost = number_format($product_data->cost * $stock, 2, '.', '');
            }
            $tax_rate = $tax_data->rate;
            $data['total_tax'] = $tax;
            $data['total_cost'] = $cost;
        }
        else {
            $data['total_tax'] = 0.00;
            $data['total_cost'] = number_format($product_data->cost * $stock, 2, '.', '');
            $net_unit_cost = number_format($product_data->cost, 2, '.', '');
            $tax_rate = 0.00;
            $tax = 0.00;
            $cost = number_format($product_data->cost * $stock, 2, '.', '');
        }

        $product_warehouse_data = Product_Warehouse::select('id', 'qty')
                                ->where([
                                    ['product_id', $product_data->id],
                                    ['warehouse_id', $warehouse_id]
                                ])->first();
        if($product_warehouse_data) {
            $product_warehouse_data->qty += $stock;
            $product_warehouse_data->save();
        }
        else {
            $lims_product_warehouse_data = new Product_Warehouse();
            $lims_product_warehouse_data->product_id = $product_data->id;
            $lims_product_warehouse_data->warehouse_id = $warehouse_id;
            $lims_product_warehouse_data->qty = $stock;
            $lims_product_warehouse_data->save();
        }
        $data['order_tax'] = 0;
        $data['grand_total'] = $data['total_cost'];
        $data['paid_amount'] = $data['grand_total'];
        //insetting data to purchase table
        $purchase_data = Purchase::create($data);
        //inserting data to product_purchases table
        ProductPurchase::create([
            'purchase_id' => $purchase_data->id,
            'product_id' => $product_data->id,
            'qty' => $stock,
            'recieved' => $stock,
            'purchase_unit_id' => $product_data->unit_id,
            'net_unit_cost' => $net_unit_cost,
            'discount' => 0,
            'tax_rate' => $tax_rate,
            'tax' => $tax,
            'total' => $cost
        ]);
        //inserting data to payments table
        Payment::create([
            'payment_reference' => 'ppr-' . date("Ymd") . '-'. date("his"),
            'user_id' => Auth::id(),
            'purchase_id' => $purchase_data->id,
            'account_id' => 0,
            'amount' => $data['grand_total'],
            'change' => 0,
            'paying_method' => 'Cash'
        ]);
    }

    public function history(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('product_history')) {
            if($request->input('warehouse_id'))
                $warehouse_id = $request->input('warehouse_id');
            else
                $warehouse_id = 0;

            if($request->input('starting_date')) {
                $starting_date = $request->input('starting_date');
                $ending_date = $request->input('ending_date');
            }
            else {
                $starting_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d') )))));
                $ending_date = date("Y-m-d");
            }
            $product_id = $request->input('product_id');
            $product_data = Product::select('name', 'code')->find($product_id);
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            return view('backend.product.history',compact('starting_date', 'ending_date', 'warehouse_id', 'product_id', 'product_data', 'lims_warehouse_list'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function saleHistoryData(Request $request)
    {
        $columns = array( 
            1 => 'created_at', 
            2 => 'reference_no',
        );

        $product_id = $request->input('product_id');
        $warehouse_id = $request->input('warehouse_id');

        $q = DB::table('sales')
            ->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
            ->where('product_sales.product_id', $product_id)
            ->whereDate('sales.created_at', '>=' ,$request->input('starting_date'))
            ->whereDate('sales.created_at', '<=' ,$request->input('ending_date'));
        if($warehouse_id)
            $q = $q->where('warehouse_id', $warehouse_id);
        if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
            $q = $q->where('sales.user_id', Auth::id());
        
        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'sales.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->join('customers', 'sales.customer_id', '=', 'customers.id')
                ->join('warehouses', 'sales.warehouse_id', '=', 'warehouses.id')
                ->select('sales.id', 'sales.reference_no', 'sales.created_at', 'customers.name as customer_name', 'customers.phone_number as customer_number', 'warehouses.name as warehouse_name', 'product_sales.qty', 'product_sales.sale_unit_id', 'product_sales.total')
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $sales = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('sales.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $sales =  $q->orwhere([
                                ['sales.reference_no', 'LIKE', "%{$search}%"],
                                ['sales.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['sales.reference_no', 'LIKE', "%{$search}%"],
                                    ['sales.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $sales =  $q->orwhere('sales.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('sales.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($sales))
        {
            foreach ($sales as $key => $sale)
            {
                $nestedData['id'] = $sale->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($sale->created_at));
                $nestedData['reference_no'] = $sale->reference_no;
                $nestedData['warehouse'] = $sale->warehouse_name;
                $nestedData['customer'] = $sale->customer_name.' ['.($sale->customer_number).']';
                $nestedData['qty'] = number_format($sale->qty, config('decimal'));
                if($sale->sale_unit_id) {
                    $unit_data = DB::table('units')->select('unit_code')->find($sale->sale_unit_id);
                    $nestedData['qty'] .= ' '.$unit_data->unit_code;
                }
                $nestedData['unit_price'] = number_format(($sale->total / $sale->qty), config('decimal'));
                $nestedData['sub_total'] = number_format($sale->total, config('decimal'));
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),  
            "recordsTotal"    => intval($totalData),  
            "recordsFiltered" => intval($totalFiltered), 
            "data"            => $data   
        );
        echo json_encode($json_data);
    }

    public function purchaseHistoryData(Request $request)
    {
        $columns = array( 
            1 => 'created_at', 
            2 => 'reference_no',
        );

        $product_id = $request->input('product_id');
        $warehouse_id = $request->input('warehouse_id');

        $q = DB::table('purchases')
            ->join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
            ->where('product_purchases.product_id', $product_id)
            ->whereDate('purchases.created_at', '>=' ,$request->input('starting_date'))
            ->whereDate('purchases.created_at', '<=' ,$request->input('ending_date'));
        if($warehouse_id)
            $q = $q->where('warehouse_id', $warehouse_id);
        if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
            $q = $q->where('purchases.user_id', Auth::id());
        
        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'purchases.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
                ->join('warehouses', 'purchases.warehouse_id', '=', 'warehouses.id')
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $purchases = $q->select('purchases.id', 'purchases.reference_no', 'purchases.created_at', 'purchases.supplier_id', 'suppliers.name as supplier_name', 'suppliers.phone_number as supplier_number', 'warehouses.name as warehouse_name', 'product_purchases.qty', 'product_purchases.purchase_unit_id', 'product_purchases.total')->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('purchases.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $purchases =  $q->select('purchases.id', 'purchases.reference_no', 'purchases.created_at', 'purchases.supplier_id', 'suppliers.name as supplier_name', 'suppliers.phone_number as supplier_number', 'warehouses.name as warehouse_name', 'product_purchases.qty', 'product_purchases.purchase_unit_id', 'product_purchases.total')
                            ->orwhere([
                                ['purchases.reference_no', 'LIKE', "%{$search}%"],
                                ['purchases.user_id', Auth::id()]
                            ])->get();
                $totalFiltered = $q->orwhere([
                                    ['purchases.reference_no', 'LIKE', "%{$search}%"],
                                    ['purchases.user_id', Auth::id()]
                                ])->count();
            }
            else {
                $purchases =  $q->select('purchases.id', 'purchases.reference_no', 'purchases.created_at', 'purchases.supplier_id', 'suppliers.name as supplier_name', 'suppliers.phone_number as supplier_number', 'warehouses.name as warehouse_name', 'product_purchases.qty', 'product_purchases.purchase_unit_id', 'product_purchases.total')
                            ->orwhere('purchases.reference_no', 'LIKE', "%{$search}%")
                            ->get();
                $totalFiltered = $q->orwhere('purchases.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($purchases))
        {
            foreach ($purchases as $key => $purchase)
            {
                $nestedData['id'] = $purchase->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($purchase->created_at));
                $nestedData['reference_no'] = $purchase->reference_no;
                $nestedData['warehouse'] = $purchase->warehouse_name;
                if($purchase->supplier_id)
                    $nestedData['supplier'] = $purchase->supplier_name.' ['.($purchase->supplier_number).']';
                else
                    $nestedData['supplier'] = 'N/A';
                $nestedData['qty'] = number_format($purchase->qty, config('decimal'));
                if($purchase->purchase_unit_id) {
                    $unit_data = DB::table('units')->select('unit_code')->find($purchase->purchase_unit_id);
                    $nestedData['qty'] .= ' '.$unit_data->unit_code;
                }
                $nestedData['unit_cost'] = number_format(($purchase->total / $purchase->qty), config('decimal'));
                $nestedData['sub_total'] = number_format($purchase->total, config('decimal'));
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),  
            "recordsTotal"    => intval($totalData),  
            "recordsFiltered" => intval($totalFiltered), 
            "data"            => $data   
        );
        echo json_encode($json_data);
    }

    public function saleReturnHistoryData(Request $request)
    {
        $columns = array( 
            1 => 'created_at', 
            2 => 'reference_no',
        );

        $product_id = $request->input('product_id');
        $warehouse_id = $request->input('warehouse_id');

        $q = DB::table('returns')
            ->join('product_returns', 'returns.id', '=', 'product_returns.return_id')
            ->where('product_returns.product_id', $product_id)
            ->whereDate('returns.created_at', '>=' ,$request->input('starting_date'))
            ->whereDate('returns.created_at', '<=' ,$request->input('ending_date'));
        if($warehouse_id)
            $q = $q->where('warehouse_id', $warehouse_id);
        if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
            $q = $q->where('returns.user_id', Auth::id());
        
        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'returns.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->join('customers', 'returns.customer_id', '=', 'customers.id')
                ->join('warehouses', 'returns.warehouse_id', '=', 'warehouses.id')
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $returnss = $q->select('returns.id', 'returns.reference_no', 'returns.created_at', 'customers.name as customer_name', 'customers.phone_number as customer_number', 'warehouses.name as warehouse_name', 'product_returns.qty', 'product_returns.sale_unit_id', 'product_returns.total')->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('returns.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $returnss =  $q->select('returns.id', 'returns.reference_no', 'returns.created_at', 'customers.name as customer_name', 'customers.phone_number as customer_number', 'warehouses.name as warehouse_name', 'product_returns.qty', 'product_returns.sale_unit_id', 'product_returns.total')
                            ->orwhere([
                                ['returns.reference_no', 'LIKE', "%{$search}%"],
                                ['returns.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['returns.reference_no', 'LIKE', "%{$search}%"],
                                    ['returns.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $returnss =  $q->select('returns.id', 'returns.reference_no', 'returns.created_at', 'customers.name as customer_name', 'customers.phone_number as customer_number', 'warehouses.name as warehouse_name', 'product_returns.qty', 'product_returns.sale_unit_id', 'product_returns.total')
                            ->orwhere('returns.reference_no', 'LIKE', "%{$search}%")
                            ->get();
                $totalFiltered = $q->orwhere('returns.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($returnss))
        {
            foreach ($returnss as $key => $returns)
            {
                $nestedData['id'] = $returns->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($returns->created_at));
                $nestedData['reference_no'] = $returns->reference_no;
                $nestedData['warehouse'] = $returns->warehouse_name;
                $nestedData['customer'] = $returns->customer_name.' ['.($returns->customer_number).']';
                $nestedData['qty'] = number_format($returns->qty, config('decimal'));
                if($returns->sale_unit_id) {
                    $unit_data = DB::table('units')->select('unit_code')->find($returns->sale_unit_id);
                    $nestedData['qty'] .= ' '.$unit_data->unit_code;
                }
                $nestedData['unit_price'] = number_format(($returns->total / $returns->qty), config('decimal'));
                $nestedData['sub_total'] = number_format($returns->total, config('decimal'));
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),  
            "recordsTotal"    => intval($totalData),  
            "recordsFiltered" => intval($totalFiltered), 
            "data"            => $data   
        );
        echo json_encode($json_data);
    }

    public function purchaseReturnHistoryData(Request $request)
    {
        $columns = array( 
            1 => 'created_at', 
            2 => 'reference_no',
        );

        $product_id = $request->input('product_id');
        $warehouse_id = $request->input('warehouse_id');

        $q = DB::table('return_purchases')
            ->join('purchase_product_return', 'return_purchases.id', '=', 'purchase_product_return.return_id')
            ->where('purchase_product_return.product_id', $product_id)
            ->whereDate('return_purchases.created_at', '>=' ,$request->input('starting_date'))
            ->whereDate('return_purchases.created_at', '<=' ,$request->input('ending_date'));
        if($warehouse_id)
            $q = $q->where('warehouse_id', $warehouse_id);
        if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
            $q = $q->where('return_purchases.user_id', Auth::id());
        
        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'return_purchases.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->leftJoin('suppliers', 'return_purchases.supplier_id', '=', 'suppliers.id')
                ->join('warehouses', 'return_purchases.warehouse_id', '=', 'warehouses.id')
                ->select('return_purchases.id', 'return_purchases.reference_no', 'return_purchases.created_at', 'return_purchases.supplier_id', 'suppliers.name as supplier_name', 'suppliers.phone_number as supplier_number', 'warehouses.name as warehouse_name', 'purchase_product_return.qty', 'purchase_product_return.purchase_unit_id', 'purchase_product_return.total')
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $return_purchases = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('return_purchases.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));

            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $return_purchases =  $q->orwhere([
                                        ['return_purchases.reference_no', 'LIKE', "%{$search}%"],
                                        ['return_purchases.user_id', Auth::id()]
                                    ])
                                    ->get();
                $totalFiltered = $q->orwhere([
                                    ['return_purchases.reference_no', 'LIKE', "%{$search}%"],
                                    ['return_purchases.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $return_purchases =  $q->orwhere('return_purchases.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('return_purchases.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($return_purchases))
        {
            foreach ($return_purchases as $key => $return_purchase)
            {
                $nestedData['id'] = $return_purchase->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($return_purchase->created_at));
                $nestedData['reference_no'] = $return_purchase->reference_no;
                $nestedData['warehouse'] = $return_purchase->warehouse_name;
                if($return_purchase->supplier_id)
                    $nestedData['supplier'] = $return_purchase->supplier_name.' ['.($return_purchase->supplier_number).']';
                else
                    $nestedData['supplier'] = 'N/A';
                $nestedData['qty'] = number_format($return_purchase->qty, config('decimal'));
                if($return_purchase->purchase_unit_id) {
                    $unit_data = DB::table('units')->select('unit_code')->find($return_purchase->purchase_unit_id);
                    $nestedData['qty'] .= ' '.$unit_data->unit_code;
                }
                $nestedData['unit_cost'] = number_format(($return_purchase->total / $return_purchase->qty), config('decimal'));
                $nestedData['sub_total'] = number_format($return_purchase->total, config('decimal'));
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),  
            "recordsTotal"    => intval($totalData),  
            "recordsFiltered" => intval($totalFiltered), 
            "data"            => $data   
        );
        echo json_encode($json_data);
    }

    public function variantData($id)
    {
        if(Auth::user()->role_id > 2) {
            return ProductVariant::join('variants', 'product_variants.variant_id', '=', 'variants.id')
                ->join('product_warehouse', function($join) {
                    $join->on('product_variants.product_id', '=', 'product_warehouse.product_id');
                    $join->on('product_variants.variant_id', '=', 'product_warehouse.variant_id');
                })
                ->select('variants.name', 'product_variants.item_code', 'product_variants.additional_cost', 'product_variants.additional_price', 'product_warehouse.qty')
                ->where([
                    ['product_warehouse.product_id', $id],
                    ['product_warehouse.warehouse_id', Auth::user()->warehouse_id]
                ])
                ->orderBy('product_variants.position')
                ->get();
        }
        else {
            return ProductVariant::join('variants', 'product_variants.variant_id', '=', 'variants.id')
                ->select('variants.name', 'product_variants.item_code', 'product_variants.additional_cost', 'product_variants.additional_price', 'product_variants.qty')
                ->orderBy('product_variants.position')
                ->where('product_id', $id)
                ->get();
        }
    }

    public function edit($id)
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('products-edit')) {
            $lims_product_list_without_variant = $this->productWithoutVariant();
            $lims_product_list_with_variant = $this->productWithVariant();
            $lims_brand_list = Brand::where('is_active', true)->get();
            $lims_category_list = Category::where('is_active', true)->get();
            $lims_unit_list = Unit::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_product_data = Product::where('id', $id)->first();
            if($lims_product_data->variant_option) {
                $lims_product_data->variant_option = json_decode($lims_product_data->variant_option);
                $lims_product_data->variant_value = json_decode($lims_product_data->variant_value);
            }
            $lims_product_variant_data = $lims_product_data->variant()->orderBy('position')->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $noOfVariantValue = 0;

            return view('backend.product.edit',compact('lims_product_list_without_variant', 'lims_product_list_with_variant', 'lims_brand_list', 'lims_category_list', 'lims_unit_list', 'lims_tax_list', 'lims_product_data', 'lims_product_variant_data', 'lims_warehouse_list', 'noOfVariantValue'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function updateProduct(Request $request)
    {
        if(!env('USER_VERIFIED')) {
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }
        else {
            $this->validate($request, [
                'name' => [
                    'max:255',
                    Rule::unique('products')->ignore($request->input('id'))->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],

                'code' => [
                    'max:255',
                    Rule::unique('products')->ignore($request->input('id'))->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ]
            ]);
            
            $lims_product_data = Product::findOrFail($request->input('id'));
            $data = $request->except('image', 'file', 'prev_img');
            $data['name'] = htmlspecialchars(trim($data['name']));

            if($data['type'] == 'combo') {
                $data['product_list'] = implode(",", $data['product_id']);
                $data['variant_list'] = implode(",", $data['variant_id']);
                $data['qty_list'] = implode(",", $data['product_qty']);
                $data['price_list'] = implode(",", $data['unit_price']);
                $data['cost'] = $data['unit_id'] = $data['purchase_unit_id'] = $data['sale_unit_id'] = 0;
            }
            elseif($data['type'] == 'digital' || $data['type'] == 'service')
                $data['cost'] = $data['unit_id'] = $data['purchase_unit_id'] = $data['sale_unit_id'] = 0;

            if(!isset($data['featured']))
                $data['featured'] = 0;

            if(!isset($data['is_embeded']))
                $data['is_embeded'] = 0;

            if(!isset($data['promotion']))
                $data['promotion'] = null;

            if(!isset($data['is_batch']))
                $data['is_batch'] = null;

            if(!isset($data['is_imei']))
                $data['is_imei'] = null;

            if(!isset($data['is_sync_disable']) && \Schema::hasColumn('products', 'is_sync_disable'))
                $data['is_sync_disable'] = null;

            $data['product_details'] = str_replace('"', '@', $data['product_details']);
            $data['product_details'] = $data['product_details'];
            if($data['starting_date'])
                $data['starting_date'] = date('Y-m-d', strtotime($data['starting_date']));
            if($data['last_date'])
                $data['last_date'] = date('Y-m-d', strtotime($data['last_date']));

            $previous_images = [];
            //dealing with previous images
            if($request->prev_img) {
                foreach ($request->prev_img as $key => $prev_img) {
                    if(!in_array($prev_img, $previous_images))
                        $previous_images[] = $prev_img;
                }
                $lims_product_data->image = implode(",", $previous_images);
                $lims_product_data->save();
            }
            else {
                $lims_product_data->image = null;
                $lims_product_data->save();
            }

            //dealing with new images
            if($request->image) {
                $images = $request->image;
                $image_names = [];
                $length = count(explode(",", $lims_product_data->image));
                foreach ($images as $key => $image) {
                    $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                    /*$image = Image::make($image)->resize(512, 512);*/
                    $imageName = date("Ymdhis") . ($length + $key+1) . '.' . $ext;
                    $image->move('public/images/product', $imageName);
                    $image_names[] = $imageName;
                }
                if($lims_product_data->image)
                    $data['image'] = $lims_product_data->image. ',' . implode(",", $image_names);
                else
                    $data['image'] = implode(",", $image_names);
            }
            else
                $data['image'] = $lims_product_data->image;

            $file = $request->file;
            if ($file) {
                $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                $fileName = strtotime(date('Y-m-d H:i:s'));
                $fileName = $fileName . '.' . $ext;
                $file->move('public/product/files', $fileName);
                $data['file'] = $fileName;
            }

            $old_product_variant_ids = ProductVariant::where('product_id', $request->input('id'))->pluck('id')->toArray();
            $new_product_variant_ids = [];
            //dealing with product variant
            if(isset($data['is_variant'])) {
                if(isset($data['variant_option']) && isset($data['variant_value'])) {
                    $data['variant_option'] = json_encode($data['variant_option']);
                    $data['variant_value'] = json_encode($data['variant_value']);
                }
                foreach ($data['variant_name'] as $key => $variant_name) {
                    $lims_variant_data = Variant::firstOrCreate(['name' => $data['variant_name'][$key]]);
                    $lims_product_variant_data = ProductVariant::where([
                                                    ['product_id', $lims_product_data->id],
                                                    ['variant_id', $lims_variant_data->id]
                                                ])->first();
                    if($lims_product_variant_data) {
                        $lims_product_variant_data->update([
                            'position' => $key+1,
                            'item_code' => $data['item_code'][$key],
                            'additional_cost' => $data['additional_cost'][$key],
                            'additional_price' => $data['additional_price'][$key]
                        ]);
                    }
                    else {
                        $lims_product_variant_data = new ProductVariant();
                        $lims_product_variant_data->product_id = $lims_product_data->id;
                        $lims_product_variant_data->variant_id = $lims_variant_data->id;
                        $lims_product_variant_data->position = $key + 1;
                        $lims_product_variant_data->item_code = $data['item_code'][$key];
                        $lims_product_variant_data->additional_cost = $data['additional_cost'][$key];
                        $lims_product_variant_data->additional_price = $data['additional_price'][$key];
                        $lims_product_variant_data->qty = 0;
                        $lims_product_variant_data->save();
                    }
                    $new_product_variant_ids[] = $lims_product_variant_data->id;
                }
            }
            else {
                $data['is_variant'] = null;
                $data['variant_option'] = null;
                $data['variant_value'] = null;
            }
            //deleting old product variant if not exist
            foreach ($old_product_variant_ids as $key => $product_variant_id) {
                if (!in_array($product_variant_id, $new_product_variant_ids))
                    ProductVariant::find($product_variant_id)->delete();
            }
            if(isset($data['is_diffPrice'])) {
                foreach ($data['diff_price'] as $key => $diff_price) {
                    if($diff_price) {
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($lims_product_data->id, $data['warehouse_id'][$key])->first();
                        if($lims_product_warehouse_data) {
                            $lims_product_warehouse_data->price = $diff_price;
                            $lims_product_warehouse_data->save();
                        }
                        else {
                            Product_Warehouse::create([
                                "product_id" => $lims_product_data->id,
                                "warehouse_id" => $data["warehouse_id"][$key],
                                "qty" => 0,
                                "price" => $diff_price
                            ]);
                        }
                    }
                }
            }
            else {
                $data['is_diffPrice'] = false;
                foreach ($data['warehouse_id'] as $key => $warehouse_id) {
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($lims_product_data->id, $warehouse_id)->first();
                    if($lims_product_warehouse_data) {
                        $lims_product_warehouse_data->price = null;
                        $lims_product_warehouse_data->save();
                    }
                }
            }
            $lims_product_data->update($data);
            $this->cacheForget('product_list');
            $this->cacheForget('product_list_with_variant');
            \Session::flash('edit_message', 'Product updated successfully');
        }
    }

    public function generateCode()
    {
        $id = Keygen::numeric(8)->generate();
        return $id;
    }

    public function search(Request $request)
    {
        $product_code = explode(" ", $request['data']);
        $lims_product_data = Product::where('code', $product_code[0])->first();

        $product[] = $lims_product_data->name;
        $product[] = $lims_product_data->code;
        $product[] = $lims_product_data->qty;
        $product[] = $lims_product_data->price;
        $product[] = $lims_product_data->id;
        return $product;
    }

    public function saleUnit($id)
    {
        $unit = Unit::where("base_unit", $id)->orWhere('id', $id)->pluck('unit_name','id');
        return json_encode($unit);
    }

    public function getData($id, $variant_id)
    {
        if($variant_id) {
            $data = Product::join('product_variants', 'products.id', 'product_variants.product_id')
                ->select('products.name', 'product_variants.item_code')
                ->where([
                    ['products.id', $id],
                    ['product_variants.variant_id', $variant_id]
                ])->first();
            $data->code = $data->item_code;
        }
        else
            $data = Product::select('name', 'code')->find($id);
        return $data;
    }

    public function productWarehouseData($id)
    {
        $warehouse = [];
        $qty = [];
        $batch = [];
        $expired_date = [];
        $imei_number = [];
        $warehouse_name = [];
        $variant_name = [];
        $variant_qty = [];
        $product_warehouse = [];
        $product_variant_warehouse = [];
        $lims_product_data = Product::select('id', 'is_variant')->find($id);
        if($lims_product_data->is_variant) {
            $lims_product_variant_warehouse_data = Product_Warehouse::where('product_id', $lims_product_data->id)->orderBy('warehouse_id')->get();
            $lims_product_warehouse_data = Product_Warehouse::select('warehouse_id', DB::raw('sum(qty) as qty'))->where('product_id', $id)->groupBy('warehouse_id')->get();
            foreach ($lims_product_variant_warehouse_data as $key => $product_variant_warehouse_data) {
                $lims_warehouse_data = Warehouse::find($product_variant_warehouse_data->warehouse_id);
                $lims_variant_data = Variant::find($product_variant_warehouse_data->variant_id);
                $warehouse_name[] = $lims_warehouse_data->name;
                $variant_name[] = $lims_variant_data->name;
                $variant_qty[] = $product_variant_warehouse_data->qty;
            }
        }
        else {
            $lims_product_warehouse_data = Product_Warehouse::where('product_id', $id)->orderBy('warehouse_id', 'asc')->get();
        }
        foreach ($lims_product_warehouse_data as $key => $product_warehouse_data) {
            $lims_warehouse_data = Warehouse::find($product_warehouse_data->warehouse_id);
            if($product_warehouse_data->product_batch_id) {
                $product_batch_data = ProductBatch::select('batch_no', 'expired_date')->find($product_warehouse_data->product_batch_id);
                $batch_no = $product_batch_data->batch_no;
                $expiredDate = date(config('date_format'), strtotime($product_batch_data->expired_date));
            }
            else {
                $batch_no = 'N/A';
                $expiredDate = 'N/A';
            }
            $warehouse[] = $lims_warehouse_data->name;
            $batch[] = $batch_no;
            $expired_date[] = $expiredDate;
            $qty[] = $product_warehouse_data->qty;
            if($product_warehouse_data->imei_number)
                $imei_number[] = $product_warehouse_data->imei_number;
            else
                $imei_number[] = 'N/A';
        }

        $product_warehouse = [$warehouse, $qty, $batch, $expired_date, $imei_number];
        $product_variant_warehouse = [$warehouse_name, $variant_name, $variant_qty];
        return ['product_warehouse' => $product_warehouse, 'product_variant_warehouse' => $product_variant_warehouse];
    }

    public function printBarcode(Request $request)
    {
        if($request->input('data'))
            $preLoadedproduct = $this->limsProductSearch($request);
        else
            $preLoadedproduct = null;
        $lims_product_list_without_variant = $this->productWithoutVariant();
        $lims_product_list_with_variant = $this->productWithVariant();
        
        return view('backend.product.print_barcode', compact('lims_product_list_without_variant', 'lims_product_list_with_variant', 'preLoadedproduct'));
    }

    public function productWithoutVariant()
    {
        return Product::ActiveStandard()->select('id', 'name', 'code')
                ->whereNull('is_variant')->get();
    }

    public function productWithVariant()
    {
        return Product::join('product_variants', 'products.id', 'product_variants.product_id')
                ->ActiveStandard()
                ->whereNotNull('is_variant')
                ->select('products.id', 'products.name', 'product_variants.item_code')
                ->orderBy('position')->get();
    }

    public function limsProductSearch(Request $request)
    {
        $product_code = explode("(", $request['data']);
        $product_code[0] = rtrim($product_code[0], " ");
        $lims_product_data = Product::where([
            ['code', $product_code[0] ],
            ['is_active', true]
        ])->first();
        if(!$lims_product_data) {
            $lims_product_data = Product::join('product_variants', 'products.id', 'product_variants.product_id')
                ->select('products.*', 'product_variants.item_code', 'product_variants.variant_id', 'product_variants.additional_price')
                ->where('product_variants.item_code', $product_code[0])
                ->first();

            $variant_id = $lims_product_data->variant_id;
            $additional_price = $lims_product_data->additional_price;
        }
        else {
            $variant_id = '';
            $additional_price = 0;
        }
        $product[] = $lims_product_data->name;
        if($lims_product_data->is_variant)
            $product[] = $lims_product_data->item_code;
        else
            $product[] = $lims_product_data->code;
        
        $product[] = $lims_product_data->price + $additional_price;
        $product[] = DNS1D::getBarcodePNG($lims_product_data->code, $lims_product_data->barcode_symbology);
        $product[] = $lims_product_data->promotion_price;
        $product[] = config('currency');
        $product[] = config('currency_position');
        $product[] = $lims_product_data->qty;
        $product[] = $lims_product_data->id;
        $product[] = $variant_id;
        return $product;
    }

    /*public function getBarcode()
    {
        return DNS1D::getBarcodePNG('72782608', 'C128');
    }*/

    public function checkBatchAvailability($product_id, $batch_no, $warehouse_id)
    {
        $product_batch_data = ProductBatch::where([
            ['product_id', $product_id],
            ['batch_no', $batch_no]
        ])->first();
        if($product_batch_data) {
            $product_warehouse_data = Product_Warehouse::select('qty')
            ->where([
                ['product_batch_id', $product_batch_data->id],
                ['warehouse_id', $warehouse_id]
            ])->first();
            if($product_warehouse_data) {
                $data['qty'] = $product_warehouse_data->qty;
                $data['product_batch_id'] = $product_batch_data->id;
                $data['expired_date'] = date(config('date_format'), strtotime($product_batch_data->expired_date));
                $data['message'] = 'ok';
            }
            else {
                $data['qty'] = 0;
                $data['message'] = 'This Batch does not exist in the selected warehouse!';
            }            
        }
        else {
            $data['message'] = 'Wrong Batch Number!';
        }
        return $data;
    }

    public function importProduct(Request $request)
    {   
        //get file
        $upload=$request->file('file');
        $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
        if($ext != 'csv')
            return redirect()->back()->with('message', 'Please upload a CSV file');

        $filePath=$upload->getRealPath();
        //open and read
        $file=fopen($filePath, 'r');
        $header= fgetcsv($file);
        $escapedHeader=[];
        //validate
        foreach ($header as $key => $value) {
            $lheader=strtolower($value);
            $escapedItem=preg_replace('/[^a-z]/', '', $lheader);
            array_push($escapedHeader, $escapedItem);
        }
        //looping through other columns
        while($columns=fgetcsv($file))
        {
            foreach ($columns as $key => $value) {
                $value=preg_replace('/\D/','',$value);
            }
           $data= array_combine($escapedHeader, $columns);
           
           if($data['brand'] != 'N/A' && $data['brand'] != ''){
                $lims_brand_data = Brand::firstOrCreate(['title' => $data['brand'], 'is_active' => true]);
                $brand_id = $lims_brand_data->id;
           }
            else
                $brand_id = null;

           $lims_category_data = Category::firstOrCreate(['name' => $data['category'], 'is_active' => true]);

           $lims_unit_data = Unit::where('unit_code', $data['unitcode'])->first();
           if(!$lims_unit_data)
                return redirect()->back()->with('not_permitted', 'Unit code does not exist in the database.');

           $product = Product::firstOrNew([ 'name'=>$data['name'], 'is_active'=>true ]);
            if($data['image'])
                $product->image = $data['image'];
            else
                $product->image = 'zummXD2dvAtI.png';

           $product->name = htmlspecialchars(trim($data['name']));
           $product->code = $data['code'];
           $product->type = strtolower($data['type']);
           $product->barcode_symbology = 'C128';
           $product->brand_id = $brand_id;
           $product->category_id = $lims_category_data->id;
           $product->unit_id = $lims_unit_data->id;
           $product->purchase_unit_id = $lims_unit_data->id;
           $product->sale_unit_id = $lims_unit_data->id;
           $product->cost = str_replace(",","",$data['cost']);
           $product->price = str_replace(",","",$data['price']);
           $product->tax_method = 1;
           $product->qty = 0;
           $product->product_details = $data['productdetails'];
           $product->is_active = true;
           $product->save();
           //dealing with variants
           if($data['variantvalue'] && $data['variantname']) {
                $variantInfo = explode(",", $data['variantvalue']);
                foreach ($variantInfo as $key => $info) {
                    $variant_option[] = strtok($info, "[");
                    $variant_value[] = str_replace("/", ",", substr($info, strpos($info, "[")+1, (strpos($info, "]") - strpos($info, "[") - 1)));
                }
                $product->variant_option = json_encode($variant_option);
                $product->variant_value = json_encode($variant_value);
                $product->is_variant = true;
                $product->save();

                $variant_names = explode(",", $data['variantname']);
                $item_codes = explode(",", $data['itemcode']);
                $additional_costs = explode(",", $data['additionalcost']);
                $additional_prices = explode(",", $data['additionalprice']);
                foreach ($variant_names as $key => $variant_name) {
                    $variant = Variant::firstOrCreate(['name' => $variant_name]);
                    if($data['itemcode'])
                        $item_code = $item_codes[$key];
                    else
                        $item_code = $variant_name . '-' . $data['code'];

                    if($data['additionalcost'])
                        $additional_cost = $additional_costs[$key];
                    else
                        $additional_cost = 0;
                    
                    if($data['additionalprice'])
                        $additional_price = $additional_prices[$key];
                    else
                        $additional_price = 0;

                    ProductVariant::create([
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'position' => $key + 1,
                        'item_code' => $item_code,
                        'additional_cost' => $additional_cost,
                        'additional_price' => $additional_price,
                        'qty' => 0
                    ]);
                }
           }
         }
         $this->cacheForget('product_list');
         $this->cacheForget('product_list_with_variant');
         return redirect('products')->with('import_message', 'Product imported successfully');
    }

    public function deleteBySelection(Request $request)
    {
        $product_id = $request['productIdArray'];
        foreach ($product_id as $id) {
            $lims_product_data = Product::findOrFail($id);
            $lims_product_data->is_active = false;
            $lims_product_data->save();
        }
        $this->cacheForget('product_list');
        $this->cacheForget('product_list_with_variant');
        return 'Product deleted successfully!';
    }

    public function destroy($id)
    {
        if(!env('USER_VERIFIED')) {
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }
        else {
            $lims_product_data = Product::findOrFail($id);
            $lims_product_data->is_active = false;
            if($lims_product_data->image != 'zummXD2dvAtI.png') {
                $images = explode(",", $lims_product_data->image);
                foreach ($images as $key => $image) {
                    if(file_exists('public/images/product/'.$image))
                        unlink('public/images/product/'.$image);
                }
            }
            $lims_product_data->save();
            $this->cacheForget('product_list');
            $this->cacheForget('product_list_with_variant');
            return redirect('products')->with('message', 'Product deleted successfully');
        }
    }
}
