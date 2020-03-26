<?php
namespace Eniture\FedExLTLFreightQuotes\Model\Carrier;

/**
 * Class WordToNumberConversion
 *
 * @package Eniture\FedExLTLFreightQuotes\Model\Carrier
 */
class WordToNumberConversion
{

    /**
     * @param $str
     * @return float|int
     */
    public function wordsToNumber($str)
    {
        $numbers = [
            'zero' => 0,
            'one' => 1,
            'two' => 2,
            'three' => 3,
            'four' => 4,
            'five' => 5,
            'six' => 6,
            'seven' => 7,
            'eight' => 8,
            'nine' => 9,
            'ten' => 10,
            'eleven' => 11,
            'twelve' => 12,
            'thirteen' => 13,
            'fourteen' => 14,
            'fifteen' => 15,
            'sixteen' => 16,
            'seventeen' => 17,
            'eighteen' => 18,
            'nineteen' => 19,
            'twenty' => 20,
            'thirty' => 30,
            'forty' => 40,
            'fifty' => 50,
            'sixty' => 60,
            'seventy' => 70,
            'eighty' => 80,
            'ninety' => 90,
            'hundred' => 100,
            'thousand' => 1000,
            'million' => 1000000,
            'billion' => 1000000000
        ];

        //first we remove all unwanted characters... and keep the text
        $str = preg_replace("/[^a-zA-Z]+/", " ", $str);

        //now we explode them word by word... and loop through them
        $words = explode(" ", $str);

        //i divide each thousands in groups then add them at the end
        //For example 2,640,234 "two million six hundred and forty thousand two hundred and thirty four"
        //is defined into 2,000,000 + 640,000 + 234

        //the $total will be the variable were we will add up to
        $total = 1;

        //flag to force the next operation to be an addition
        $force_addition = false;

        //hold the last digit we added/multiplied
        $last_digit = null;

        //the final_sum will be the array that will hold every portion "2000000,640000,234" which we will sum at the end to get the result
        $final_sum = [];

        foreach ($words as $word) {
            //if its not an and or a valid digit we skip this turn
            if (!isset($numbers[$word]) && $word != "and") {
                continue;
            }

            //all small letter to ease the comparaison
            $word = strtolower($word);

            //if it's an and .. and this is the first digit in the group we set the total = 0
            //and force the next operation to be an addition
            if ($word == "and") {
                if ($last_digit === null) {
                    $total = 0;
                }
                $force_addition = true;
            } else {
                //if its a digit and the force addition flag is on we sum
                if ($force_addition) {
                    $total += $numbers[$word];
                    $force_addition = false;
                } else {
                    //if the last digit is bigger than the current digit we sum else we multiply
                    //example twenty one => 20+1,  twenty hundred 20 * 100
                    if ($last_digit !== null && $last_digit > $numbers[$word]) {
                        $total += $numbers[$word];
                    } else {
                        $total *= $numbers[$word];
                    }
                }
                $last_digit = $numbers[$word];

                //finally we distinguish a group by the word thousand, million, billion  >= 1000 !
                //we add the current total to the $final_sum array clear it and clear all other flags...
                if ($numbers[$word] >= 1000) {
                    $final_sum[] = $total;
                    $last_digit = null;
                    $force_addition = false;
                    $total = 1;
                }
            }
        }
        // there is your final answer !
        $final_sum[] = $total;
        return array_sum($final_sum);
    }
}
