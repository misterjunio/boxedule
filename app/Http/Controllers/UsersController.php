<?php

namespace App\Http\Controllers;

use App\Lesson;
use App\User;
use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Mail;
use Validator;

class UsersController extends Controller
{		
	/**
	 * User index.
	 *
	 * @param  Request  $request
	 * @return Response
	 */
	public function index(Request $request) {
		return view('users.index', ['users' => User::where([['admin', false], ['name', '!=', 'Guest']])->orderBy('name', 'asc')->paginate(10)]);	
	}
		
	/**
	 * User page.
	 *
	 * @param  Request  $request
	 * @return Response
	 */
	public function show(User $user) {
		return view('users.show', ['user' => $user]);
	}
		
	/**
	 * Edit user profile.
	 *
	 * @param  Request  $request
	 * @return Response
	 */
	public function edit(User $user) {
		return view('users.edit', ['user' => $user]);
	}
		
	/**
	 * Update user profile.
	 *
	 * @param  Request  $request
	 * @return Response
	 */
	public function update(Request $request, User $user) {
		$this->validate($request, [
        'name' => 'required|max:255',
        'email' => 'required|email|max:255|unique:users,email,'.$user->id,
				'password' => 'required|min:6|confirmed'
    ]);
		User::where('id', $user['id'])->update([
				'name' => $request->input('name'),
				'email' => $request->input('email'),
				'password' => bcrypt($request->input('password'))
		]);
		return redirect('/users/' . $user['id']);
	}
		
	/**
	 * Edit user's day limit.
	 *
	 * @param  Request  $request
	 * @return Response
	 */
	public function edit_day_limit(Request $request) {
		return response()->json(User::where('id', intval($request->input('user')))
							->update(['day_limit' => intval($request->input('day_limit'))]));
	}
		
	/**
	 * Edit user's month payment status.
	 *
	 * @param  Request  $request
	 * @return Response
	 */
	public function edit_month_payment(Request $request) {
		User::where('id', intval($request->input('user')))
							->update(['current_month_payment' => boolval($request->input('month_payment'))]);
		return response()->json(boolval($request->input('month_payment')));
	}
		
	/**
	 * Return users list.
	 *
	 * @param  Request  $request
	 * @return Response
	 */
	public function users_list(Request $request) {
		$lesson = Lesson::find(intval($request->input('lesson')));
		$ids = $lesson->users()->lists('user_id');
		return response()->json(User::where([['admin', false], ['name', '!=', 'Guest']])->whereNotIn('id', $ids)->orderBy('name', 'asc')->paginate(8));
	}
	
	/**
	 * Write email to send to all users.
	 *
	 * @param  Request  $request
	 * @return Response
	 */
	public function email_page(Request $request) {
		return view('users.email_page');
	}
	
	/**
	 * Send a global e-mail to all users.
	 *
	 * @param  Request  $request
	 * @return Response
	 */
	public function send_email(Request $request) {
		$this->validate($request, [
				'subject' => 'required|max:255',
				'content' => 'required'
		]);
		$users = User::where([['admin', false], ['name', '!=', 'Guest']])->get();
		foreach ($users as $user) {
			Mail::raw($request->input('content'), function ($message) use ($request, $user) {
				$message->to($user['email']);
				$message->subject($request->input('subject'));
			});
		}
		return redirect('/users');
	}
}
