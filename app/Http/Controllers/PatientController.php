<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Models\Patients;

class PatientController extends Controller
{

    public function __construct()
    {
        $this->now = time();
    }

    //Menampilkan semua data
    public function index()
	{
		$apiRes = [];
        try {
			$data = Patients::get();

			if (count($data) < 1) {
				$apiRes['meta'] = [
					'code' => '200',
					'message' => 'Data is empty'
				];
				$apiRes['data'] = [];

				return new Response($apiRes, 200);
			}

			$apiRes['meta'] = [
                'code' => '200',
                'message' => 'Get All Resource'
            ];

			$apiRes['data'] = $data;

            return new Response($apiRes, 200);
        } catch (\Exception $e) {
            $apiRes['meta'] = [
                'code' => '500',
                'message' => 'Server Error'
            ];
            if (env('APP_DEBUG')) $apiRes['meta']['message'] = $e->getMessage();
            return new Response($apiRes, 500);
        }
	}

    //Menginput Data Baru
    public function store(Request $req)
	{
		$apiRes = [];
        try {
			$patientDataInsert = [
                'name' => $req->name,
                'phone' => $req->phone,
                'address' => $req->address,
                'status' => $req->status,
                'in_date_at' => $req->in_date_at,
                'out_date_at' => $req->out_date_at,
                'created_at' => date('Y-m-d H:i:s', $this->now),
            ];
            // menentukan rusles dari setiap inputan
            $rulesPatientDataInsert = [
                'name' => 'required|string|max:255',
                'phone' => 'required|digits_between:10,13',
                'address' => 'required|string',
                'status' => 'required|string|max:255',
                'in_date_at' => 'required|date_format:Y-m-d',
                'out_date_at' => 'required|date_format:Y-m-d',
            ];

			// validasi data yang diinput
			$isValidPatientDataInsert = Validator::make($patientDataInsert, $rulesPatientDataInsert);
			if (!$isValidPatientDataInsert->passes()) {
                $apiRes['meta'] = [
                    'code' => '400',
                    'message' => $isValidPatientDataInsert->messages()->first(),
                ];

                return new Response($apiRes, 400);
            }

            $patient = new Patients();
            // validasi jika ada nama yang sama
            if (isset($req->name)) {
                $data = $patient::where("name" ,$req->name)->first();
                if($data != null){
                    if ($data != $req->name) {
                    $apiRes['meta'] = [
                        'code' => '200',
                        'message' => 'Data Already Exist'
                    ];

                    return new Response($apiRes, 200);
                    }
                }
            }

            // validasi jika ada no. telp yang sama
            if (isset($req->phone)) {
				$checkPhone = $patient::where("phone" ,$req->phone)->first();
				if ($checkPhone != null) {
					if ($checkPhone != $req->phone) {
						$apiRes['meta'] = [
							'code' => '200',
							'message' => 'Phone Number Already Exist',
						];

						return new Response($apiRes, 200);
					}
				}
			}

            $patient->name = $patientDataInsert['name'];
            $patient->phone = $patientDataInsert['phone'];
            $patient->address = $patientDataInsert['address'];
            $patient->status = $patientDataInsert['status'];
            $patient->in_date_at = $patientDataInsert['in_date_at'];
			$patient->out_date_at = $patientDataInsert['out_date_at'];
            $patient->created_at = $patientDataInsert['created_at'];
            $patient->save();

            // Dikarenakan $eloquent->id adalah return null Maka kita harus pakai DB::getPdo()->lastInsertId()
            $patient->id = DB::getPdo()->lastInsertId();

            $apiRes['meta'] = [
                'code' => '201',
                'message' => 'Resource is added successfully'
            ];
            $apiRes['data'] = $patient;

            return new Response($apiRes, 201);
        } catch (\Exception $e) {
            $apiRes['meta'] = [
                'code' => '500',
                'message' => 'Server Error'
            ];
            if (env('APP_DEBUG')) $apiRes['meta']['message'] = $e->getMessage();

            return new Response($apiRes, 500);
        }
	}

