@extends('backend.layout.main')

@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{trans('file.Update Product')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        <form id="product-form">
                            <input type="hidden" name="id" value="{{$lims_product_data->id}}" />
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Type')}} *</strong> </label>
                                        <div class="input-group">
                                            <select name="type" required class="form-control selectpicker" id="type">
                                                <option value="standard">Standard</option>
                                                <option value="combo">Combo</option>
                                                <option value="digital">Digital</option>
                                                <option value="service">Service</option>
                                            </select>
                                            <input type="hidden" name="type_hidden" value="{{$lims_product_data->type}}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Name')}} *</strong> </label>
                                        <input type="text" name="name" value="{{$lims_product_data->name}}" required class="form-control">
                                        <span class="validation-msg" id="name-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Code')}} *</strong> </label>
                                        <div class="input-group">
                                            <input type="text" name="code" id="code" value="{{$lims_product_data->code}}" class="form-control" required>
                                            <div class="input-group-append">
                                                <button id="genbutton" type="button" class="btn btn-sm btn-default" title="{{trans('file.Generate')}}"><i class="fa fa-refresh"></i></button>
                                            </div>
                                        </div>
                                        <span class="validation-msg" id="code-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Barcode Symbology')}} *</strong> </label>
                                        <div class="input-group">
                                            <input type="hidden" name="barcode_symbology_hidden" value="{{$lims_product_data->barcode_symbology}}">
                                            <select name="barcode_symbology" required class="form-control selectpicker">
                                                <option value="UPCE">UPC-E</option>
                                                <option value="C128">Code 128</option>
                                                <option value="C39">Code 39</option>
                                                <option value="UPCA">UPC-A</option>
                                                <option value="EAN8">EAN-8</option>
                                                <option value="EAN13">EAN-13</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div id="digital" class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Attach File')}}</strong> </label>
                                        <div class="input-group">
                                            <input id="file" type="file" name="file" class="form-control">
                                        </div>
                                        <span class="validation-msg"></span>
                                    </div>
                                </div>
                                <div id="combo" class="col-md-9 mb-1">
                                    <label>{{trans('file.add_product')}}</label>
                                    <div class="search-box input-group mb-3">
                                        <button class="btn btn-secondary"><i class="fa fa-barcode"></i></button>
                                        <input type="text" name="product_code_name" id="lims_productcodeSearch" placeholder="Please type product code and select..." class="form-control" />
                                    </div>
                                    <label>{{trans('file.Combo Products')}}</label>
                                    <div class="table-responsive">
                                        <table id="myTable" class="table table-hover order-list">
                                            <thead>
                                                <tr>
                                                    <th>{{trans('file.product')}}</th>
                                                    <th>{{trans('file.Quantity')}}</th>
                                                    <th>{{trans('file.Unit Price')}}</th>
                                                    <th><i class="dripicons-trash"></i></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if($lims_product_data->type == 'combo')
                                                <?php
                                                    $product_list = explode(",", $lims_product_data->product_list);
                                                    $qty_list = explode(",", $lims_product_data->qty_list);
                                                    $variant_list = explode(",", $lims_product_data->variant_list);
                                                    $price_list = explode(",", $lims_product_data->price_list);
                                                ?>
                                                @foreach($product_list as $key=>$id)
                                                <tr>
                                                    <?php
                                                        $product = App\Product::find($id);
                                                        if($lims_product_data->variant_list && $variant_list[$key]) {
                                                            $product_variant_data = App\ProductVariant::select('item_code')->FindExactProduct($id, $variant_list[$key])->first();
                                                            $product->code = $product_variant_data->item_code;
                                                        }
                                                        else
                                                            $variant_list[$key] = "";
                                                    ?>
                                                    <td>{{$product->name}} [{{$product->code}}]</td>
                                                    <td><input type="number" class="form-control qty" name="product_qty[]" value="{{$qty_list[$key]}}" step="any"></td>
                                                    <td><input type="number" class="form-control unit_price" name="unit_price[]" value="{{$price_list[$key]}}" step="any"/></td>
                                                    <td><button type="button" class="ibtnDel btn btn-danger btn-sm">X</button></td>
                                                    <input type="hidden" class="product-id" name="product_id[]" value="{{$id}}"/>
                                                    <input type="hidden" class="variant-id" name="variant_id[]" value="{{$variant_list[$key]}}"/>
                                                </tr>
                                                @endforeach
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Brand')}}</strong> </label>
                                        <div class="input-group">
                                            <input type="hidden" name="brand" value="{{ $lims_product_data->brand_id}}">
                                          <select name="brand_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Brand...">
                                            @foreach($lims_brand_list as $brand)
                                                <option value="{{$brand->id}}">{{$brand->title}}</option>
                                            @endforeach
                                          </select>
                                      </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <input type="hidden" name="category" value="{{$lims_product_data->category_id}}">
                                        <label>{{trans('file.category')}} *</strong> </label>
                                        <div class="input-group">
                                          <select name="category_id" required class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Category...">
                                            @foreach($lims_category_list as $category)
                                                <option value="{{$category->id}}">{{$category->name}}</option>
                                            @endforeach
                                          </select>
                                      </div>
                                    </div>
                                </div>
                                <div id="unit" class="col-md-12">
                                    <div class="row ">
                                        <div class="col-md-4">
                                                <label>{{trans('file.Product Unit')}} *</strong> </label>
                                                <div class="input-group">
                                                  <select required class="form-control selectpicker" data-live-search="true" data-live-search-style="begins" title="Select unit..." name="unit_id">
                                                    @foreach($lims_unit_list as $unit)
                                                        @if($unit->base_unit==null)
                                                            <option value="{{$unit->id}}">{{$unit->unit_name}}</option>
                                                        @endif
                                                    @endforeach
                                                  </select>
                                                  <input type="hidden" name="unit" value="{{ $lims_product_data->unit_id}}">
                                              </div>
                                        </div>
                                        <div class="col-md-4">
                                                <label>{{trans('file.Sale Unit')}}</strong> </label>
                                                <div class="input-group">
                                                  <select class="form-control selectpicker" name="sale_unit_id" id="sale-unit">
                                                  </select>
                                                  <input type="hidden" name="sale_unit" value="{{ $lims_product_data->sale_unit_id}}">
                                              </div>
                                        </div>
                                        <div class="col-md-4 mt-2">
                                                <div class="form-group">
                                                    <label>{{trans('file.Purchase Unit')}}</strong> </label>
                                                    <div class="input-group">
                                                      <select class="form-control selectpicker" name="purchase_unit_id">
                                                      </select>
                                                      <input type="hidden" name="purchase_unit" value="{{ $lims_product_data->purchase_unit_id}}">
                                                  </div>
                                                </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="cost" class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Cost')}} *</strong> </label>
                                        <input type="number" name="cost" value="{{$lims_product_data->cost}}" required class="form-control" step="any">
                                        <span class="validation-msg"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Price')}} *</strong> </label>
                                        <input type="number" name="price" value="{{$lims_product_data->price}}" required class="form-control" step="any">
                                        <span class="validation-msg"></span>
                                    </div>
                                    <div class="form-group">
                                        <input type="hidden" name="qty" value="{{ $lims_product_data->qty }}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Daily Sale Objective')}}</strong> </label>
                                        <input type="number" name="daily_sale_objective" class="form-control" step="any" value="{{$lims_product_data->daily_sale_objective}}">
                                    </div>
                                </div>
                                <div id="alert-qty" class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Alert Quantity')}}</strong> </label>
                                        <input type="number" name="alert_quantity" value="{{$lims_product_data->alert_quantity}}" class="form-control" step="any">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <input type="hidden" name="tax" value="{{$lims_product_data->tax_id}}">
                                        <label>{{trans('file.product')}} {{trans('file.Tax')}}</strong> </label>
                                        <select name="tax_id" class="form-control selectpicker">
                                            <option value="">No Tax</option>
                                            @foreach($lims_tax_list as $tax)
                                                <option value="{{$tax->id}}">{{$tax->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <input type="hidden" name="tax_method_id" value="{{$lims_product_data->tax_method}}">
                                        <label>{{trans('file.Tax Method')}}</strong> </label>
                                        <select name="tax_method" class="form-control selectpicker">
                                            <option value="1">{{trans('file.Exclusive')}}</option>
                                            <option value="2">{{trans('file.Inclusive')}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mt-3">
                                        @if($lims_product_data->featured)
                                            <input type="checkbox" name="featured" value="1" checked>
                                        @else
                                            <input type="checkbox" name="featured" value="1">
                                        @endif
                                        <label>{{trans('file.Featured')}}</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mt-3">
                                        @if($lims_product_data->is_embeded)
                                            <input type="checkbox" name="is_embeded" value="1" checked>
                                        @else
                                            <input type="checkbox" name="is_embeded" value="1">
                                        @endif
                                        <label>{{trans('file.Embedded Barcode')}} <i class="dripicons-question" data-toggle="tooltip" title="{{trans('file.Check this if this product will be used in weight scale machine.')}}"></i></label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Image')}}</strong> </label> <i class="dripicons-question" data-toggle="tooltip" title="{{trans('file.You can upload multiple image. Only .jpeg, .jpg, .png, .gif file can be uploaded. First image will be base image.')}}"></i>
                                        <div id="imageUpload" class="dropzone"></div>
                                        <span class="validation-msg" id="image-error"></span>
                                    </div>
                                </div>
                                @if($lims_product_data->image)
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th><button type="button" class="btn btn-sm"><i class="fa fa-list"></i></button></th>
                                                    <th>Image</th>
                                                    <th>Remove</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $images = explode(",", $lims_product_data->image)?>
                                                @foreach($images as $key => $image)
                                                <tr>
                                                    <td><button type="button" class="btn btn-sm"><i class="fa fa-list"></i></button></i></td>
                                                    <td>
                                                        <img src="{{url('public/images/product', $image)}}" height="60" width="60">
                                                        <input type="hidden" name="prev_img[]" value="{{$image}}">
                                                    </td>
                                                    <td><button type="button" class="btn btn-sm btn-danger remove-img">X</button></td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Details')}}</label>
                                        <textarea name="product_details" class="form-control" rows="5">{{str_replace('@', '"', $lims_product_data->product_details)}}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-2" id="diffPrice-option">
                                    @if($lims_product_data->is_diffPrice)
                                        <h5><input name="is_diffPrice" type="checkbox" id="is-diffPrice" value="1" checked>&nbsp; {{trans('file.This product has different price for different warehouse')}}</h5>
                                    @else
                                        <h5><input name="is_diffPrice" type="checkbox" id="is-diffPrice" value="1">&nbsp; {{trans('file.This product has different price for different warehouse')}}</h5>
                                    @endif
                                </div>
                                <div class="col-md-6" id="diffPrice-section">
                                    <div class="table-responsive ml-2">
                                        <table id="diffPrice-table" class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>{{trans('file.Warehouse')}}</th>
                                                    <th>{{trans('file.Price')}}</th>
                                                </tr>
                                                @foreach($lims_warehouse_list as $warehouse)
                                                <tr>
                                                    <td>
                                                        <input type="hidden" name="warehouse_id[]" value="{{$warehouse->id}}">
                                                        {{$warehouse->name}}
                                                    </td>
                                                    <td>
                                                        <?php
                                                            $product_warehouse = \App\Product_Warehouse::FindProductWithoutVariant($lims_product_data->id, $warehouse->id)->first();
                                                        ?>
                                                        @if($product_warehouse)
                                                            <input type="number" name="diff_price[]" class="form-control" value="{{$product_warehouse->price}}">
                                                        @else
                                                            <input type="number" name="diff_price[]" class="form-control">
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-3" id="batch-option">
                                    @if($lims_product_data->is_batch)
                                    <h5><input name="is_batch" type="checkbox" id="is-batch" value="1" checked>&nbsp; {{trans('file.This product has batch and expired date')}}</h5>
                                    @else
                                    <h5><input name="is_batch" type="checkbox" id="is-batch" value="1">&nbsp; {{trans('file.This product has batch and expired date')}}</h5>
                                    @endif
                                </div>
                                <div class="col-md-12 mt-3" id="imei-option">
                                    @if($lims_product_data->is_imei)
                                    <h5><input name="is_imei" type="checkbox" id="is-imei" value="1" checked>&nbsp; {{trans('file.This product has IMEI or Serial numbers')}}</h5>
                                    @else
                                    <h5><input name="is_imei" type="checkbox" id="is-imei" value="1">&nbsp; {{trans('file.This product has IMEI or Serial numbers')}}</h5>
                                    @endif
                                </div>
                                <div class="col-md-12 mt-3" id="variant-option">
                                    @if($lims_product_data->is_variant)
                                    <h5 class="d-none"><input name="is_variant" type="checkbox" id="is-variant" value="1" checked>&nbsp; {{trans('file.This product has variant')}}</h5>
                                    @else
                                    <h5><input name="is_variant" type="checkbox" id="is-variant" value="1">&nbsp; {{trans('file.This product has variant')}}</h5>
                                    @endif
                                </div>
                                <div class="col-md-12" id="variant-section">
                                    @if($lims_product_data->variant_option)
                                    <div class="row" id="variant-input-section">
                                        @foreach($lims_product_data->variant_option as $key => $variant_option)
                                        <?php
                                            $noOfVariantValue += count(explode(",", $lims_product_data->variant_value[$key]));
                                        ?>
                                        <div class="col-md-4 form-group mt-2">
                                            <label>{{trans('file.Option')}} *</label>
                                            <input type="text" name="variant_option[]" class="form-control variant-field" value="{{$lims_product_data->variant_option[$key]}}">
                                        </div>
                                        <div class="col-md-6 form-group mt-2">
                                            <label>{{trans('file.Value')}} *</label>
                                            <input type="text" name="variant_value[]" class="type-variant form-control variant-field" value="{{$lims_product_data->variant_value[$key]}}">
                                        </div>
                                        @endforeach
                                    </div>
                                    @else
                                    <div class="row" id="variant-input-section">
                                        <div class="col-md-4 form-group mt-2">
                                            <label>{{trans('file.Option')}} *</label>
                                            <input type="text" name="variant_option[]" class="form-control variant-field" placeholder="Size, Color etc...">
                                        </div>
                                        <div class="col-md-6 form-group mt-2">
                                            <label>{{trans('file.Value')}} *</label>
                                            <input type="text" name="variant_value[]" class="type-variant form-control variant-field">
                                        </div>
                                    </div>
                                    @endif
                                    <div class="col-md-12 form-group">
                                        <button type="button" class="btn btn-info add-more-variant"><i class="dripicons-plus"></i> {{trans('file.Add More Variant')}}</button>
                                    </div>
                                    <div class="table-responsive ml-2">
                                        <table id="variant-table" class="table table-hover variant-list">
                                            <thead>
                                                <tr>
                                                    <th>{{trans('file.name')}}</th>
                                                    <th>{{trans('file.Item Code')}}</th>
                                                    <th>{{trans('file.Additional Cost')}}</th>
                                                    <th>{{trans('file.Additional Price')}}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($lims_product_variant_data as $key=> $variant)
                                                <tr>
                                                    <td>{{$variant->name}}
                                                        <input type="hidden" class="form-control variant-name" name="variant_name[]" value="{{$variant->name}}" />
                                                    </td>
                                                    <td><input type="text" class="form-control" name="item_code[]" value="{{$variant->pivot['item_code']}}" /></td>
                                                    <td><input type="number" class="form-control additional-cost" name="additional_cost[]" value="{{$variant->pivot['additional_cost']}}" step="any" /></td>
                                                    <td><input type="number" class="form-control additional-price" name="additional_price[]" value="{{$variant->pivot['additional_price']}}" step="any" /></td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-3">
                                    <input type="hidden" name="promotion_hidden" value="{{$lims_product_data->promotion}}">
                                    <h5><input name="promotion" type="checkbox" id="promotion" value="1">&nbsp; {{trans('file.Add Promotional Price')}}</h5>
                                </div>

                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-4" id="promotion_price">   <label>{{trans('file.Promotional Price')}}</label>
                                            <input type="number" name="promotion_price" value="{{$lims_product_data->promotion_price}}" class="form-control" step="any" />
                                        </div>
                                        <div id="start_date" class="col-md-4">
                                            <div class="form-group">
                                                <label>{{trans('file.Promotion Starts')}}</label>
                                                <input type="text" name="starting_date" value="{{$lims_product_data->starting_date}}" id="starting_date" class="form-control" />
                                            </div>
                                        </div>
                                        <div id="last_date" class="col-md-4">
                                            <div class="form-group">
                                                <label>{{trans('file.Promotion Ends')}}</label>
                                                <input type="text" name="last_date" value="{{$lims_product_data->last_date}}" id="ending_date" class="form-control" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if (\Schema::hasColumn('products', 'woocommerce_product_id'))
                                <div class="col-md-12 mt-3">
                                    <h5><input name="is_sync_disable" {{$lims_product_data->is_sync_disable==1 ? 'checked':''}} type="checkbox" id="is_sync_disable" value="1">&nbsp; {{trans('file.Disable Woocommerce Sync')}}</h5>
                                </div>
                                @endif
                                <div class="col-md-12 mt-3">
                                    <div class="form-group">
                                        <input type="button" value="{{trans('file.submit')}}" class="btn btn-primary" id="submit-btn">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script type="text/javascript">

    $("ul#product").siblings('a').attr('aria-expanded','true');
    $("ul#product").addClass("show");
    var product_id = <?php echo json_encode($lims_product_data->id) ?>;
    var is_batch = <?php echo json_encode($lims_product_data->is_batch) ?>;
    var is_variant = <?php echo json_encode($lims_product_data->is_variant) ?>;
    var redirectUrl = <?php echo json_encode(url('products')); ?>;
    var variantPlaceholder = <?php echo json_encode(trans('file.Enter variant value seperated by comma')); ?>;
    var variantIds = [];
    var combinations = [];
    var oldCombinations = [];
    var step;
    var count = 1;
    var customizedVariantCode = 1;
    var noOfVariantValue = <?php echo json_encode($noOfVariantValue); ?>;
    console.log(noOfVariantValue);
    $('[data-toggle="tooltip"]').tooltip();

    $(".remove-img").on("click", function () {
        $(this).closest("tr").remove();
    });

    $("#digital").hide();
    $("#combo").hide();
    $("select[name='type']").val($("input[name='type_hidden']").val());
    variantShowHide();
    diffPriceShowHide();
    if(is_batch)
        $("#variant-option").hide();
    if(is_variant) {
        var customizedVariantCode = 0;
        $("#batch-option").hide();
    }

    if($("input[name='type_hidden']").val() == "digital"){
        $("input[name='cost']").prop('required',false);
        $("select[name='unit_id']").prop('required',false);
        hide();
        $("#digital").show();
    }
    else if($("input[name='type_hidden']").val() == "service"){
        $("input[name='cost']").prop('required',false);
        $("select[name='unit_id']").prop('required',false);
        hide();
        $("#variant-section, #variant-option").hide();
    }
    else if($("input[name='type_hidden']").val() == "combo"){
        $("input[name='cost']").prop('required', false);
        $("input[name='price']").prop('disabled', true);
        $("select[name='unit_id']").prop('required', false);
        hide();
        $("#combo").show();
    }

    var promotion = $("input[name='promotion_hidden']").val();
    if(promotion){
        $("input[name='promotion']").prop('checked', true);
        $("#promotion_price").show(300);
        $("#start_date").show(300);
        $("#last_date").show(300);
    }
    else {
        $("#promotion_price").hide(300);
        $("#start_date").hide(300);
        $("#last_date").hide(300);
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#genbutton').on("click", function(){
      $.get('../gencode', function(data){
        $("input[name='code']").val(data);
      });
    });

    $('.selectpicker').selectpicker({
      style: 'btn-link',
    });

    $('.type-variant').on('input', function() {
        alert('dadffff');
    });

    $('.add-more-variant').on("click", function() {
        var htmlText = '<div class="col-md-4 form-group mt-2"><label>Option *</label><input type="text" name="variant_option[]" class="form-control variant-field" placeholder="Size, Color etc..."></div><div class="col-md-6 form-group mt-2"><label>Value *</label><input type="text" name="variant_value[]" class="type-variant form-control variant-field"></div>';
        $("#variant-input-section").append(htmlText);
        $('.type-variant').tagsInput();
    });

    //start variant related js
    $(function() {
        $('.type-variant').tagsInput();
    });

    (function($) {
        var delimiter = [];
        var inputSettings = [];
        var callbacks = [];

        $.fn.addTag = function(value, options) {
            if(count == noOfVariantValue)
                customizedVariantCode = 1;
            options = jQuery.extend({
                focus: false,
                callback: true
            }, options);

            this.each(function() {
                var id = $(this).attr('id');
                var tagslist = $(this).val().split(_getDelimiter(delimiter[id]));
                if (tagslist[0] === '') tagslist = [];

                value = jQuery.trim(value);

                if ((inputSettings[id].unique && $(this).tagExist(value)) || !_validateTag(value, inputSettings[id], tagslist, delimiter[id])) {
                    $('#' + id + '_tag').addClass('error');
                    return false;
                }

                $('<span>', {class: 'tag'}).append(
                    $('<span>', {class: 'tag-text'}).text(value),
                    $('<button>', {class: 'tag-remove'}).click(function() {
                        return $('#' + id).removeTag(encodeURI(value));
                    })
                ).insertBefore('#' + id + '_addTag');

                tagslist.push(value);

                $('#' + id + '_tag').val('');
                if (options.focus) {
                    $('#' + id + '_tag').focus();
                } else {
                    $('#' + id + '_tag').blur();
                }

                $.fn.tagsInput.updateTagsField(this, tagslist);

                if (options.callback && callbacks[id] && callbacks[id]['onAddTag']) {
                    var f = callbacks[id]['onAddTag'];
                    f.call(this, this, value);
                }

                if (callbacks[id] && callbacks[id]['onChange']) {
                    var i = tagslist.length;
                    var f = callbacks[id]['onChange'];
                    f.call(this, this, value);
                }

                $(".type-variant").each(function(index) {
                    variantIds.splice(index, 1, $(this).attr('id'));
                });
                count++;
                if(customizedVariantCode) {
                    first_variant_values = $('#'+variantIds[0]).val().split(_getDelimiter(delimiter[variantIds[0] ]));
                    combinations = first_variant_values;
                    step = 1;
                    while(step < variantIds.length) {
                        var newCombinations = [];
                        for (var i = 0; i < combinations.length; i++) {
                            new_variant_values = $('#'+variantIds[step]).val().split(_getDelimiter(delimiter[variantIds[step] ]));
                            for (var j = 0; j < new_variant_values.length; j++) {
                                newCombinations.push(combinations[i]+'/'+new_variant_values[j]);
                            }
                        }
                        combinations = newCombinations;
                        step++;
                    }
                    var rownumber = $('table.variant-list tbody tr:last').index();
                    if(rownumber > -1) {
                        oldCombinations = [];
                        oldAdditionalCost = [];
                        oldAdditionalPrice = [];
                        oldProductVariantId = [];
                        $(".variant-name").each(function(i) {
                            oldCombinations.push($(this).val());
                            oldProductVariantId.push($('table.variant-list tbody tr:nth-child(' + (i + 1) + ')').find('.product-variant-id').val());
                            oldAdditionalCost.push($('table.variant-list tbody tr:nth-child(' + (i + 1) + ')').find('.additional-cost').val());
                            oldAdditionalPrice.push($('table.variant-list tbody tr:nth-child(' + (i + 1) + ')').find('.additional-price').val());
                        });
                    }

                    $("table.variant-list tbody").remove();
                    var newBody = $("<tbody>");
                    for(i = 0; i < combinations.length; i++) {
                        var variant_name = combinations[i];
                        var item_code = variant_name+'-'+$("#code").val();
                        var newRow = $("<tr>");
                        var cols = '';
                        cols += '<td>'+variant_name+'<input type="hidden" class="variant-name" name="variant_name[]" value="' + variant_name + '" /></td>';
                        cols += '<td><input type="text" class="form-control item-code" name="item_code[]" value="'+item_code+'" /></td>';
                        //checking if this variant already exist in the variant table
                        oldIndex = oldCombinations.indexOf(combinations[i]);
                        if(oldIndex >= 0) {
                            cols += '<td><input type="number" class="form-control additional-cost" name="additional_cost[]" value="'+oldAdditionalCost[oldIndex]+'" step="any" /></td>';
                            cols += '<td><input type="number" class="form-control additional-price" name="additional_price[]" value="'+oldAdditionalPrice[oldIndex]+'" step="any" /></td>';
                        }
                        else {
                            cols += '<td><input type="number" class="form-control additional-cost" name="additional_cost[]" value="" step="any" /></td>';
                            cols += '<td><input type="number" class="form-control additional-price" name="additional_price[]" value="" step="any" /></td>';
                        }
                        newRow.append(cols);
                        newBody.append(newRow);
                    }
                    $("table.variant-list").append(newBody);
                }
            });

            return false;
        };

        $.fn.removeTag = function(value) {
            value = decodeURI(value);

            this.each(function() {
                var id = $(this).attr('id');

                var old = $(this).val().split(_getDelimiter(delimiter[id]));

                $('#' + id + '_tagsinput .tag').remove();

                var str = '';
                for (i = 0; i < old.length; ++i) {
                    if (old[i] != value) {
                        str = str + _getDelimiter(delimiter[id]) + old[i];
                    }
                }

                $.fn.tagsInput.importTags(this, str);

                if (callbacks[id] && callbacks[id]['onRemoveTag']) {
                    var f = callbacks[id]['onRemoveTag'];
                    f.call(this, this, value);
                }
            });

            return false;
        };

        $.fn.tagExist = function(val) {
            var id = $(this).attr('id');
            var tagslist = $(this).val().split(_getDelimiter(delimiter[id]));
            return (jQuery.inArray(val, tagslist) >= 0);
        };

        $.fn.importTags = function(str) {
            var id = $(this).attr('id');
            $('#' + id + '_tagsinput .tag').remove();
            $.fn.tagsInput.importTags(this, str);
        };

        $.fn.tagsInput = function(options) {
            var settings = jQuery.extend({
                interactive: true,
                placeholder: variantPlaceholder,
                minChars: 0,
                maxChars: null,
                limit: null,
                validationPattern: null,
                width: 'auto',
                height: 'auto',
                autocomplete: null,
                hide: true,
                delimiter: ',',
                unique: true,
                removeWithBackspace: true
            }, options);

            var uniqueIdCounter = 0;

            this.each(function() {
                if (typeof $(this).data('tagsinput-init') !== 'undefined') return;

                $(this).data('tagsinput-init', true);

                if (settings.hide) $(this).hide();

                var id = $(this).attr('id');
                if (!id || _getDelimiter(delimiter[$(this).attr('id')])) {
                    id = $(this).attr('id', 'tags' + new Date().getTime() + (++uniqueIdCounter)).attr('id');
                }

                var data = jQuery.extend({
                    pid: id,
                    real_input: '#' + id,
                    holder: '#' + id + '_tagsinput',
                    input_wrapper: '#' + id + '_addTag',
                    fake_input: '#' + id + '_tag'
                }, settings);

                delimiter[id] = data.delimiter;
                inputSettings[id] = {
                    minChars: settings.minChars,
                    maxChars: settings.maxChars,
                    limit: settings.limit,
                    validationPattern: settings.validationPattern,
                    unique: settings.unique
                };

                if (settings.onAddTag || settings.onRemoveTag || settings.onChange) {
                    callbacks[id] = [];
                    callbacks[id]['onAddTag'] = settings.onAddTag;
                    callbacks[id]['onRemoveTag'] = settings.onRemoveTag;
                    callbacks[id]['onChange'] = settings.onChange;
                }

                var markup = $('<div>', {id: id + '_tagsinput', class: 'tagsinput'}).append(
                    $('<div>', {id: id + '_addTag'}).append(
                        settings.interactive ? $('<input>', {id: id + '_tag', class: 'tag-input', value: '', placeholder: settings.placeholder}) : null
                    )
                );

                $(markup).insertAfter(this);

                $(data.holder).css('width', settings.width);
                $(data.holder).css('min-height', settings.height);
                $(data.holder).css('height', settings.height);

                if ($(data.real_input).val() !== '') {
                    $.fn.tagsInput.importTags($(data.real_input), $(data.real_input).val());
                }

                // Stop here if interactive option is not chosen
                if (!settings.interactive) return;

                $(data.fake_input).val('');
                $(data.fake_input).data('pasted', false);

                $(data.fake_input).on('focus', data, function(event) {
                    $(data.holder).addClass('focus');

                    if ($(this).val() === '') {
                        $(this).removeClass('error');
                    }
                });

                $(data.fake_input).on('blur', data, function(event) {
                    $(data.holder).removeClass('focus');
                });

                if (settings.autocomplete !== null && jQuery.ui.autocomplete !== undefined) {
                    $(data.fake_input).autocomplete(settings.autocomplete);
                    $(data.fake_input).on('autocompleteselect', data, function(event, ui) {
                        $(event.data.real_input).addTag(ui.item.value, {
                            focus: true,
                            unique: settings.unique
                        });

                        return false;
                    });

                    $(data.fake_input).on('keypress', data, function(event) {
                        if (_checkDelimiter(event)) {
                            $(this).autocomplete("close");
                        }
                    });
                } else {
                    $(data.fake_input).on('blur', data, function(event) {
                        $(event.data.real_input).addTag($(event.data.fake_input).val(), {
                            focus: true,
                            unique: settings.unique
                        });

                        return false;
                    });
                }

                // If a user types a delimiter create a new tag
                $(data.fake_input).on('keypress', data, function(event) {
                    if (_checkDelimiter(event)) {
                        event.preventDefault();

                        $(event.data.real_input).addTag($(event.data.fake_input).val(), {
                            focus: true,
                            unique: settings.unique
                        });

                        return false;
                    }
                });

                $(data.fake_input).on('paste', function () {
                    $(this).data('pasted', true);
                });

                // If a user pastes the text check if it shouldn't be splitted into tags
                $(data.fake_input).on('input', data, function(event) {
                    if (!$(this).data('pasted')) return;

                    $(this).data('pasted', false);

                    var value = $(event.data.fake_input).val();

                    value = value.replace(/\n/g, '');
                    value = value.replace(/\s/g, '');

                    var tags = _splitIntoTags(event.data.delimiter, value);

                    if (tags.length > 1) {
                        for (var i = 0; i < tags.length; ++i) {
                            $(event.data.real_input).addTag(tags[i], {
                                focus: true,
                                unique: settings.unique
                            });
                        }

                        return false;
                    }
                });

                // Deletes last tag on backspace
                data.removeWithBackspace && $(data.fake_input).on('keydown', function(event) {
                    if (event.keyCode == 8 && $(this).val() === '') {
                         event.preventDefault();
                         var lastTag = $(this).closest('.tagsinput').find('.tag:last > span').text();
                         var id = $(this).attr('id').replace(/_tag$/, '');
                         $('#' + id).removeTag(encodeURI(lastTag));
                         $(this).trigger('focus');
                    }
                });

                // Removes the error class when user changes the value of the fake input
                $(data.fake_input).keydown(function(event) {
                    // enter, alt, shift, esc, ctrl and arrows keys are ignored
                    if (jQuery.inArray(event.keyCode, [13, 37, 38, 39, 40, 27, 16, 17, 18, 225]) === -1) {
                        $(this).removeClass('error');
                    }
                });
            });

            return this;
        };

        $.fn.tagsInput.updateTagsField = function(obj, tagslist) {
            var id = $(obj).attr('id');
            $(obj).val(tagslist.join(_getDelimiter(delimiter[id])));
        };

        $.fn.tagsInput.importTags = function(obj, val) {
            $(obj).val('');

            var id = $(obj).attr('id');
            var tags = _splitIntoTags(delimiter[id], val);

            for (i = 0; i < tags.length; ++i) {
                $(obj).addTag(tags[i], {
                    focus: false,
                    callback: false
                });
            }

            if (callbacks[id] && callbacks[id]['onChange']) {
                var f = callbacks[id]['onChange'];
                f.call(obj, obj, tags);
            }
        };

        var _getDelimiter = function(delimiter) {
            if (typeof delimiter === 'undefined') {
                return delimiter;
            } else if (typeof delimiter === 'string') {
                return delimiter;
            } else {
                return delimiter[0];
            }
        };

        var _validateTag = function(value, inputSettings, tagslist, delimiter) {
            var result = true;

            if (value === '') result = false;
            if (value.length < inputSettings.minChars) result = false;
            if (inputSettings.maxChars !== null && value.length > inputSettings.maxChars) result = false;
            if (inputSettings.limit !== null && tagslist.length >= inputSettings.limit) result = false;
            if (inputSettings.validationPattern !== null && !inputSettings.validationPattern.test(value)) result = false;

            if (typeof delimiter === 'string') {
                if (value.indexOf(delimiter) > -1) result = false;
            } else {
                $.each(delimiter, function(index, _delimiter) {
                    if (value.indexOf(_delimiter) > -1) result = false;
                    return false;
                });
            }

            return result;
        };

        var _checkDelimiter = function(event) {
            var found = false;

            if (event.which === 13) {
                return true;
            }

            if (typeof event.data.delimiter === 'string') {
                if (event.which === event.data.delimiter.charCodeAt(0)) {
                    found = true;
                }
            } else {
                $.each(event.data.delimiter, function(index, delimiter) {
                    if (event.which === delimiter.charCodeAt(0)) {
                        found = true;
                    }
                });
            }

            return found;
         };

         var _splitIntoTags = function(delimiter, value) {
             if (value === '') return [];

             if (typeof delimiter === 'string') {
                 return value.split(delimiter);
             } else {
                 var tmpDelimiter = '∞';
                 var text = value;

                 $.each(delimiter, function(index, _delimiter) {
                     text = text.split(_delimiter).join(tmpDelimiter);
                 });

                 return text.split(tmpDelimiter);
             }

             return [];
         };
    })(jQuery);
    //end of variant related js

    tinymce.init({
      selector: 'textarea',
      height: 130,
      plugins: [
        'advlist autolink lists link image charmap print preview anchor textcolor',
        'searchreplace visualblocks code fullscreen',
        'insertdatetime media table contextmenu paste code wordcount'
      ],
      toolbar: 'insert | undo redo |  formatselect | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat',
      branding:false
    });

    var barcode_symbology = $("input[name='barcode_symbology_hidden']").val();
    $('select[name=barcode_symbology]').val(barcode_symbology);

    var brand = $("input[name='brand']").val();
    $('select[name=brand_id]').val(brand);

    var cat = $("input[name='category']").val();
    $('select[name=category_id]').val(cat);

    if($("input[name='unit']").val()) {
        $('select[name=unit_id]').val($("input[name='unit']").val());
        populate_unit($("input[name='unit']").val());
    }

    var tax = $("input[name='tax']").val();
    if(tax)
        $('select[name=tax_id]').val(tax);

    var tax_method = $("input[name='tax_method_id']").val();
    $('select[name=tax_method]').val(tax_method);
    $('.selectpicker').selectpicker('refresh');

    $('select[name="type"]').on('change', function() {
        if($(this).val() == 'combo'){
            $("input[name='cost']").prop('required',false);
            $("select[name='unit_id']").prop('required',false);
            hide();
            $("#digital").hide();
            $("#variant-section, #variant-option, #diffPrice-option, #diffPrice-section").hide(300);
            $("#combo").show();
            $("input[name='price']").prop('disabled',true);
        }
        else if($(this).val() == 'digital'){
            $("input[name='cost']").prop('required',false);
            $("select[name='unit_id']").prop('required',false);
            $("input[name='file']").prop('required',true);
            hide();
            $("#combo").hide();
            $("#digital").show();
            $("#variant-section, #variant-option, #diffPrice-option, #diffPrice-section").hide(300);
            $("input[name='price']").prop('disabled',false);
        }
        else if($(this).val() == 'service') {
            $("input[name='cost']").prop('required',false);
            $("select[name='unit_id']").prop('required',false);
            $("input[name='file']").prop('required',true);
            hide();
            $("#combo").hide(300);
            $("#digital").hide(300);
            $("input[name='price']").prop('disabled',false);
            $("#is-variant").prop("checked", false);
            $("#variant-section, #variant-option").hide(300);
        }
        else if($(this).val() == 'standard'){
            $("input[name='cost']").prop('required',true);
            $("select[name='unit_id']").prop('required',true);
            $("input[name='file']").prop('required',false);
            $("#cost").show();
            $("#unit").show();
            $("#alert-qty").show();
            $("#variant-option").show(300);
            $("#diffPrice-option").show(300);
            $("#digital").hide();
            $("#combo").hide();
            $("input[name='price']").prop('disabled',false);
        }
    });

    $('select[name="unit_id"]').on('change', function() {
        unitID = $(this).val();
        if(unitID) {
            populate_unit_second(unitID);
        }else{
            $('select[name="sale_unit_id"]').empty();
            $('select[name="purchase_unit_id"]').empty();
        }
    });

    <?php $productArray = []; ?>
    var lims_product_code = [
        @foreach($lims_product_list_without_variant as $product)
        <?php
            $productArray[] = htmlspecialchars($product->code . ' (' . $product->name . ')');
        ?>
        @endforeach
        @foreach($lims_product_list_with_variant as $product)
            <?php
                $productArray[] = htmlspecialchars($product->item_code . ' (' . $product->name . ')');
            ?>
        @endforeach
            <?php
                echo  '"'.implode('","', $productArray).'"';
            ?> ];

    var lims_productcodeSearch = $('#lims_productcodeSearch');

    lims_productcodeSearch.autocomplete({
        source: function(request, response) {
            var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term), "i");
            response($.grep(lims_product_code, function(item) {
                return matcher.test(item);
            }));
        },
        select: function(event, ui) {
            var data = ui.item.value;
            $.ajax({
                type: 'GET',
                url: '../lims_product_search',
                data: {
                    data: data
                },
                success: function(data) {
                    //console.log(data);
                    var flag = 1;
                    $(".product-id").each(function() {
                        if ($(this).val() == data[8]) {
                            alert('Duplicate input is not allowed!')
                            flag = 0;
                        }
                    });
                    $("input[name='product_code_name']").val('');
                    if(flag){
                        var newRow = $("<tr>");
                        var cols = '';
                        cols += '<td>' + data[0] +' [' + data[1] + ']</td>';
                        cols += '<td><input type="number" class="form-control qty" name="product_qty[]" value="1" step="any"/></td>';
                        cols += '<td><input type="number" class="form-control unit_price" name="unit_price[]" value="' + data[2] + '" step="any"/></td>';
                        cols += '<td><button type="button" class="ibtnDel btn btn-sm btn-danger">X</button></td>';
                        cols += '<input type="hidden" class="product-id" name="product_id[]" value="' + data[8] + '"/>';
                        cols += '<input type="hidden" class="" name="variant_id[]" value="' + data[9] + '"/>';

                        newRow.append(cols);
                        $("table.order-list tbody").append(newRow);
                        calculate_price();
                    }
                }
            });
        }
    });

    //Change quantity or unit price
    $("#myTable").on('input', '.qty , .unit_price', function() {
        calculate_price();
    });

    //Delete product
    $("table.order-list tbody").on("click", ".ibtnDel", function(event) {
        $(this).closest("tr").remove();
        calculate_price();
    });

    function calculate_price() {
        var price = 0;
        $(".qty").each(function() {
            rowindex = $(this).closest('tr').index();
            quantity =  $(this).val();
            unit_price = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .unit_price').val();
            price += quantity * unit_price;
        });
        $('input[name="price"]').val(price);
    }

    function hide() {
        $("#cost").hide();
        $("#unit").hide();
        $("#alert-qty").hide();
    }

    function populate_unit(unitID){
        $.ajax({
            url: '../saleunit/'+unitID,
            type: "GET",
            dataType: "json",

            success:function(data) {
                  $('select[name="sale_unit_id"]').empty();
                  $('select[name="purchase_unit_id"]').empty();
                  $.each(data, function(key, value) {
                      $('select[name="sale_unit_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                      $('select[name="purchase_unit_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                  });
                  $('.selectpicker').selectpicker('refresh');
                  var sale_unit = $("input[name='sale_unit']").val();
                  var purchase_unit = $("input[name='purchase_unit']").val();
                $('#sale-unit').val(sale_unit);
                $('select[name=purchase_unit_id]').val(purchase_unit);
                $('.selectpicker').selectpicker('refresh');
            },
        });
    }

    function populate_unit_second(unitID){
        $.ajax({
            url: '../saleunit/'+unitID,
            type: "GET",
            dataType: "json",
            success:function(data) {
                  $('select[name="sale_unit_id"]').empty();
                  $('select[name="purchase_unit_id"]').empty();
                  $.each(data, function(key, value) {
                      $('select[name="sale_unit_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                      $('select[name="purchase_unit_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                  });
                  $('.selectpicker').selectpicker('refresh');
            },
        });
    };

    $("input[name='is_batch']").on("change", function () {
        if ($(this).is(':checked')) {
            $("#variant-option").hide(300);
        }
        else
            $("#variant-option").show(300);
    });

    $("input[name='is_variant']").on("change", function () {
        variantShowHide();
    });

    $("input[name='is_diffPrice']").on("change", function () {
        diffPriceShowHide();
    });

    function variantShowHide() {
         if ($("#is-variant").is(':checked')) {
            $("#variant-section").show(300);
            $("#batch-option").hide(300);
            $(".variant-field").prop("required", true);
        }
        else {
            $("#variant-section").hide(300);
            $("#batch-option").show(300);
            $(".variant-field").prop("required", false);
        }
    };

    function diffPriceShowHide() {
         if ($("#is-diffPrice").is(':checked')) {
            $("#diffPrice-section").show(300);
        }
        else {
            $("#diffPrice-section").hide(300);
        }
    };

    $( "#promotion" ).on( "change", function() {
        if ($(this).is(':checked')) {
            $("#promotion_price").show();
            $("#start_date").show();
            $("#last_date").show();
        }
        else {
            $("#promotion_price").hide();
            $("#start_date").hide();
            $("#last_date").hide();
        }
    });

    var starting_date = $('#starting_date');
    starting_date.datepicker({
     format: "dd-mm-yyyy",
     startDate: "<?php echo date('d-m-Y'); ?>",
     autoclose: true,
     todayHighlight: true
     });

    var ending_date = $('#ending_date');
    ending_date.datepicker({
     format: "dd-mm-yyyy",
     startDate: "<?php echo date('d-m-Y'); ?>",
     autoclose: true,
     todayHighlight: true
     });

    //dropzone portion
    Dropzone.autoDiscover = false;

    jQuery.validator.setDefaults({
        errorPlacement: function (error, element) {
            if(error.html() == 'Select Category...')
                error.html('This field is required.');
            $(element).closest('div.form-group').find('.validation-msg').html(error.html());
        },
        highlight: function (element) {
            $(element).closest('div.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('div.form-group').removeClass('has-error').addClass('has-success');
            $(element).closest('div.form-group').find('.validation-msg').html('');
        }
    });

    function validate() {
        var product_code = $("input[name='code']").val();
        var barcode_symbology = $('select[name="barcode_symbology"]').val();
        var exp = /^\d+$/;

        if(!(product_code.match(exp)) && (barcode_symbology == 'UPCA' || barcode_symbology == 'UPCE' || barcode_symbology == 'EAN8' || barcode_symbology == 'EAN13') ) {
            alert('Product code must be numeric.');
            return false;
        }
        else if(product_code.match(exp)) {
            if(barcode_symbology == 'UPCA' && product_code.length > 11){
                alert('Product code length must be less than 12');
                return false;
            }
            else if(barcode_symbology == 'EAN8' && product_code.length > 7){
                alert('Product code length must be less than 8');
                return false;
            }
            else if(barcode_symbology == 'EAN13' && product_code.length > 12){
                alert('Product code length must be less than 13');
                return false;
            }
        }

        if( $("#type").val() == 'combo' ) {
            var rownumber = $('table.order-list tbody tr:last').index();
            if (rownumber < 0) {
                alert("Please insert product to table!")
                return false;
            }
        }
        $("input[name='price']").prop('disabled',false);
        return true;
    }

    $(".dropzone").sortable({
        items:'.dz-preview',
        cursor: 'grab',
        opacity: 0.5,
        containment: '.dropzone',
        distance: 20,
        tolerance: 'pointer',
        stop: function () {
          var queue = myDropzone.getAcceptedFiles();
          newQueue = [];
          $('#imageUpload .dz-preview .dz-filename [data-dz-name]').each(function (count, el) {
                var name = el.innerHTML;
                queue.forEach(function(file) {
                    if (file.name === name) {
                        newQueue.push(file);
                    }
                });
          });
          myDropzone.files = newQueue;
        }
    });

    myDropzone = new Dropzone('div#imageUpload', {
        addRemoveLinks: true,
        autoProcessQueue: false,
        uploadMultiple: true,
        parallelUploads: 100,
        maxFilesize: 12,
        paramName: 'image',
        clickable: true,
        method: 'POST',
        url:'../update',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        renameFile: function(file) {
            var dt = new Date();
            var time = dt.getTime();
            return time + file.name;
        },
        acceptedFiles: ".jpeg,.jpg,.png,.gif",
        init: function () {
            var myDropzone = this;
            $('#submit-btn').on("click", function (e) {
                e.preventDefault();
                if ( $("#product-form").valid() && validate() ) {
                    tinyMCE.triggerSave();
                    if(myDropzone.getAcceptedFiles().length) {
                        myDropzone.processQueue();
                    }
                    else {
                        var formData = new FormData();
                        //$("#product-form").serialize();
                        var data = $("#product-form").serializeArray();
                        $.each(data, function (key, el) {
                            formData.append(el.name, el.value);
                        });
                        var file = $('#file')[0].files;
                        if(file.length > 0)
                            formData.append('file',file[0]);
                        $.ajax({
                            type:'POST',
                            url:'../update',
                            data: formData,
                            contentType: false,
                            processData: false,
                            success:function(response) {
                                //console.log(response);
                                location.href = redirectUrl;
                            },
                            error:function(response) {
                                //console.log(response);
                              if(response.responseJSON.errors.name) {
                                  $("#name-error").text(response.responseJSON.errors.name);
                              }
                              else if(response.responseJSON.errors.code) {
                                  $("#code-error").text(response.responseJSON.errors.code);
                              }
                            },
                        });
                    }
                }
            });

            this.on('sending', function (file, xhr, formData) {
                // Append all form inputs to the formData Dropzone will POST
                var data = $("#product-form").serializeArray();
                $.each(data, function (key, el) {
                    formData.append(el.name, el.value);
                });
                var file = $('#file')[0].files;
                if(file.length > 0)
                    formData.append('file',file[0]);
            });
        },
        error: function (file, response) {
            console.log(response);
            /*if(response.errors.name) {
              $("#name-error").text(response.errors.name);
              this.removeAllFiles(true);
            }
            else if(response.errors.code) {
              $("#code-error").text(response.errors.code);
              this.removeAllFiles(true);
            }
            else {
              try {
                  var res = JSON.parse(response);
                  if (typeof res.message !== 'undefined' && !$modal.hasClass('in')) {
                      $("#success-icon").attr("class", "fas fa-thumbs-down");
                      $("#success-text").html(res.message);
                      $modal.modal("show");
                  } else {
                      if ($.type(response) === "string")
                          var message = response; //dropzone sends it's own error messages in string
                      else
                          var message = response.message;
                      file.previewElement.classList.add("dz-error");
                      _ref = file.previewElement.querySelectorAll("[data-dz-errormessage]");
                      _results = [];
                      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
                          node = _ref[_i];
                          _results.push(node.textContent = message);
                      }
                      return _results;
                  }
              } catch (error) {
                  console.log(error);
              }
            }*/
        },
        successmultiple: function (file, response) {
            location.href = redirectUrl;
            //console.log('sss: '+ response);
        },
        completemultiple: function (file, response) {
            console.log(file, response, "completemultiple");
        },
        reset: function () {
            console.log("resetFiles");
            this.removeAllFiles(true);
        }
    });

</script>
@endpush
