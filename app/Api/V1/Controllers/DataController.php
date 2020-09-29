<?php



namespace App\Api\V1\Controllers;

use App\DataProduct;
use App\DataTransaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;



class DataController extends Controller{





    public function create(Request $request)
    {

        $this->validate($request, [
            'network' => 'required',
            'bundle' => 'required|unique:data_products',
            'code' => 'required|unique:data_products',
            'price' => 'required',
            'validity' => 'required'
        ]);



        $flag = DataProduct::create($request->all());

        if(!$flag){
            return response()->json(['status'=>'error','message'=>'Unable to create data product'],400);
        }

        return response()->json(['status'=>'success','message'=>'Data successfully created'],201);
     

    }




    public function adminTransactions()
    {
        $transactions = DataTransaction::with('user')->orderBy('id','DESC')->paginate(20);

        return response()->json($transactions,200);
     
    }



    public function update(Request $request, $id)
    {

        $this->validate($request, [
            'bundle' => 'exists:data_products',
        ]);


        $flag = DataProduct::findOrFail($id)->update($request->only(['validity','price','code']));

        if(!$flag){
            return response()->json(['status'=>'error','message'=>'Unable to update data product'],400);
        }

        return response()->json(['status'=>'success','message'=>'Data successfully updated'],201);
     
    }


























    
}