    //Menampilkan Data Pasien Berdasarkan ID
    public function show($id)
	{
		$apiRes = [];
        try {
            $data = Patients::find($id);

			if ($data == null) {
				$apiRes['meta'] = [
					'code' => '404',
					'message' => 'Resource not found'
				];

				return new Response($apiRes, 404);
			}

			$apiRes['meta'] = [
                'code' => '200',
                'message' => 'Get Detail Resource'
            ];
            $apiRes['data'] = $data;

            return new Response($apiRes, 200);
        } catch (\Exception $e) {
            $apiRes['meta'] = [
                'code' => '500',
                'message' => 'Server Error'
            ];
            if (env('APP_DEBUG')) $apiRes['meta']['message'] = $e->getMessage();
            return new Response($apiRes, 500);
        }
	}

    public function update(Request $req, $id)
	{
		$apiRes = [];

        try {
			$updateDataPatient = [
                'updated_at' => date('Y-m-d H:i:s', $this->now),
            ];
            $rulesupdateDataPatient = [];

			// only updated data if input not empty
			if ($req->name != null & trim($req->name) != '') {
                $updateDataPatient['name'] = $req->name;
                $rulesupdateDataPatient['name'] = 'required|string|max:255';
            }
			if ($req->phone != null & trim($req->phone) != '') {
                $updateDataPatient['phone'] = $req->phone;
                $rulesupdateDataPatient['phone'] = 'required|digits_between:8,14';
            }
			if ($req->address != null & trim($req->address) != '') {
                $updateDataPatient['address'] = $req->address;
                $rulesupdateDataPatient['address'] = 'required|string';
            }
			if ($req->status_covid != null & trim($req->status_covid) != '') {
                $updateDataPatient['status'] = $req->status_covid;
                $rulesupdateDataPatient['status'] = 'required|string|max:255';
            }
			if ($req->in_date_at != null & trim($req->in_date_at) != '') {
                $updateDataPatient['in_date_at'] = $req->in_date_at;
                $rulesupdateDataPatient['in_date_at'] = 'required|date_format:Y-m-d';
            }
			if ($req->out_date_at != null & trim($req->out_date_at) != '') {
                $updateDataPatient['out_date_at'] = $req->out_date_at;
                $rulesupdateDataPatient['out_date_at'] = 'required|date_format:Y-m-d';
            }

			// validasi input
			// $dataUpdt = array_merge($dataUpdtRecordPatient, $updateDataPatient);
			// $rulesDataUpdt = array_merge($rulesDataUpdtRecordPatient, $rulesupdateDataPatient);
			$isValidDataIns = Validator::make($updateDataPatient, $rulesupdateDataPatient);
			if (!$isValidDataIns->passes()) {
                $apiRes['meta'] = [
                    'code' => '400',
                    'message' => $isValidDataIns->messages()->first(),
                ];

                return new Response($apiRes, 400);
            }

			// check resource
			$data = Patients::find($id);
			if ($data == null) {
				$apiRes['meta'] = [
					'code' => '404',
					'message' => 'patient not found'
				];

				return new Response($apiRes, 404);
			}

			// check uniq phone
			if (isset($updateDataPatient['phone'])) {
				$checkPhone = Patients::where("phone" ,$updateDataPatient['phone'])->first();
				if ($checkPhone != null) {
					if ($checkPhone != $updateDataPatient['phone']) {
						$apiRes['meta'] = [
							'code' => '200',
							'message' => 'Phone Number Already Exist',
						];

						return new Response($apiRes, 200);
					}
				}
			}

			// update data
			$data->update($updateDataPatient);

			// get updated data
			$data = Patients::find($id);

            $apiRes['meta'] = [
                'code' => '200',
                'message' => 'success update patient'
            ];
            $apiRes['data'] = $data;

            return new Response($apiRes, 200);
        } catch (\Exception $e) {
            $apiRes['meta'] = [
                'code' => '500',
                'message' => 'server error'
            ];
            if (env('APP_DEBUG')) $apiRes['meta']['message'] = $e->getMessage();

            return new Response($apiRes, 500);
        }
	}

