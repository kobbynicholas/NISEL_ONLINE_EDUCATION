<?php

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| CURRICULUM BOOKING PRICES
|--------------------------------------------------------------------------
|
| Each subject booking gives:
| - 2 lessons per week
| - 8 lessons per month
|
|--------------------------------------------------------------------------
*/

$curriculumPrices = [

    'Cambridge' => 1000,

    'IB' => 1200,

    'GES' => 800

];


/*
|--------------------------------------------------------------------------
| GET CURRICULUM PRICE
|--------------------------------------------------------------------------
*/

function getCurriculumPrice($curriculum)
{

    global $curriculumPrices;


    $curriculum =
        trim($curriculum);


    /*
    |--------------------------------------------------------------------------
    | Find curriculum case-insensitively
    |--------------------------------------------------------------------------
    */

    foreach (
        $curriculumPrices
        as $name => $price
    ) {

        if (
            strtolower($name)
            ===
            strtolower($curriculum)
        ) {

            return (float) $price;

        }

    }


    return 0;

}


/*
|--------------------------------------------------------------------------
| FORMAT CURRENCY
|--------------------------------------------------------------------------
*/

function formatGHS($amount)
{

    return
        'GHS '
        .
        number_format(
            (float) $amount,
            2
        );

}

?>
