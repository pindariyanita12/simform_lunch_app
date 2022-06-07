<?php

namespace App\Http\Controllers;

use App\Models\LunchDate;
use App\Models\Record;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;

class AdminController extends Controller
{

    //admin dashboard
    public function show(Request $request)
    {

        if ($request->all() == null) {
            $dateis = date('Y-m-d');
        } else {
            $dateis = $request->date;
        }

        App::setLocale('hi');
        $uniquerecord = Record::select(DB::raw('DISTINCT Date(created_at) as lunchdate,count(is_taken) as totaldishes'))->whereYear('created_at', '=', date('Y'))->whereMonth('created_at', date('m'))->groupBy('lunchdate')->get();

        $totalmonthlydishes = $uniquerecord->sum('totaldishes');

        $totaltrainees = DB::table('records')->join('users', 'users.id', '=', 'records.user_id')->whereDate('records.created_at', '=', $dateis)->where('users.type', '0')->select(DB::raw('COUNT(is_taken) AS uniquerecord'))->first();

        $totalemployees = DB::table('records')->join('users', 'users.id', '=', 'records.user_id')->whereDate('records.created_at', '=', $dateis)->where('users.type', '1')->select(DB::raw('COUNT(is_taken) AS uniquerecord'))->first();

        if ($request->ajax()) {
            if($dateis==null){
                $dateis=date('Y-m-d');
            }
            $record = Record::with('user')->whereDate('created_at','=', $dateis)->get();

            return datatables()->of($record)
                ->editColumn('userempid', function ($userdata) {

                    return empty($userdata->user->emp_id) ? "NA" : $userdata->user->emp_id;
                })
                ->editColumn('username', function ($userdata) {
                    return empty($userdata->user->name) ? "NA" : $userdata->user->name;
                })
                ->addColumn('action', function ($userdata) {
                    $actionBtn = '<a href="' . route('admin.admindashboard.destroy', [$userdata->user->id, $userdata->id]) . '" class="btn btn-danger btn-sm" ><i class="fa fa-trash ">Delete</i></a>';
                    return $actionBtn;
                })

                ->rawColumns(['action'])
                ->make(true);
        }
        return view('admin.admindashboard', ['totaldishes' => $totalmonthlydishes, 'totaltrainees' => $totaltrainees->uniquerecord, 'totalemployees' => $totalemployees->uniquerecord]);

    }

    //off days for admin
    public function offday()
    {
        App::setLocale('hi');
        $dates = LunchDate::all();
        $d = "";
        foreach ($dates as $date) {
            $d = $d . $date->weekend . ',';
        }
        return view('admin.offday', ['dates' => $d]);
    }

    //Datewise records of users
    public function dateWise(Request $request)
    {
        App::setLocale('hi');
        if ($request->ajax()) {
            $idis = $request->date;
            $record = Record::with('user')->whereDate('created_at', '=', $request->date)->get();
            return datatables()->of($record)
                ->editColumn('userempid', function ($userdata) {
                    return empty($userdata->user->emp_id) ? "NA" : $userdata->user->emp_id;
                })
                ->editColumn('username', function ($userdata) {
                    return empty($userdata->user->name) ? "NA" : $userdata->user->name;
                })
                ->addColumn('action', function ($userdata) use ($idis) {
                    $actionBtn = '<a href="' . route('admin.admindashboard.destroy', [$userdata->user->id, $idis]) . '" class="btn btn-danger btn-sm" ><i class="fa fa-trash ">Delete</i></a>';
                    return $actionBtn;
                })
                ->make(true);
        }
        return view('admin.dateWiserecord');

    }

    //monthwise records
    public function monthWise(Request $request)
    {
        App::setLocale('hi');
        if ($request->ajax()) {

            $idis = $request->idis;
            $uniquerecord = DB::table('records')->join('users', 'users.id', '=', 'records.user_id')->whereYear('records.created_at', '=', date('Y'))->whereMonth('records.created_at', '=', $request->idis)->select(DB::raw('DISTINCT users.id,users.emp_id,users.email, users.name,COUNT(is_taken) AS uniquerecord'))->groupBy('users.email')->get();

            return datatables()->of($uniquerecord, $idis)

                ->editColumn('userempid', function ($userdata) {

                    return empty($userdata->user->emp_id) ? "NA" : $userdata->user->emp_id;
                })
                ->editColumn('username', function ($userdata) {
                    return empty($userdata->user->name) ? "NA" : $userdata->user->name;
                })
                ->editColumn('uniquerecord', function ($userdata) {
                    return $userdata->uniquerecord;
                })
                ->addColumn('action', function ($userdata) use ($idis) {
                    $actionBtn = '<a href="' . route('admin.admindashboard.destroy', [$userdata->id, $idis]) . '" class="btn btn-danger btn-sm" ><i class="fa fa-trash ">Delete</i></a>';
                    return $actionBtn;
                })
                ->make(true);
        }

        return view('admin.monthWiserecord');
    }