    //Menghapus Data Pasien Berdasarkan ID
    public function destroy($id)
	{
		$apiRes = [];
        try {
			$data = Patients::find($id);

			if ($data == null) {
				$apiRes['meta'] = [
					'code' => '404',
					'message' => 'Resource not found'
				];

				return new Response($apiRes, 404);
			}

			$data->delete();

            $apiRes['meta'] = [
                'code' => '200',
                'message' => 'Resource is delete successfully'
            ];

            return new Response($apiRes, 200);
        } catch (\Exception $e) {
            $apiRes['meta'] = [
                'code' => '500',
                'message' => 'Server Error'
            ];
            if (env('APP_DEBUG')) $apiRes['meta']['message'] = $e->getMessage();

            return new Response($apiRes, 500);
        }
	}

    // Mencari nama pasien berdasarkan inputan user
    public function search($name)
	{
		$apiRes = [];
        try {
            $data = Patients::where('name', 'like', '%' . $name . '%')->get();

			if (count($data) < 1) {
				$apiRes['meta'] = [
					'code' => '404',
					'message' => 'Resource not found'
				];

				return new Response($apiRes, 404);
			}

            $apiRes['meta'] = [
                'code' => '200',
                'message' => 'Get searched resource'
            ];
            $apiRes['data'] = $data;

            return new Response($apiRes, 200);
        } catch (\Exception $e) {
            $apiRes['meta'] = [
                'code' => '500',
                'message' => 'Server Error'
            ];
            if (env('APP_DEBUG')) $apiRes['meta']['message'] = $e->getMessage();
            return new Response($apiRes, 500);
        }
	}

    // Menampilkan semua data Pasien yang statusnya positive
    public function positive()
	{
		$apiRes = [];
        try {
            $data = Patients::where('status', '=', Patients::TEXT_POSITIVE)->get();

            $cdata = count($data);

            $apiRes['meta'] = [
                'code' => '200',
                'message' => 'Get positive resource'
            ];
            $apiRes['total'] = $cdata;

			$apiRes['data'] = $data;


            return new Response($apiRes, 200);
        } catch (\Exception $e) {
            $apiRes['meta'] = [
                'code' => '500',
                'message' => 'Server Error'
            ];
            if (env('APP_DEBUG')) $apiRes['meta']['message'] = $e->getMessage();
            return new Response($apiRes, 500);
        }
	}

    // Menampilkan semua data Pasien yang statusnya recovered
    public function recovered()
	{
		$apiRes = [];
        try {
            $data = Patients::where('status', '=', Patients::TEXT_RECOVERED)->get();

            $cdata = count($data);

            $apiRes['meta'] = [
                'code' => '200',
                'message' => 'Get recovered resource'
            ];
            $apiRes['total'] = $cdata;

			$apiRes['data'] = $data;


            return new Response($apiRes, 200);
        } catch (\Exception $e) {
            $apiRes['meta'] = [
                'code' => '500',
                'message' => 'Server Error'
            ];
            if (env('APP_DEBUG')) $apiRes['meta']['message'] = $e->getMessage();
            return new Response($apiRes, 500);
        }
	}

    // Menampilkan semua data Pasien yang statusnya dead
    public function dead()
	{
		$apiRes = [];
        try {
            $data = Patients::where('status', '=', Patients::TEXT_DEAD)->get();

            $cdata = count($data);

            $apiRes['meta'] = [
                'code' => '200',
                'message' => 'Get dead resource'
            ];
            $apiRes['total'] = $cdata;

			$apiRes['data'] = $data;


            return new Response($apiRes, 200);
        } catch (\Exception $e) {
            $apiRes['meta'] = [
                'code' => '500',
                'message' => 'Server Error'
            ];
            if (env('APP_DEBUG')) $apiRes['meta']['message'] = $e->getMessage();
            return new Response($apiRes, 500);
        }
	}
}
