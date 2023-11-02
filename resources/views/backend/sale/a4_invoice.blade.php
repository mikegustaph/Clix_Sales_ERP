<!DOCTYPE html>
<html>
    <head>
        <link rel="icon" type="image/png" href="{{url('public/logo', $general_setting->site_logo)}}" />
        <title>{{$lims_sale_data->customer->name.'_Sale_'.$lims_sale_data->reference_no}}</title>
        <style type="text/css">
            span,td {
                font-size: 13px;
                line-height: 1.4;
            }
            @media print {
                .hidden-print {
                    display: none !important;
                }
                tr.table-header {
                    background-color:rgb(1, 75, 148) !important;
                    -webkit-print-color-adjust: exact;
                }
                td.td-text {
                    background-color:rgb(205, 218, 235) !important;
                    -webkit-print-color-adjust: exact;
                }
            }
            table,tr,td {font-family: sans-serif;border-collapse: collapse;}
        </style>
    </head>
    <body>
        @if(preg_match('~[0-9]~', url()->previous()))
        @php $url = '../../pos'; @endphp
        @else
            @php $url = url()->previous(); @endphp
        @endif
        <div class="hidden-print">
            <table>
                <tr>
                    <td><a href="{{$url}}" class="btn btn-info"><i class="fa fa-arrow-left"></i> {{trans('file.Back')}}</a> </td>
                    <td><button onclick="window.print();" class="btn btn-primary"><i class="dripicons-print"></i> {{trans('file.Print')}}</button></td>
                </tr>
            </table>
            <br>
        </div>
        <table style="width: 100%;border-collapse: collapse;">
            <tr>
                <td colspan="2" style="padding:9px 0;width:40%">
                    <h1 style="margin:0">{{$lims_biller_data->company_name}}</h1>
                    <div>
                        <span>Address:</span>&nbsp;&nbsp;<span>{{$lims_warehouse_data->address}}</span>
                    </div>
                    <div>
                        <span>Phone:</span>&nbsp;&nbsp;<span>{{$lims_warehouse_data->phone}}</span>
                    </div>
                    @if($general_setting->vat_registration_number)
                    <div>
                        <span>{{trans('file.VAT Number')}}:</span>&nbsp;&nbsp;<span>{{$general_setting->vat_registration_number}}</span>
                    </div>
                    @endif
                    <?php 
                        foreach($sale_custom_fields as $key => $fieldName) {
                            $field_name = str_replace(" ", "_", strtolower($fieldName));
                            echo '<div><span>'.$fieldName.': ' . $lims_sale_data->$field_name.'</span></div>';
                        }
                        foreach($customer_custom_fields as $key => $fieldName) {
                            $field_name = str_replace(" ", "_", strtolower($fieldName));
                            echo '<div><span>'.$fieldName.': ' . $lims_customer_data->$field_name.'</span></div>';
                        }
                    ?>
                </td>
                <td style="width:30%; text-align: middle; vertical-align: top;">
                    <img src="{{url('logo', $general_setting->site_logo)}}" height="80" width="120">
                </td>
                <td style="padding:5px -19px;width:30%;text-align:right;">
                    <div style="display: flex;justify-content: space-between;border-bottom:1px solid #aaa">
                        <span>Invoice No:</span> <span>{{$lims_sale_data->reference_no}}</span>
                    </div>
                    <div style="display: flex;justify-content: space-between;border-bottom:1px solid #aaa">
                        <span>Date:</span> <span>{{$lims_sale_data->created_at}}</span>
                    </div>
                    @if($paid_by_info)
                        <div style="display: flex;justify-content: space-between;border-bottom:1px solid #aaa">
                            <span>Paid By:</span> <span>{{$paid_by_info}}</span>
                        </div>
                    @endif
                </td>
            </tr>
        </table>
        <table style="width: 100%;border-collapse: collapse; margin-top: 4px;">
            <tr>
                <td colspan="3" style="padding:4px 0;width:30%;vertical-align:top">
                    <h2 style="background-color: rgb(1, 75, 148); color: white; padding:3px 10px; margin-bottom:0">Bill To</h2>
                    <div style="margin-top: 10px;margin-left: 10px">
                        <span>{{$lims_customer_data->name}}</span>
                    </div>
                    <div style="margin-left: 10px">
                        <span>VAT Number:</span>&nbsp;&nbsp;<span>{{$lims_customer_data->tax_no}}</span>
                    </div>
                    <div style="margin-left: 10px">
                        <span>Address:</span>&nbsp;&nbsp;<span>{{$lims_customer_data->address}}</span>
                    </div>
                    <div style="margin-bottom: 10px;margin-left: 10px">
                        <span>Phone:</span>&nbsp;&nbsp;<span>{{$lims_customer_data->phone_number}}</span>
                    </div>
                </td>
                <td colspan="4" style="width:60%">
                    
                </td>
            </tr>
        </table>
        <table dir="@if( Config::get('app.locale') == 'ar' || $general_setting->is_rtl){{'rtl'}}@endif" style="width: 100%;border-collapse: collapse;">
            <tr class="table-header" style="background-color: rgb(1, 75, 148); color: white;">
                <td style="border:1px solid #222;padding:1px 3px;width:4%;text-align:center">#</td>
                <td style="border:1px solid #222;padding:1px 3px;width:49%;text-align:center">{{trans('file.Description')}}</td>
                <td style="border:1px solid #222;padding:1px 3px;width:6%;text-align:center">{{trans('file.Qty')}}</td>
                <td style="border:1px solid #222;padding:1px 3px;width:9%;text-align:center">{{trans('file.Unit Price')}}</td>
                <td style="border:1px solid #222;padding:1px 3px;width:7%;text-align:center">{{trans('file.Total')}}</td>
                <td style="border:1px solid #222;padding:1px 3px;width:7%;text-align:center">{{trans('file.Tax')}}</td>
                <td style="border:1px solid #222;padding:1px 2px;width:13%;text-align:center;">{{trans('file.Subtotal')}}</td>
            </tr>
            <?php 
                $total_product_tax = 0;
                $totalPrice = 0;
            ?>
            @foreach($lims_product_sale_data as $key => $product_sale_data)
            <?php 
                $lims_product_data = \App\Product::select('name')->find($product_sale_data->product_id);
                if($product_sale_data->sale_unit_id) {
                    $unit = \App\Unit::select('unit_code')->find($product_sale_data->sale_unit_id);
                    $unit_code = $unit->unit_code;
                }
                else
                    $unit_code = '';

                if($product_sale_data->variant_id) {
                    $variant = \App\Variant::select('name')->find($product_sale_data->variant_id);
                    $variant_name = $variant->name;
                }
                else
                    $variant_name = '';
                $totalPrice += $product_sale_data->net_unit_price * $product_sale_data->qty; 
            ?>
            <tr>
                <td style="@if( Config::get('app.locale') == 'ar' || $general_setting->is_rtl){{'border-right:1px solid #222;'}}@endif border:1px solid #222;padding:1px 3px;text-align: center;">{{$key+1}}</td>
                <td style="border:1px solid #222;padding:1px 3px;font-size: 15px;line-height: 1.2;">
                    {{$lims_product_data->name}}
                </td>
                <td style="border:1px solid #222;padding:1px 3px;text-align:center">{{$product_sale_data->qty.' '.$unit_code.' '.$variant_name}}</td>
                <td style="border:1px solid #222;padding:1px 3px;text-align:center">{{$product_sale_data->net_unit_price }}</td>
                <td style="border:1px solid #222;padding:1px 3px;text-align:center">{{$product_sale_data->net_unit_price * $product_sale_data->qty }}</td>
                <td style="border:1px solid #222;padding:1px 3px;text-align:center">{{$product_sale_data->tax }}</td>
                <td style="border:1px solid #222;border-right:1px solid #222;padding:1px 3px;text-align:center;font-size: 15px;">{{$product_sale_data->total }}</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="3" rowspan="4" style="border:1px solid #222;padding:1px 3px;text-align: center; vertical-align: top;">
                    {{trans('file.Note')}}<br>{{$lims_sale_data->sale_note}}
                </td>
                <td class="td-text" colspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);">
                    {{trans('file.Total Before Tax')}}
                </td>
                <td class="td-text" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;">
                        {{number_format((float)($lims_sale_data->grand_total - ($lims_sale_data->total_tax+$lims_sale_data->order_tax) ) ,$general_setting->decimal, '.', '')}}
                </td>
            </tr>
            <tr>
                <td class="td-text" colspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);">
                    {{trans('file.Tax')}}
                </td>
                <td class="td-text" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;">
                    {{number_format((float)($lims_sale_data->total_tax+$lims_sale_data->order_tax) ,$general_setting->decimal, '.', '')}}
                </td>
            </tr>
            <tr>
                <td class="td-text" colspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);">
                    {{trans('file.Discount')}}
                </td>
                <td class="td-text" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;">
                    {{number_format((float)($lims_sale_data->total_discount+$lims_sale_data->order_discount) ,$general_setting->decimal, '.', '')}}
                </td>
            </tr>
            <tr>
                <td class="td-text" colspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);">{{trans('file.grand total')}}</td>
                <td class="td-text" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;">{{number_format((float)$lims_sale_data->grand_total ,$general_setting->decimal, '.', '')}}</td>
            </tr>
            <tr>
                @if($general_setting->currency_position == 'prefix')
                    <td class="td-text" colspan="3" rowspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;vertical-align: bottom;font-size: 15px;">
                        {{trans('file.In Words')}}<br>{{$currency_code}} <span style="text-transform:capitalize;font-size: 15px;">{{str_replace("-"," ",$numberInWords)}}</span> only
                    </td>
                @else
                    <td class="td-text" colspan="3" rowspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;vertical-align: bottom;font-size: 15px;">
                        {{trans('file.In Words')}}:<br><span style="text-transform:capitalize;font-size: 15px;">{{str_replace("-"," ",$numberInWords)}}</span> {{$currency_code}} only
                    </td>
                @endif
            </tr>
            <tr>
                <td class="td-text" colspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);">
                    {{trans('file.Paid')}}
                </td>
                <td class="td-text" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;">
                    {{number_format((float)$lims_sale_data->paid_amount ,$general_setting->decimal, '.', '')}}
                </td>
            </tr>
            <tr>
                <td class="td-text" colspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);">
                    {{trans('file.Due')}}
                </td>
                <td class="td-text" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;">
                    {{number_format((float)($lims_sale_data->grand_total - $lims_sale_data->paid_amount) ,$general_setting->decimal, '.', '')}}
                </td>
            </tr>
        </table>
        <table style="width: 100%; border-collapse: collapse;margin-top:-9px;">
            <tr>
                <td style="width: 100%; text-align: center">
                    <br>
                    <?php echo '<img style="max-width:100%" src="data:image/png;base64,' . DNS1D::getBarcodePNG($lims_sale_data->reference_no, 'C128') . '" alt="barcode"   />';?>
                    <br><br>
                    <?php echo '<img style="width:5%" src="data:image/png;base64,' . DNS2D::getBarcodePNG($qrText, 'QRCODE') . '" alt="barcode"   />';?> 
                </td>
            </tr>
        </table>
        <script type="text/javascript">
            localStorage.clear();
            function auto_print() {     
                window.print();
                
            }
            setTimeout(auto_print, 1000);
        </script>
    </body>
</html>