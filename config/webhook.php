<?php




return [


    "reverse_clause" => [
        "You are not sending to valid MTN number.",
        "Sorry, you are not gifting to a valid Globacom user.",
        "USSD string *312*500_gift was not recognized."
    ],

    "ignore_clause" => [
        "SENT",
        "Sorry Operation failed",
        "cannot be processed",
        "please wait for a confirmation SMS thank you",
        "Ussd timeout occurred!",
        "Invalid input provided.",
        "MMI complete.",
        "UNKNOWN APPLICATION",
        "Dear Customer, Service is currently unavailable.",
        "Carrier info",
        "You don't have sufficient data to share.",
        "SORRY!Insufficient credit balance",
        "Sorry, your request may not respond in time. Please try again later.",
        "Oops, activation of 40GB Monthly Plan plan was not successful",
        "Oops, activation of SHARE"   
    ],
    
    "retry_clause" => [
        "Connection problem or invalid MMI code.",
        "Oops, looks like the code you used was incorrect. Please check and try again.",
        "Enter Recipient's number",
        "Invalid msisdn provided",
        "You are not sending to valid MTN number.",
        "System is busy. Please try later.",
        "You have reached your SME data share limit.",
        "Sorry Operation failed , Please try again later",
        "SORRY!Insufficient credit balance for the plan you want to buy.Please recharge your line or you can simply Borrow Data. To Borrow Data now, just dial *321#",
        "You have entered invalid PIN.",
        "Sorry for the inconvenience Please try after some time",
        "Sorry Operation failed , Please try again later"
    ],
    "success_clause"=>[
        "successfully",
        "successful",
        "under process",
        "topped up",
        "Oops, activation of 40GB Monthly Plan plan was not successful"
    ],
    "change_pin_clause"=>[
        "your security key is"
    ],
    "check_stop_transaction_and_change_pin"=>[
        //"Oops, activation of SHARE",
        "Yello, invalid input entered . Please check and try again." 
    ],

    "check_telerivet_ussd" =>[
        "*461*"
    ],
    "check_telerivet_glo" =>[
        "*127*"
    ],

    "check_telerivet_mtn_airtime" =>[
        "*456*"
    ],

    "check_telerivet_airtel_data"=>[
        "*141*"
    ]

];
