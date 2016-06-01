<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Validator;
use App\Jobs\Verify;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Verify
{
	/**
	 * Show the calendar
	 *
	 * @return mixed
	 */
	public function index()
	{
		return view('pages.appointments.index');
	}

	/**
	 * Get all appointments from your company
	 *
	 * @param Request $request
	 * @return array
	 */
	public function get(Request $request)
	{
		$start = date('Y-m-d H:i:s', $request->get('start'));
		$end = date('Y-m-d H:i:s', $request->get('end'));
		$appointments = get_company()->appointments([$start, $end])->get();

		return $appointments->map(function($appointment) {
			return collect($appointment, $appointment->appointmentType);
		});
	}

	/**
	 * Show the create form
	 *
	 * @param  Request $request
	 * @return mixed
	 */
	public function create(Request $request)
	{
		$date = date('Y-m-d H:i:s', $request->get('date'));
		$appointment_types = get_company()->appointmentTypes->pluck('name', 'id');

		return view('pages.appointments.create', compact(['date', 'appointment_types']));
	}

	/**
	 * Store a new record
	 *
	 * @param  Request $request
	 * @return mixed
	 */
	public function store(Request $request)
	{
		$validator = $this->verify($request->all());

		if ($validator) {
			return $validator;
		}

		$appointment = new Appointment;
		$appointment->fill($request->all());
		$appointment->save();

		return redirect('appointments/' . $appointment->id . '/edit')->with('success', 'Successfully updated');
	}

	/**
	 * Show the edit form
	 *
	 * @param  int $id
	 * @return mixed
	 */
	public function edit($id)
	{
		$appointment = Appointment::find($id);
		$appointment_types = get_company()->appointmentTypes->pluck('name', 'id');

		return view('pages.appointments.edit', compact(['appointment', 'appointment_types']));
	}

	/**
	 * Update the record by it's $id
	 *
	 * @param  Request $request
	 * @param  int $id
	 * @return mixed
	 */
	public function update(Request $request, $id)
	{
		$validator = $this->verify($request->all());

		if ($validator) {
			return $validator;
		}

		$appointment = Appointment::find($id);
		$appointment->fill($request->all());
		$appointment->save();

		return redirect()->back()->with('success', 'Successfully updated');
	}

	/**
	 * Delete an appointment
	 *
	 * @param  int $id
	 * @return mixed
	 */
	public function delete($id)
	{
		$appointment = Appointment::find($id);
		$appointment->delete();

		return redirect('appointments')->with('success', 'Successfully deleted');
	}

	/**
	 * Create a new Validor instance
	 *
	 * @param  Request $request
	 * @param  mixed $rules
	 * @return Validator
	 */
	public function validator($request, $rules = null)
	{
		return Validator::make($request, [
			'name' => 'required|max:255',
			'scheduled_at' => 'required|date'
		]);
	}

	/**
	 * Count all appointments per month of this year
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getStats()
	{
		$appointments = [];

		for ($i = 1; $i <= 12; $i++) {
			$date = Carbon::create(Carbon::now()->year, $i, 1, 0);

			$appointments[] = get_company()->appointments([
				$date->startOfMonth()->toDateTimeString(),
				$date->endOfMonth()->toDateTimeString()
			])->selectRaw('count(*) as amount')->get()->pluck('amount')->sum();
		}

		return collect($appointments)->flatten();
	}
}