    public function destroy(Request $request)
    {
        $record = Record::with('user')->where('user_id', $request->id)->whereYear('created_at', '=', date('Y'))->whereMonth('created_at', '=', $request->idis)->get();
        if (count($record) > 1) {
            $record->each->delete();
            return redirect('/admindashboard');

        } else {
            $record = Record::with('user')->where('user_id', $request->id)->first();
            $record->delete();
            return redirect('/admindashboard');

        }

    }
    public function destroymonthwise(Request $request)
    {
        $record = Record::with('user')->where('user_id', $request->id)->whereYear('created_at', '=', date('Y'))->whereMonth('created_at', '=', $request->idis)->get();
        if (count($record) > 1) {
            $record->each->delete();
            return redirect('/daily-dishes');

        } else {
            $record = Record::with('user')->where('user_id', $request->id)->first();
            $record->delete();
            return redirect('/daily-dishes');

        }

    }

    public function dailyDishes(Request $request)
    {
        //  dd($request->all());

        App::setLocale('hi');
        $trainees = DB::table('records')->join('users', 'users.id', '=', 'records.user_id')->whereYear('records.created_at', '=', date('Y'))->where('users.type', '0')->select(DB::raw('DISTINCT users.id,users.emp_id,users.email, users.name,COUNT(is_taken) AS uniquerecord'))->groupBy('users.email')->get();

        $uniquerecord = Record::select(DB::raw('DISTINCT Date(created_at) as lunchdate,count(is_taken) as totaldishes'))->whereYear('created_at', '=', date('Y'))->whereMonth('created_at', date('m'))->groupBy('lunchdate')->get();

        $totaldishes = $uniquerecord->sum('totaldishes');

        if ($request->ajax()) {
            $uniquerecord = Record::select(DB::raw('DISTINCT Date(created_at) as lunchdate,count(is_taken) as totaldishes'))->whereYear('created_at', '=', date('Y'))->whereMonth('created_at', date('m'))->groupBy('lunchdate')->get();
            return datatables()->of($uniquerecord, $trainees)
                ->editColumn('date', function ($userdata) {
                    return empty($userdata->lunchdate) ? "NA" : $userdata->lunchdate;
                })
                ->editColumn('total', function ($userdata) {
                    return empty($userdata->totaldishes) ? "NA" : $userdata->totaldishes;
                })
                ->addColumn('traineename', function ($userdata1) use ($trainees) {
                    return empty($trainees->name) ? "NA" : $trainees->name;
                })

                ->make(true);
        }
        return view('admin.dailydishes', ['totaldishes' => $totaldishes]);
    }

    public function trainees(Request $request)
    {
        // dd(date("m"));
        if ($request->all() == null) {
            $idis = date('Y-m-d');
        } else {
            $idis = $request->idis2;
        }
        $trainees = DB::table('records')->join('users', 'users.id', '=', 'records.user_id')->whereYear('records.created_at', '=', date('Y'))->whereMonth('records.created_at', '=', $request->idis2)->where('users.type', '0')->select(DB::raw('DISTINCT users.id,users.emp_id,users.email, users.name,COUNT(is_taken) AS uniquerecord'))->groupBy('users.email')->get();
        if ($request->ajax()) {
            $idis=$request->idis2;
            return datatables()->of($trainees)
                ->editColumn('trainee_id', function ($userdata) {
                    return empty($userdata->emp_id) ? "NA" : $userdata->emp_id;
                })
                ->addColumn('traineename', function ($userdata) {
                    return empty($userdata->name) ? "NA" : $userdata->name;
                })
                ->addColumn('uniquerecord', function ($userdata) {
                    return empty($userdata->uniquerecord) ? "NA" : $userdata->uniquerecord;
                })
                ->addColumn('action', function ($userdata) use ($idis) {
                    $actionBtn = '<a href="' . route('admin.admindashboard.destroymonthwise', [$userdata->id, $idis]) . '" class="btn btn-danger btn-sm" ><i class="fa fa-trash ">Delete</i></a>';
                    return $actionBtn;
                })
                ->make(true);
        }
        return redirect('admin.dailydishes.trainees');
    }
    public function employees(Request $request)
    {
        // dd($request->all());
        $idis=date('m');
        if ($request->idis == null) {
            dd("inside if");
            $idis = date('m');
        } else {

            $idis = $request->idis;
        }
        if ($request->ajax()) {
            $idis=$request->idis;
            $uniquerecord = DB::table('records')->join('users', 'users.id', '=', 'records.user_id')->whereYear('records.created_at', '=', date('Y'))->whereMonth('records.created_at', '=', $request->idis)->where('users.type', '1')->select(DB::raw('DISTINCT users.id,users.emp_id,users.email, users.name,COUNT(is_taken) AS uniquerecord'))->groupBy('users.email')->get();
            return datatables()->of($uniquerecord)
                ->editColumn('emp_id', function ($userdata) {

                    return empty($userdata->emp_id) ? "NA" : $userdata->emp_id;
                })
                ->addColumn('employeename', function ($userdata) {
                    return empty($userdata->name) ? "NA" : $userdata->name;
                })
                ->addColumn('uniquerecord', function ($userdata) {
                    return empty($userdata->uniquerecord) ? "NA" : $userdata->uniquerecord;
                })
                ->addColumn('action', function ($userdata) use ($idis) {
                    $actionBtn = '<a href="' . route('admin.admindashboard.destroymonthwise', [$userdata->id, $idis]) . '" class="btn btn-danger btn-sm" ><i class="fa fa-trash ">Delete</i></a>';
                    return $actionBtn;
                })
                ->make(true);
        }
        return redirect('admin.dailydishes.employees');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('dataTable')
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->orderBy(1)
            ->buttons(
                Button::make('csv'),
            );
    }

}
