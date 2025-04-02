<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use OpenApi\Annotations;
use App\Models\Pharmacies;
use App\Models\PharmacyMasks;
use App\Models\PharmacyOpeningHours;
use App\Models\Users;
use App\Models\UserPurchaseHistories;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Phantom Platform",
 * ),
 *
 */
class ApiController extends Controller
{
    /**
     * @OA\Tag(
     *     name="B1.",
     *     description="List all pharmacies open at a specific time and on a day of the week if requested."
     * )
     * @OA\Get( 
     *   tags={"B1."},
     *   path="/api/getPharmacies",
     *   summary="get the pharmacies data",
     *   @OA\Parameter(
     *      parameter="time",
     *      in="query",
     *      name="time",
     *      description="input time.(14:30)",
     *      @OA\Schema(
     *          type="string",
     *          @OA\Property(
     *                property="time",
     *                type="string",
     *                pattern="^([01][0-9]|2[0-3]):[0-5][0-9]$",
     *                example="14:30"
     *            )
     *      )
     *   ),
     *   @OA\Parameter(
     *      parameter="week",
     *      in="query",
     *      name="week",
     *      description="select day of the week.",
     *      @OA\Schema(
     *          type="string",
     *          enum={"Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"}
     *      )
     *   ),
     *   @OA\Response(response=200, description="OK",
     *      content={
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 example={
     *                    {
     *                      "message": "",
     *                      "data": {
     *                         {
     *                          "name": "DFW Wellness",
     *                          "cash_balance": 328.41
     *                         },
     *                      }
     *                   }
     *              }
     *          )
     *       }
     *   )
     * )
     */
    public function getPharmacies(Request $request)
    {
        $response = ['status' => 'error', 'message' => '', 'data' => []];

        $rules = [
            'time' => 'required|date_format:H:i',
            'week' => 'required|string|in:Mon,Tue,Wed,Thu,Fri,Sat,Sun'
        ];

        $messages = [];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            $response['message'] = $validator->errors();
            return response()->json($response, 400);
        }

        $param_time = $request->get("time");
        $param_week = $request->get("week");
        $pharmacies = Pharmacies::query()
            ->join('pharmacy_opening_hours as poh', 'pharmacies.id', '=', 'poh.pharmacy_id')
            ->select('pharmacies.name', 'pharmacies.cash_balance')
            ->distinct();
        if ($param_time) {
            $pharmacies->where(function ($query) use ($param_time) {
                $query->where(function ($query1) use ($param_time) {
                    $query1->whereColumn("poh.start_time", "<=", "poh.end_time")
                        ->where("poh.start_time", "<=", $param_time)
                        ->where("poh.end_time", ">=", $param_time);
                })->orWhere(function ($query1) use ($param_time) {
                    $query1->whereColumn("poh.start_time", ">", "poh.end_time")
                        ->where(function ($query2) use ($param_time) {
                            $query2->where("poh.start_time", "<=", $param_time)
                                ->orWhere("poh.end_time", ">=", $param_time);
                        });
                });
            });
        }
        if ($param_week) {
            $pharmacies->where("poh.week", $param_week);
        }
        $response['status'] = 'success';
        $response['data'] = $pharmacies->get();

