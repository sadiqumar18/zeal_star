<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


// Data types
define('BOOLEAN', '1');
define('INTEGER', '2');
define('STRING', '3');
//Error Codes
define('REQUEST_METHOD_NOT_VALID', 100);
define('REQUEST_CONTENTTYPE_NOT_VALID', 101);
define('REQUEST_NOT_VALID', 102);
define('VALIDATE_PARAMETER_REQUIRED', 103);
define('VALIDATE_PARAMETER_DATATYPE', 104);



class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public function validateParameter( $fieldName, $value, $dataType, $required = true)
	{
		if ($required && empty($value))  {
			$this->throwError(VALIDATE_PARAMETER_REQUIRED, $fieldName.' is required.');
		}

		switch ($dataType) {
			case BOOLEAN:
				if (!is_bool($value)) {
					$this->throwError(VALIDATE_PARAMETER_DATATYPE, $fieldName.' is Not Valid.');
				}
				break;
			case INTEGER:
				if (!is_numeric($value)) {
					$this->throwError(VALIDATE_PARAMETER_DATATYPE, $fieldName.' is Not Valid.');
				}
				break;
			case STRING:
				if (!is_string($value)) {
					$this->throwError(VALIDATE_PARAMETER_DATATYPE, $fieldName.' is Not Valid.');
				}
				break;
			
			default:
				$this->throwError(VALIDATE_PARAMETER_DATATYPE, $fieldName.' is Not Valid.');
				break;
		}

	

	}

	public function throwError($code, $message)
	{
		header("content-type: application/json");
		$errorMsg = json_encode(
			 ['status' => 'error', 'message' => $message]);
		echo $errorMsg;
		exit;
	}

	public function throwMsg($code, $message)
	{
		return response()->json([
			'status' => $code,
			'message' => $message
		 ]);
	}

	public function  generateKey($length) {
        $key = '';
        $keys = array_merge(range(0, 9),range('a', 'z'));
        for ($i = 0; $i < $length; $i++) {
                $key .= $keys[array_rand($keys)];
            }
            return $key;
	}
	
	public function  generatePin($length) {
        $key = '';
        $keys = array_merge(range(0, 9));
        for ($i = 0; $i < $length; $i++) {
                $key .= $keys[array_rand($keys)];
            }
            return $key;
    }
}
