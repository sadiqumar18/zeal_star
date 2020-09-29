<?php

namespace App\Api\V1\Controllers;

use App\GeneratedPin;
use Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GeneratedPinController extends Controller
{

   public function generateRecharge($value,$size)
   {

      for ($i= 0; $i <= $size ; $i++) { 
        $generated = new GeneratedPin;
        $generated->serial_number = $this->generateKey(16);
        $generated->pin_number = $this->generatePin(16);
        $generated->value = $value;
        $generated->save();

        
        

      }

      return response()->json([
         'status' => 'success',
         'message' => $size.' pins generated'
      ]);


   }


}
