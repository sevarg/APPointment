<?php

namespace App\Http\Controllers\Auth;

use Auth;
use Validator;
use App\Http\File;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
	/**
	 * @var Int|null
	 */
	private $company_id = null;

	/**
	 * Set the company variable so we know only to get the users based on the current company
	 *
	 * @param Request $request
	 */
	public function __construct(Request $request)
	{
		$this->company_id = $request->company_id ?? null;
	}

	/**
	 * Show the index
	 * Only get the users based on the given company_id or by the company of the logged in user
	 *
	 * @return Response
	 */
	public function index()
	{
		$company_id = $this->company_id ?? get_company()->id;
		$users = Company::find($company_id)->users->where('role', 0);

		return view('pages.users.index', [
			'users' => $users,
			'company_id' => $this->company_id
		]);
	}

	/**
	 * Show the edit form
	 *
	 * @param  Int $user_id
	 * @return Response
	 */
	public function edit($user_id)
	{
		$user = User::find($user_id);

		return view('pages.users.edit', compact('user'));
	}

	/**
	 * Update the user information
	 *
	 * @param  Request $request
	 * @param  Int $user_id
	 * @return Response
	 */
	public function update(Request $request, $user_id)
	{
		$user = User::find($user_id);
		$validator = $this->validator($request->all(), $user);

		if ($validator->fails()) {
			return redirect()->back()->with('errors', $validator->errors()->all())->withInput();
		}

		$user->fill($request->all());

		if ($request->get('password')) {
			$user->password = bcrypt($request->get('password'));
		}

		if ($request->hasFile('avatar')) {
			$path = File::upload($request, 'avatar');

			if (is_array($path)) {
				return redirect()->back()->with('errors', $path)->withInput();
			}

			$user->avatar = $path ?? $user->avatar;
		}

		$user->save();

		return redirect()->back()->with('success', 'Successfully updated');
	}

	/**
	 * Show the create form
	 *
	 * @return Response
	 */
	public function create()
	{
		return view('pages.users.create');
	}

	/**
	 * Create a new user
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function store(Request $request)
	{
		$validator = $this->validator($request->all());
		if ($validator->fails()) {
			return redirect()->back()->with('errors', $validator->errors()->all())->withInput();
		}

		$user = new User;
		$user->fill($request->all());
		$user->company_id = $this->company_id ?? get_company()->id;
		$user->password = bcrypt($request->get('password'));
		$user->role = 0;

		$user->save();

		return redirect('users/' . $user->id . '/edit')->with('success', 'Successfully created');
	}

	/**
	 * Create a new Validor instance
	 *
	 * @param  Request $request
	 * @param  User|null $user
	 * @return Validator
	 */
	public function validator($request, $user = null)
	{
		return Validator::make($request, [
            'firstname' => 'required|max:255',
            'surname' => 'required|max:255',
            'email' => 'required|email|max:255' . (isset($user) && $user->email == $request['email']) ? '' : '|unique:users',
			'phonenumber' => 'required|max:255',
            'password' => 'min:6',
        ]);
	}

	/**
	 * Login using the $user_id
	 *
	 * @param  Int $company_id
	 * @param  Int $user_id
	 * @return Response
	 */
	public function loginUsingId($company_id, $user_id)
	{
		Auth::loginUsingId((int) $user_id);
		return redirect('/');
	}

}
