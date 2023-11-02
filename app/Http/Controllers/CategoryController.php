<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Category;
use App\Product;
use DB;
use Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;
use App\Traits\TenantInfo;
use App\Traits\CacheForget;

class CategoryController extends Controller
{
    use CacheForget;
    use TenantInfo;

    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('category')) {
            return view('backend.category.create');
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function categoryData(Request $request)
    {
        $columns = array( 
            0 =>'id',
            2 =>'name',
            3=> 'parent_id',
            4=> 'is_active',
        );
        
        $totalData = Category::where('is_active', true)->count();
        $totalFiltered = $totalData; 

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        if(empty($request->input('search.value')))
            $categories = Category::offset($start)
                        ->where('is_active', true)
                        ->limit($limit)
                        ->orderBy($order,$dir)
                        ->get();
        else
        {
            $search = $request->input('search.value'); 
            $categories =  Category::where([
                            ['name', 'LIKE', "%{$search}%"],
                            ['is_active', true]
                        ])->offset($start)
                        ->limit($limit)
                        ->orderBy($order,$dir)->get();

            $totalFiltered = Category::where([
                            ['name','LIKE',"%{$search}%"],
                            ['is_active', true]
                        ])->count();
        }
        $data = array();
        if(!empty($categories))
        {
            foreach ($categories as $key=>$category)
            {
                $nestedData['id'] = $category->id;
                $nestedData['key'] = $key;

                if($category->image)
                    $nestedData['image'] = '<img src="'.url('images/category', $category->image).'" height="80" width="80">';
                else
                    $nestedData['image'] = '<img src="'.url('images/zummXD2dvAtI.png').'" height="80" width="80">';

                $nestedData['name'] = $category->name;

                if($category->parent_id)
                    $nestedData['parent_id'] = Category::find($category->parent_id)->name;
                else
                    $nestedData['parent_id'] = "N/A";

                $nestedData['number_of_product'] = $category->product()->where('is_active', true)->count();
                $nestedData['stock_qty'] = $category->product()->where('is_active', true)->sum('qty');
                $total_price = $category->product()->where('is_active', true)->sum(DB::raw('price * qty'));
                $total_cost = $category->product()->where('is_active', true)->sum(DB::raw('cost * qty'));
                
                if(config('currency_position') == 'prefix')
                    $nestedData['stock_worth'] = config('currency').' '.$total_price.' / '.config('currency').' '.$total_cost;
                else
                    $nestedData['stock_worth'] = $total_price.' '.config('currency').' / '.$total_cost.' '.config('currency');

                $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.trans("file.action").'
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" data-id="'.$category->id.'" class="open-EditCategoryDialog btn btn-link" data-toggle="modal" data-target="#editModal" ><i class="dripicons-document-edit"></i> '.trans("file.edit").'</button>
                                </li>
                                <li class="divider"></li>'.
                                \Form::open(["route" => ["category.destroy", $category->id], "method" => "DELETE"] ).'
                                <li>
                                  <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> '.trans("file.delete").'</button> 
                                </li>'.\Form::close().'
                            </ul>
                        </div>';
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

    public function store(Request $request)
    {
        $request->name = preg_replace('/\s+/', ' ', $request->name);
        $this->validate($request, [
            'name' => [
                'max:255',
                    Rule::unique('categories')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'image' => 'image|mimes:jpg,jpeg,png,gif',
        ]);
        $image = $request->image;
        if ($image) {
            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis");
            if(!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
                $image->move('public/images/category', $imageName);
            }
            else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                $image->move('public/images/category', $imageName);
            }
            $lims_category_data['image'] = $imageName;
        }
        $lims_category_data['name'] = $request->name;
        $lims_category_data['parent_id'] = $request->parent_id;
        $lims_category_data['is_active'] = true;
        if(isset($input['is_sync_disable']) && \Schema::hasColumn('categories', 'is_sync_disable')) {
            $lims_category_data['is_sync_disable'] = $request->is_sync_disable;
        }
        Category::create($lims_category_data);
        $this->cacheForget('category_list');
        return redirect('category')->with('message', 'Category inserted successfully');
    }

    public function edit($id)
    {
        $lims_category_data = Category::findOrFail($id);
        $lims_parent_data = Category::where('id', $lims_category_data['parent_id'])->first();
        if($lims_parent_data)
            $lims_category_data['parent'] = $lims_parent_data['name'];
        return $lims_category_data;
    }

    public function update(Request $request)
    {
        $this->validate($request,[
            'name' => [
                'max:255',
                Rule::unique('categories')->ignore($request->category_id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'image' => 'image|mimes:jpg,jpeg,png,gif',
        ]);

        $input = $request->except('image');
        $image = $request->image;
        if ($image) {
            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis");
            if(!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
                $image->move('public/images/category', $imageName);
            }
            else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                $image->move('public/images/category', $imageName);
            }
            
            $input['image'] = $imageName;
        }

        if(!isset($input['is_sync_disable']) && \Schema::hasColumn('categories', 'is_sync_disable'))
            $input['is_sync_disable'] = null;

        $lims_category_data = Category::findOrFail($request->category_id);
        $lims_category_data->update($input);
        $this->cacheForget('category_list');
        return redirect('category')->with('message', 'Category updated successfully');
    }

    public function import(Request $request)
    {
        //get file
        $upload=$request->file('file');
        $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
        if($ext != 'csv')
            return redirect()->back()->with('not_permitted', 'Please upload a CSV file');
        $filename =  $upload->getClientOriginalName();
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
        //looping through othe columns
        while($columns=fgetcsv($file))
        {
            if($columns[0]=="")
                continue;
            foreach ($columns as $key => $value) {
                $value=preg_replace('/\D/','',$value);
            }
            $data= array_combine($escapedHeader, $columns);
            $category = Category::firstOrNew(['name' => $data['name'], 'is_active' => true ]);
            if($data['parentcategory']){
                $parent_category = Category::firstOrNew(['name' => $data['parentcategory'], 'is_active' => true ]);
                $parent_id = $parent_category->id;
            }
            else
                $parent_id = null;

            $category->parent_id = $parent_id;
            $category->is_active = true;
            $category->save();
        }
        $this->cacheForget('category_list');
        return redirect('category')->with('message', 'Category imported successfully');
    }

    public function deleteBySelection(Request $request)
    {
        $category_id = $request['categoryIdArray'];
        foreach ($category_id as $id) {
            $lims_product_data = Product::where('category_id', $id)->get();
            foreach ($lims_product_data as $product_data) {
                $product_data->is_active = false;
                $product_data->save();
            }
            $lims_category_data = Category::findOrFail($id);
            if($lims_category_data->image && !config('database.connections.saleprosaas_landlord') && file_exists('public/images/category/'.$lims_category_data->image)) {
                unlink('public/images/category/'.$lims_category_data->image);
            }
            elseif($lims_category_data->image && file_exists('images/category/'.$lims_category_data->image)) {
                unlink('images/category/'.$lims_category_data->image);
            }
            $lims_category_data->is_active = false;
            $lims_category_data->save();
        }
        $this->cacheForget('category_list');
        return 'Category deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_category_data = Category::findOrFail($id);
        $lims_category_data->is_active = false;
        $lims_product_data = Product::where('category_id', $id)->get();
        foreach ($lims_product_data as $product_data) {
            $product_data->is_active = false;
            $product_data->save();
        }
        if($lims_category_data->image && !config('database.connections.saleprosaas_landlord') && file_exists('public/images/category/'.$lims_category_data->image)) {
            unlink('public/images/category/'.$lims_category_data->image);
        }
        elseif($lims_category_data->image && file_exists('images/category/'.$lims_category_data->image)) {
            unlink('images/category/'.$lims_category_data->image);
        }
        $lims_category_data->save();
        $this->cacheForget('category_list');
        return redirect('category')->with('not_permitted', 'Category deleted successfully');
    }
}
