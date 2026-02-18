<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\FeeFundCategory;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApiController extends Controller
{
    public function fetchFeeCategories(){
        $consumer = Consumer::select([
            'id as category_id',
            'category_title',
            'details as category_description',
        ])->get();

        return $consumer;
    }
}