        return response()->json($response, 200);
    }
    /**
     * @OA\Tag(
     *     name="B2.",
     *     description="List all masks sold by a given pharmacy, sorted by mask name or price."
     * )
     * @OA\Get(
     *   tags={"B2."},
     *   path="/api/getPharmacyMasks",
     *   summary="get pharmacy masks by pharmacy name.",
     *   @OA\Parameter(
     *      parameter="pharmacyName",
     *      in="query",
     *      name="pharmacyName",
     *      description="input pharmacy name.",
     *      @OA\Schema(
     *          type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      parameter="sort",
     *      in="query",
     *      name="sort",
     *      description="select sort.",
     *      @OA\Schema(
     *          type="string",
     *          enum={"mask_name", "mask_price"}
     *      )
     *   ),
     *   @OA\Response(response=200, description="OK",
     *      content={
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 example={
     *                    {
     *                      "message": "",
     *                      "data": {
     *                         {
     *                          "mask_name": "True Barrier (green) (3 per pack)",
     *                          "mask_price": 13.7
     *                         },
     *                      }
     *                   }
     *              }
     *          )
     *       }
     *   )
     * )
     */
    public function getPharmacyMasks(Request $request)
    {
        $response = ['status' => 'error', 'message' => '', 'data' => []];

        $rules = [
            'pharmacyName' => 'required|string',
            'sort' => 'string|in:mask_name,mask_price'
        ];

        $messages = [];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            $response['message'] = $validator->errors();
            return response()->json($response, 400);
        }

        $param_pharmacy = $request->get("pharmacyName");
        $sort = $request->get("sort") ?: 'mask_name';
        $pharmacies = PharmacyMasks::query()
            ->join('pharmacies', 'pharmacies.id', '=', 'pharmacy_masks.pharmacy_id')
            ->select('pharmacy_masks.mask_name', 'pharmacy_masks.mask_price');
        if ($param_pharmacy) {
            $pharmacies->where("pharmacies.name", $param_pharmacy);
        }
        if ($sort) {
            $pharmacies->orderBy($sort, "asc");
        }
        $response['status'] = 'success';
        $response['data'] = $pharmacies->get();

        return response()->json($response, 200);
    }
    /**
     * @OA\Tag(
     *     name="B3.",
     *     description="List all pharmacies with more or less than x mask products within a price range."
     * )
     * @OA\Get(
     *   tags={"B3."},
     *   path="/api/getPharmaciesByMaskPriceAndAmount",
     *   summary="get pharmacies by mask price and amount.",
     *   @OA\Parameter(
     *      parameter="priceFrom",
     *      in="query",
     *      name="priceFrom",
     *      description="input price range.",
     *      @OA\Schema(
     *          type="number",
     *          format="float"
     *      )
     *   ),
     *   @OA\Parameter(
     *      parameter="priceTo",
     *      in="query",
     *      name="priceTo",
     *      description="input price range.",
     *      @OA\Schema(
     *          type="number",
     *          format="float"
     *      )
     *   ),
     *   @OA\Parameter(
     *      parameter="amount",
     *      in="query",
     *      name="amount",
     *      description="input amount.",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *   ),
     *   @OA\Response(response=200, description="OK",
     *      content={
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 example={
     *                    {
     *                      "message": "",
     *                      "data": {
     *                         {
     *                          "name": "DFW Wellness",
     *                          "mask_amount": 5
     *                         },
     *                      }
     *                   }
     *              }
     *          )
     *       }
     *   )
     * )
     */
    public function getPharmaciesByMaskPriceAndAmount(Request $request)
    {
        $response = ['status' => 'error', 'message' => '', 'data' => []];

        $rules = [
            'priceFrom' => 'required|numeric',
            'priceTo' => 'required|numeric|gte:priceFrom'
        ];

        $messages = [];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $response['message'] = $validator->errors();
            return response()->json($response, 400);
        }

        $param_price_from = $request->get("priceFrom");
        $param_price_to = $request->get("priceTo");
        $param_amount = $request->get("amount");
        $pharmacies = PharmacyMasks::query()
            ->join('pharmacies', 'pharmacies.id', '=', 'pharmacy_masks.pharmacy_id')
            ->select('pharmacies.name as pharmacy_name', DB::raw('COUNT(*) as mask_amount'));
        if ($param_price_from) {
            $pharmacies->where("pharmacy_masks.mask_price", ">=", $param_price_from);
        }
        if ($param_price_to) {
            $pharmacies->where("pharmacy_masks.mask_price", "<=", $param_price_to);
        }
        if ($param_amount) {
            $pharmacies->having("mask_amount", ">=", $param_amount);
        }
        $response['status'] = 'success';
        $response['data'] = $pharmacies->groupBy("pharmacies.name")->get();

        return response()->json($response, 200);
    }
    /**
     * @OA\Tag(
     *     name="B4.",
     *     description="The top x users by total transaction amount of masks within a date range."
     * )
     * @OA\Get(
     *   tags={"B4."},
     *   path="/api/getUsersByDateRange",
     *   summary="get users by date range.",
     *   @OA\Parameter(
     *      parameter="startDate",
     *      in="query",
     *      name="startDate",
     *      description="input start date.",
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      )
     *   ),
     *   @OA\Parameter(
     *      parameter="endDate",
     *      in="query",
     *      name="endDate",
     *      description="input end date.",
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      )
     *   ),
     *   @OA\Parameter(
     *      parameter="top",
     *      in="query",
     *      name="top",
     *      description="input top.",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *   ),
     *   @OA\Response(response=200, description="OK",
     *      content={
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 example={
     *                    {
     *                      "message": "",
     *                      "data": {
     *                         {
     *                          "name": "user-name",
     *                          "transaction_amount": 12.35
     *                         },
     *                      }
     *                   }
     *              }
     *          )
     *       }
     *   )
     * )
     */
    public function getUsersByDateRange(Request $request)
    {
        $response = ['status' => 'error', 'message' => '', 'data' => []];

        $rules = [
            'startDate' => 'required|date_format:Y-m-d',
            'endDate' => 'required|date_format:Y-m-d|gte:startDate',
            'top' => 'integer'
        ];

        $messages = [];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $response['message'] = $validator->errors();
            return response()->json($response, 400);
        }

        $param_start_date = $request->get("startDate");
        $param_end_date = $request->get("endDate");
        $param_top = $request->get("top");
        $users = Users::query()
            ->join('user_purchase_histories', 'users.id', '=', 'user_purchase_histories.user_id')
            ->select('users.name', DB::raw('sum(user_purchase_histories.transaction_amount) as transaction_amount'));
        if ($param_start_date) {
            $users->where("user_purchase_histories.transaction_date", ">=", $param_start_date);
        }
        if ($param_end_date) {
            $users->where("user_purchase_histories.transaction_date", "<=", $param_end_date);
        }

        $users->orderByDesc("transaction_amount");
        if ($param_top) {
            $users->limit($param_top);
        }
        $response['status'] = 'success';
        $response['data'] = $users->groupBy("users.name")->get();

        return response()->json($response, 200);
    }
    /**
     * @OA\Tag(
     *     name="B5.",
     *     description="The total amount of masks and dollar value of transactions within a date range."
     * )
     * @OA\Get(
     *   tags={"B5."},
     *   path="/api/getMaskTransactionsData",
     *   summary="get mask transactions data.",
     *   @OA\Parameter(
     *      parameter="startDate",
     *      in="query",
     *      name="startDate",
     *      description="input start date.",
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      )
     *   ),
     *   @OA\Parameter(
     *      parameter="endDate",
     *      in="query",
     *      name="endDate",
     *      description="input end date.",
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      )
     *   ),
     *   @OA\Response(response=200, description="OK",
     *      content={
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 example={
     *                    {
     *                      "message": "",
     *                      "data": {
     *                         {
     *                          "mask_name": "mask-name",
     *                          "dollar": 333,
     *                          "amount": 5
     *                         },
     *                      }
     *                   }
     *              }
     *          )
     *       }
     *   )
     * )
     */
    public function getMaskTransactionsData(Request $request)
    {
        $response = ['status' => 'error', 'message' => '', 'data' => []];

        $rules = [
            'startDate' => 'required|date_format:Y-m-d',
            'endDate' => 'required|date_format:Y-m-d|gte:startDate'
        ];

        $messages = [];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $response['message'] = $validator->errors();
            return response()->json($response, 400);
        }

        $param_start_date = $request->get("startDate");
        $param_end_date = $request->get("endDate");
        $users = UserPurchaseHistories::query()
            ->select('mask_name', DB::raw('sum(transaction_amount) as dollar'), DB::raw('count(*) as amount'));
        if ($param_start_date) {
            $users->where("transaction_date", ">=", $param_start_date);
        }
        if ($param_end_date) {
            $users->where("transaction_date", "<=", $param_end_date);
        }

        $users->orderByDesc("transaction_amount");
        $response['status'] = 'success';
        $response['data'] = $users->groupBy("mask_name")->get();

        return response()->json($response, 200);
    }
    /**
     * @OA\Tag(
     *     name="B6.",
     *     description="Search for pharmacies or masks by name, ranked by relevance to the search term."
     * )
     * @OA\Get(
     *   tags={"B6."},
     *   path="/api/getPharmaciesOrMasks",
     *   summary="get pharmacies or masks by keyword.",
     *   @OA\Parameter(
     *      parameter="keyword",
     *      in="query",
     *      name="keyword",
     *      description="input keyword name.",
     *      @OA\Schema(
     *          type="string"
     *      )
     *   ),
     *   @OA\Response(response=200, description="OK",
     *      content={
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 example={
     *                    {
     *                      "message": "",
     *                      "data": {
     *                         {
     *                          "name": "pharmacy-name",
     *                          "mask_name": "mask-name"
     *                         },
     *                      }
     *                   }
     *              }
     *          )
     *       }
     *   )
     * )
     */
    public function getPharmaciesOrMasks(Request $request)
    {
        $response = ['status' => 'error', 'message' => '', 'data' => []];

        $rules = [
            'keyword' => 'required|string'
        ];

        $messages = [];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $response['message'] = $validator->errors();
            return response()->json($response, 400);
        }

        $param_keyword = $request->get("keyword");
        $pharmaciesOrMasks = Pharmacies::query()
            ->join('pharmacy_masks', 'pharmacies.id', '=', 'pharmacy_masks.pharmacy_id')
            ->select('pharmacies.name', 'pharmacy_masks.mask_name');
        if ($param_keyword) {
            $pharmaciesOrMasks->where("pharmacies.name", "like", '%' . $param_keyword . '%')
                ->orWhere("pharmacy_masks.mask_name", "like", '%' . $param_keyword . '%');
        }

        $pharmaciesOrMasks->orderByRaw("
                                CASE 
                                    WHEN pharmacies.name LIKE '$param_keyword' THEN 6
                                    WHEN pharmacy_masks.mask_name LIKE '$param_keyword' THEN 5
                                    WHEN pharmacies.name LIKE '$param_keyword%' THEN 4
                                    WHEN pharmacy_masks.mask_name LIKE '$param_keyword%' THEN 3
                                    WHEN pharmacies.name LIKE '%$param_keyword%' THEN 2
                                    WHEN pharmacy_masks.mask_name LIKE '%$param_keyword%' THEN 1
                                    ELSE 0
                                END DESC
                            ");
        $response['status'] = 'success';
        $response['data'] = $pharmaciesOrMasks->get();

        return response()->json($response, 200);
    }
    /**
     * @OA\Tag(
     *     name="B7.",
     *     description="Process a user purchases a mask from a pharmacy, and handle all relevant data changes in an atomic transaction."
     * )
     * @OA\Get(
     *   tags={"B7."},
     *   path="/api/getUser",
     *   summary="get user name and cash balance.",
     *   @OA\Parameter(
     *      parameter="userName",
     *      in="query",
     *      name="userName",
     *      description="input user name.",
     *      @OA\Schema(
     *          type="string"
     *      )
     *   ),
     *   @OA\Response(response=200, description="OK",
     *      content={
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 example={
     *                    {
     *                      "code": 200,
     *                      "message": "",
     *                      "data": {
     *                         "name": "user-name",
     *                         "cash_balance": 333
     *                      }
     *                   }
     *              }
     *          )
     *       }
     *   )
     * )
     */
    public function getUser(Request $request)
    {
        $response = ['status' => 'error', 'message' => '', 'data' => []];

        $rules = [
            'userName' => 'required|string'
        ];

        $messages = [];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $response['message'] = $validator->errors();
            return response()->json($response, 400);
        }

        $user_name = $request->get('userName');
        $user = Users::where('name', $user_name)->first();
        if ($user) {
            $response['status'] = 'success';
            $response['data'] = $user->toArray();
        } else {
            $response['message'] = "User is not exists.";
        }

        return response()->json($response, 200);
    }
    /**
     * @OA\Tag(
     *     name="B7.",
     *     description="Process a user purchases a mask from a pharmacy, and handle all relevant data changes in an atomic transaction."
     * )
     * @OA\Post(
     *   tags={"B7."},
     *   path="/api/addUser",
     *   summary="add user with name and cash balace.",
     *   @OA\Parameter(
     *      parameter="userName",
     *      in="query",
     *      name="userName",
     *      description="input user name.",
     *      @OA\Schema(
     *          type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      parameter="cashBalance",
     *      in="query",
     *      name="cashBalance",
     *      description="input init cashBalance.",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *   ),
     *   @OA\Response(response=200, description="OK",
     *      content={
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 example={
     *                    {
     *                      "code": 200,
     *                      "message": ""
     *                   }
     *              }
     *          )
     *       }
     *   )
     * )
     */
    public function addUser(Request $request)
    {
        $response = ['status' => 'error', 'message' => ''];

        $rules = [
            'userName' => 'required|string',
            'cashBalance' => 'required|numeric'
        ];

        $messages = [];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $response['message'] = $validator->errors();
            return response()->json($response, 400);
        }

        $user_name = $request->get('userName');
        $cash_balance = $request->get('cashBalance');
        $userExists = Users::where('name', $user_name)->first();
        if (!$userExists) {
            Users::create([
                'name' => $user_name,
                'cash_balance' => $cash_balance,
            ]);
        } else {
            $response['message'] = "User exists.";
        }

        return response()->json($response, 200);
    }
    /**
     * @OA\Tag(
     *     name="B7.",
     *     description="Process a user purchases a mask from a pharmacy, and handle all relevant data changes in an atomic transaction."
     * )
     * @OA\Post(
     *   tags={"B7."},
     *   path="/api/updateUserCashBalance",
     *   summary="update user's cash balance.",
     *   @OA\Parameter(
     *      parameter="userName",
     *      in="query",
     *      name="userName",
     *      description="input user name.",
     *      @OA\Schema(
     *          type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      parameter="cashBalance",
     *      in="query",
     *      name="cashBalance",
     *      description="input cashBalance.",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *   ),
     *   @OA\Response(response=200, description="OK",
     *      content={
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 example={
     *                    {
     *                      "message": ""
     *                   }
     *              }
     *          )
     *       }
     *   )
     * )
     */
    public function updateUserCashBalance(Request $request)
    {
        $response = ['status' => 'error', 'message' => ''];

        $rules = [
            'userName' => 'required|string',
            'cashBalance' => 'required|numeric'
        ];

        $messages = [];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $response['message'] = $validator->errors();
            return response()->json($response, 400);
        }

        $user_name = $request->get('userName');
        $cash_balance = $request->get('cashBalance');
        $user = Users::where('name', $user_name)->first();
        if ($user) {
            $user->update([
                'cash_balance' => $user->cash_balance + $cash_balance
            ]);
            $response['status'] = 'success';
        } else {
            $response['data'] = "User is not exists.";
        }

        return response()->json($response, 200);
    }
    /**
     * @OA\Tag(
     *     name="B7.",
     *     description="Process a user purchases a mask from a pharmacy, and handle all relevant data changes in an atomic transaction."
     * )
     * @OA\Post(
     *   tags={"B7."},
     *   path="/api/userPurchaseMasks",
     *   summary="user purchase masks.",
     *   @OA\Parameter(
     *      parameter="userName",
     *      in="query",
     *      name="userName",
     *      description="input user name.",
     *      @OA\Schema(
     *          type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      parameter="pharmacyName",
     *      in="query",
     *      name="pharmacyName",
     *      description="input pharmacy name.",
     *      @OA\Schema(
     *          type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      parameter="maskName",
     *      in="query",
     *      name="maskName",
     *      description="input mask name.",
     *      @OA\Schema(
     *          type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      parameter="transactionAmount",
     *      in="query",
     *      name="transactionAmount",
     *      description="input transaction amount.",
     *      @OA\Schema(
     *          type="number",
     *          format="float"
     *      )
     *   ),
     *   @OA\Parameter(
     *      parameter="transactionDate",
     *      in="query",
     *      name="transactionDate",
     *      description="input transaction date.",
     *      @OA\Schema(
     *          type="string",
     *          format="date-time"
     *      )
     *   ),
     *   @OA\Response(response=200, description="OK",
     *      content={
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 example={
     *                    {
     *                      "message": ""
     *                   }
     *              }
     *          )
     *       }
     *   )
     * )
     */
    public function userPurchaseMasks(Request $request)
    {
        $response = ['status' => 'error', 'message' => ''];

        $rules = [
            'userName' => 'required|string',
            'pharmacyName' => 'required|string',
            'maskName' => 'required|string',
            'transactionAmount' => 'required|numeric',
            'transactionDate' => 'required|date_format:Y-m-d'
        ];

        $messages = [];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $response['message'] = $validator->errors();
            return response()->json($response, 400);
        }

        $user_name = $request->get('userName');
        $pharmacy_name = $request->get('pharmacyName');
        $mask_name = $request->get('maskName');
        $transaction_amount = $request->get('transactionAmount');
        $transaction_date = $request->get('transactionDate');
        $user = Users::where('name', $user_name)->first();
        $pharmacy = Pharmacies::where('name', $pharmacy_name)->first();
        if ($user && $pharmacy) {
            $user->cash_balance = $user->cash_balance - $transaction_amount;
            $user->save();

            UserPurchaseHistories::create([
                'user_id' => $user->id,
                'pharmacy_name' => $pharmacy_name,
                'mask_name' => $mask_name,
                'transaction_amount' => $transaction_amount,
                'transaction_date' => $transaction_date
            ]);

            $pharmacy->cash_balance = $pharmacy->cash_balance + $transaction_amount;
            $pharmacy->save();
            $response['status'] = 'success';
        } else {
            $response['message'] = "User or pharmacy not exists!";
        }

        return response()->json($response);
    }
}
